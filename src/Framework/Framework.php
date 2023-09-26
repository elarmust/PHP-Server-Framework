<?php

/**
 * Server class that initializes all modules and starts
 * necessary processes for http server, cli and scheduler.
 *
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework;

use Framework\Core\ClassContainer;
use Framework\WebSocket\WebSocketControllerStack;
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
use OpenSwoole\Core\Psr\ServerRequest;
use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use Psr\Log\LogLevel;

class Framework {
    private ModuleManager $moduleManager;
    private ClassContainer $classContainer;
    private Configuration $configuration;
    private HttpRouter $router;
    private Logger $logger;
    private Server $server;
    private EventManager $eventManager;
    private WebSocketControllerStack $webSocketRegistry;
    private bool $maintenance = false;
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
        $this->webSocketRegistry = $this->classContainer->get(WebSocketControllerStack::class);

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

        $this->server->on('request', function (Request $request, Response $response) {
            \OpenSwoole\Core\Psr\Response::emit($response, $this->router->process(ServerRequest::from($request)));
        });

        if (($this->configuration->getConfig('websocket.enabled') ?? false) == true) {
            $this->logger->log(LogLevel::INFO, 'Websocket enabled.', identifier: 'framework');
            $this->server->on('open', function (Server $server, Request $request) {
                $event = $this->eventManager->dispatchEvent('websocketOpen', [$server, &$request]);
                if ($event->isCanceled()) {
                    $server->close($request->fd);
                    return;
                }
            });

            $this->server->on('message', function (Server $server, Frame $frame) {
                $frame = $this->webSocketRegistry->execute($server, $frame);
                $server->push($frame->fd, $frame->data, $frame->opcode);
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

    public function getServer(): Server {
        return $this->server;
    }

    public function sslEnabled(): bool {
        return $this->ssl;
    }
}
