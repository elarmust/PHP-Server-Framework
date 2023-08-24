<?php

/**
 * Server class that initializes all modules and starts
 * necessary processes for http server, cli and scheduler.
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework;

use Framework\Core\ClassContainer;
use OpenSwoole\Constant;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\Util;
use Framework\EventManager\EventManager;
use Framework\Database\Database;
use Framework\Http\HttpRouter;
use Framework\Core\Module\ModuleManager;
use Framework\Configuration\Configuration;
use Framework\Cron\CronManager;
use Framework\Logger\Logger;
use Framework\Enable;
use Framework\Logger\LogAdapters\DefaultLogAdapter;
use OpenSwoole\Timer;
use OpenSwoole\Coroutine;
use OpenSwoole\WebSocket\Server;
use OpenSwoole\Core\Psr\Request;
use OpenSwoole\Core\Psr\Response;
use Psr\Log\LogLevel;

class FrameworkServer {
    private ModuleManager $moduleManager;
    private ClassContainer $classContainer;
    private Configuration $configuration;
    private HttpRouter $router;
    private Logger $logger;
    private Server $server;
    private EventManager $eventManager;
    private bool $maintenance = false;
    private array $wsConnections = [];
    private bool $ssl = false;

    public function __construct() {
        $this->classContainer = new ClassContainer();
        $this->classContainer->set($this);
        $this->classContainer->set($this->classContainer);
        define('SERVER_START_TIME', microtime(true));
        $this->logger = $this->classContainer->get(Logger::class, [$this->classContainer->get(DefaultLogAdapter::class)]);
        $this->logger->log(LogLevel::INFO, 'Starting Framework server...', identifier: 'framework');
        $this->configuration = $this->classContainer->get(Configuration::class);
        $this->configuration->loadConfiguration(BASE_PATH . '/config.json', 'json');
        define('SERVER_IP', $this->configuration->getConfig('ip'));
        define('SERVER_PORT', $this->configuration->getConfig('port'));
        $databaseInfo = $this->configuration->getConfig('databases.main');
        $databaseParams = $this->classContainer->prepareArguments(Database::class, [$databaseInfo['host'], $databaseInfo['port'], $databaseInfo['database'], $databaseInfo['username'], $databaseInfo['password'], $databaseInfo['charset'], 100]);
        $this->classContainer->get(Database::class, $databaseParams);
        $this->classContainer->get(CronManager::class);
        $this->moduleManager = $this->classContainer->get(ModuleManager::class);
        $this->eventManager = $this->classContainer->get(EventManager::class);
        $this->router = $this->classContainer->get(HttpRouter::class);

        if ($this->configuration->getConfig('cert.cert') && $this->configuration->getConfig('cert.key')) {
            $swooleSock = Constant::SOCK_TCP | Constant::SSL;
            $this->ssl = true;
        } else {
            $swooleSock = Constant::SOCK_TCP;
        }

        Coroutine::run(function () {
            $this->classContainer->get(Enable::class)->onEnable();
    
            // Load modules.
            foreach ($this->moduleManager->getModules() as $module) {
                $this->logger->log(LogLevel::INFO, 'Loading module \'' . $module->getName() . '\'...', identifier: 'framework');
                $this->moduleManager->loadModule($module);
            }
        });

        $this->classContainer->set($this->classContainer->get(Database::class, $databaseParams, cache: false));
        $this->server = $this->classContainer->get(Server::class, [SERVER_IP, SERVER_PORT, Server::POOL_MODE, $swooleSock]);
        $this->run();
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
            'worker_num' => 2 * Util::getCPUNum(),
            'max_coroutine' => 3000,
            'open_http2_protocol' => true
        ];

        if ($this->ssl) {
            $set['ssl_cert_file'] = $this->configuration->getConfig('cert.cert');
            $set['ssl_key_file'] = $this->configuration->getConfig('cert.key');
        }

        $this->server->set($set);

        $this->server->setHandler($this->router);

        $this->server->on('message', function (Server $server, Frame $frame) {
            $this->eventManager->dispatchEvent('websocketMessage', [$this, $frame]);
        });

        if (($this->configuration->getConfig('websocket.enabled') ?? false) == true) {
            $this->logger->log(LogLevel::INFO, 'Websocket enabled.', identifier: 'framework');
            $this->server->on('open', function (Server $server, Request $request) {
                $event = $this->eventManager->dispatchEvent('websocketOpen', [$server, &$request]);
                if ($event->isCanceled()) {
                    $server->close($request->fd);
                    return;
                }

                echo "WebSocket connection opened: {$request->fd}\n";
            });

            $this->server->on('message', function (Server $server, Request $frame) {
                echo "Received message: {$frame->data}\n";

                $this->eventManager->dispatchEvent('websocketMessage', [$server, &$frame]);
                // Send a response message back to the client
                $server->push($frame->fd, "Server received: {$frame->data}");
            });

            $this->server->on('close', function (Server $server, int $fd) {
                $this->eventManager->dispatchEvent('websocketClose', [$this, $fd]);
            });
        }

        $this->server->on('Start', function () {
            $this->eventManager->dispatchEvent('httpStart', [$this]);
            $this->logger->log(LogLevel::INFO, 'Framework server is ready. Listening on: ' . SERVER_IP . ' ' . SERVER_PORT . ', Load time: ' . round(microtime(true) - SERVER_START_TIME, 2) . 's', identifier: 'framework');
        });

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
            $this->logger->log(LogLevel::INFO, 'Pausing server activities...', identifier: 'framework');
        } else {
            $this->logger->log(LogLevel::INFO, 'Resuming server activity...', identifier: 'framework');
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
    public function stopServer(): void {
        $this->logger->log(LogLevel::INFO, 'Stopping server...', identifier: 'framework');

        // Cancel all timers
        foreach (Timer::list() as $timer) {
            Timer::clear($timer);
        }

        // Cancel all coroutines.
        foreach (Coroutine::list() as $cid) {
            Coroutine::cancel($cid);
        }

        foreach (array_reverse($this->moduleManager->getModules()) as $module) {
            $this->logger->log(LogLevel::INFO, 'Unloading module \'' . $module->getName() . '\'...', identifier: 'framework');
            $this->moduleManager->unloadModule($module);
        }

        $this->logger->log(LogLevel::INFO, 'Server stopped!', identifier: 'framework');
        $this->classContainer->get(Enable::class)->onDisable();

        $this->server->shutdown();
    }

    public function sslEnabled(): bool {
        return $this->ssl;
    }
}
