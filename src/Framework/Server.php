<?php

/**
 * Server class that initializes all modules and starts
 * necessary processes for http server, cli and scheduler.
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework;

use Framework\Core\ClassManager;
use Framework\EventManager\EventManager;
use Framework\Database\Database;
use Framework\Http\HttpRouter;
use Framework\Core\Module\ModuleManager;
use Framework\Configuration\Configuration;
use Framework\Cron\CronManager;
use Framework\Logger\Logger;
use Framework\Enable;
use Swoole\Timer;
use Swoole\Coroutine;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Coroutine\Http\Server as HttpServer;
use Swoole\WebSocket\CloseFrame;

class Server {
    private ModuleManager $moduleManager;
    private ClassManager $classManager;
    private Configuration $configuration;
    private CronManager $cronManager;
    private HttpRouter $router;
    private Logger $logger;
    private HttpServer $server;
    private EventManager $eventManager;
    private bool $maintenance = false;
    private array $wsConnections = [];

    public function __construct() {
        Coroutine\run(function () {
            define('SERVER_START_TIME', microtime(true));
            $this->classManager = new ClassManager();
            $this->classManager->setClassInstance($this);
            $this->logger = $this->classManager->getClassInstance(Logger::class);
            $this->logger->log(Logger::LOG_INFO, 'Starting Framework server...', 'framework');
            $this->configuration = $this->classManager->getClassInstance(Configuration::class);
            $this->configuration->loadConfiguration(BASE_PATH . '/config.json', 'json');
            define('SERVER_IP', $this->configuration->getConfig('ip'));
            define('SERVER_PORT', $this->configuration->getConfig('port'));
            $databaseInfo = $this->configuration->getConfig('databases.main');
            $this->classManager->getClassInstance(Database::class, [$databaseInfo['host'], $databaseInfo['port'], $databaseInfo['database'], $databaseInfo['username'], $databaseInfo['password'], $databaseInfo['charset'], 100]);
            $this->cronManager = $this->classManager->getClassInstance(CronManager::class);
            $this->moduleManager = $this->classManager->getClassInstance(ModuleManager::class);
            $this->eventManager = $this->classManager->getClassInstance(EventManager::class);
            $this->router = $this->classManager->getClassInstance(HttpRouter::class);

            // Run internal onEnable().
            $this->classManager->getClassInstance(Enable::class)->onEnable();

            if ($this->configuration->getConfig('cert.cert') && $this->configuration->getConfig('cert.key')) {
                $swooleSock = SWOOLE_SOCK_TCP6 | SWOOLE_SSL;
            } else {
                $swooleSock = SWOOLE_SOCK_TCP6;
            }

            $this->server = $this->classManager->getClassInstance(HttpServer::class, [SERVER_IP, SERVER_PORT, SWOOLE_PROCESS, $swooleSock]);

            // Load modules.
            foreach ($this->moduleManager->getModulesList() as $module) {
                $this->logger->log(Logger::LOG_INFO, 'Loading module \'' . $module->getName() . '\'...', 'framework');
                $this->moduleManager->loadModule($module);
            }

            $this->run();
        });
    }

    /**
     * Run server processes
     *
     * @return void
     */
    public function run(): void {
        $set = [
            'enable_coroutine' => true,
            'pid_file' => BASE_PATH . '/var/server.pid',
            'worker_num' => 2 * swoole_cpu_num(),
            'max_coroutine' => 3000,
            'open_http2_protocol' => true
        ];

        if ($this->server->ssl) {
            $set['ssl_cert_file'] = $this->configuration->getConfig('cert.cert');
            $set['ssl_key_file'] = $this->configuration->getConfig('cert.key');
        }

        $this->server->set($set);

        $this->server->handle('/', function (Request $request, Response $response) {
            go(function () use (&$response, &$request) {
                $request->server['request_uri'] = explode('/', $request->server['request_uri']);
                $result = $this->router->parseRequest($request, $response);
                // Check if the response is still available. It may have been closed previously!
                if ($response->isWritable()) {
                    $response->end($result);
                }
            });
        });

        if (($this->configuration->getConfig('websocket.enabled') ?? false) == true) {
            $this->logger->log(Logger::LOG_INFO, 'Websocket enabled.', 'framework');
            $this->server->handle('/' . ($this->configuration->getConfig('websocket.websocketURLPath') ?? 'ws'), function (Request $request, Response $response) {
                $response->upgrade();
                $event = $this->eventManager->dispatchEvent('websocketOpen', [&$this, &$response]);
                if ($event->isCanceled()) {
                    $response->close();
                    return;
                }

                $objectId = spl_object_id($response);
                $this->wsConnections[$objectId] = $response;

                while (true) {
                    $frame = $response->recv($this->configuration->getConfig('websocket.timeoutSeconds') ?? 600);
                    if ($frame === '') {
                        $this->eventManager->dispatchEvent('websocketClose', [&$this, &$response]);
                        unset($this->wsConnections[$objectId]);
                        $response->close();
                        break;
                    } else if ($frame === false) {
                        $this->eventManager->dispatchEvent('websocketClose', [&$this, &$response]);
                        unset($this->wsConnections[$objectId]);
                        $response->close();
                        break;
                    } else {
                        if ($frame->data == 'close' || get_class($frame) === CloseFrame::class) {
                            $this->eventManager->dispatchEvent('websocketClose', [&$this, &$response]);
                            unset($this->wsConnections[$objectId]);
                            $response->close();
                            break;
                        }

                        $this->eventManager->dispatchEvent('websocketMessage', [&$this, &$frame]);
                    }
                }
            });
        }

        $this->eventManager->dispatchEvent('httpStart', [&$this]);
        $this->logger->log(Logger::LOG_INFO, 'Framework server is ready. Listening on: ' . SERVER_IP . ' ' . SERVER_PORT . ', Load time: ' . round(microtime(true) - SERVER_START_TIME, 2) . 's', 'framework');
        $this->server->start();
    }

    public function getWebsocketConnections(): array {
        return $this->wsConnections;
    }

    /**
     * Set maintenance mode.
     * 
     * @param bool $state
     *
     * @return void
     */
    public function maintenance(bool $state): void {
        if ($state) {
            $this->logger->log(Logger::LOG_INFO, 'Pausing server activities...', 'framework');
        } else {
            $this->logger->log(Logger::LOG_INFO, 'Resuming server activity...', 'framework');
        }

        $this->maintenance = $state;
    }

    /**
     * Get maintenance mode.
     *
     * @return bool
     */
    public function getMaintenance(): bool {
        return $this->maintenance;
    }

    /**
     * Stop server.
     *
     * @return void
     */
    public function stop(): void {
        $this->logger->log(Logger::LOG_INFO, 'Stopping server...', 'framework');

        foreach (Timer::list() as $timer) {
            Timer::clear($timer);
        }

        // Disable cron jobs. This will let cron jobs know to finish what ever they are doing and exit.
        foreach ($this->cronManager->getCronJobs() as $cronJob) {
            $cronJob->disable();
        }

        foreach (Coroutine::list() as $cid) {
            Coroutine::cancel($cid);
        }

        foreach (array_reverse($this->moduleManager->getModulesList()) as $module) {
            $this->logger->log(Logger::LOG_INFO, 'Unloading module \'' . $module->getName() . '\'...', 'framework');
            $this->moduleManager->unloadModule($module);
        }

        $this->logger->log(Logger::LOG_INFO, 'Server stopped!', 'framework');
        $this->classManager->getClassInstance(Enable::class)->onDisable();

        $this->server->shutdown();
    }
}
