<?php

/**
 * Server class that initializes all modules and starts
 * necessary processes for http server, cli and scheduler.
 *
 * Copyright @ Elar Must.
 */

namespace Framework;

use Framework\Logger\LogAdapters\DefaultLogAdapter;
use Framework\Event\Events\WebSocketCloseEvent;
use Framework\Event\Events\WebSocketOpenEvent;
use Framework\Configuration\Configuration;
use Framework\WebSocket\WebSocketRegistry;
use Framework\Event\Events\HttpStartEvent;
use Framework\Event\EventListenerProvider;
use Framework\Container\ClassContainer;
use Framework\Module\ModuleRegistry;
use Framework\Event\EventDispatcher;
use Framework\Database\Migrations;
use Framework\Http\RouteRegistry;
use Framework\Task\TaskScheduler;
use Framework\Database\Database;
use Framework\View\ViewRegistry;
use Framework\Cron\CronManager;
use Framework\Http\HttpRouter;
use Framework\Logger\Logger;
use Framework\Cli\Cli;
use Framework\Init;
use OpenSwoole\Core\Psr\ServerRequest;
use OpenSwoole\WebSocket\Server;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\Http\Response;
use OpenSwoole\Http\Request;
use OpenSwoole\Coroutine;
use OpenSwoole\Constant;
use OpenSwoole\Runtime;
use OpenSwoole\Timer;
use OpenSwoole\Util;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LogLevel;
use Throwable;

class Framework {
    private ModuleRegistry $moduleRegistry;
    private ClassContainer $classContainer;
    private Configuration $configuration;
    private HttpRouter $router;
    private Logger $logger;
    private Server $server;
    private EventDispatcher $EventDispatcher;
    private WebSocketRegistry $webSocketRegistry;
    private Database $database;
    private Init $init;
    private bool $maintenance = false;
    private bool $ssl = false;
    private float $startTime;
    private string $ip;
    private int $port;

    public function __construct() {
        define('FRAMEWORK', $this);
        $serverOptions = [
            'enable_coroutine' => true,
            'pid_file' => BASE_PATH . '/var/server.pid',
            'worker_num' => 2 * Util::getCPUNum(),
            'max_coroutine' => 3000,
            'open_http2_protocol' => true
        ];

        $this->startTime = microtime(true);
        $this->classContainer = new ClassContainer();
        $this->classContainer->set($this);
        $this->classContainer->set($this->classContainer);
        $this->logger = $this->classContainer->get(Logger::class, [$this->classContainer->get(DefaultLogAdapter::class)]);
        $this->logger->log(LogLevel::INFO, 'Starting Framework server...', identifier: 'framework');


        $this->logger->log(LogLevel::INFO, 'Loading configuration...', identifier: 'framework');
        $this->configuration = $this->classContainer->get(Configuration::class);
        $this->configuration->loadConfiguration(BASE_PATH . '/config.json', 'json');
        if ($this->configuration->getConfig('testing')) {
            $this->logger->log(LogLevel::NOTICE, 'Using test configuration.', identifier: 'framework');
            $this->configuration->loadConfiguration(BASE_PATH . '/config_test.json', 'json');
        }

        $errorReporting = (bool) $this->configuration->getConfig('displayPHPErrors') ?: true;
        $this->logger->log(LogLevel::INFO, 'PHP error reporting: ' . ($errorReporting ? 'true' : 'false'), identifier: 'framework');
        ini_set('display_errors', $errorReporting);
        $timeZone = $this->configuration->getConfig('defaultTimeZone') ?: 'UTC';
        $this->logger->log(LogLevel::INFO, 'Default timezone: ' . $timeZone, identifier: 'framework');
        date_default_timezone_set($timeZone);

        $this->ip = $this->configuration->getConfig('ip');
        $this->port = $this->configuration->getConfig('port');

        if ($this->configuration->getConfig('cert.cert') && $this->configuration->getConfig('cert.key')) {
            $swooleSock = Constant::SOCK_TCP | Constant::SSL;
            $this->ssl = true;
        } else {
            $swooleSock = Constant::SOCK_TCP;
        }

        if ($this->ssl) {
            $serverOptions['ssl_cert_file'] = $this->configuration->getConfig('cert.cert');
            $serverOptions['ssl_key_file'] = $this->configuration->getConfig('cert.key');
        }

        $this->logger->log(LogLevel::INFO, 'Initializing database...', identifier: 'framework');
        $databaseInfo = $this->configuration->getConfig('databases.main');
        $databaseParams = $this->classContainer->prepareFunctionArguments(Database::class, parameters: [$databaseInfo['host'], $databaseInfo['port'], $databaseInfo['database'], $databaseInfo['username'], $databaseInfo['password'], $databaseInfo['charset'], 100]);
        $this->database = $this->classContainer->get(Database::class, $databaseParams);


        $this->logger->log(LogLevel::INFO, 'Initializing event dispatcher...', identifier: 'framework');
        $this->EventDispatcher = $this->classContainer->get(EventDispatcher::class, [$this->classContainer->get(EventListenerProvider::class)]);


        $this->logger->log(LogLevel::INFO, 'Initializing HTTP router...', identifier: 'framework');
        $this->router = $this->classContainer->get(HttpRouter::class);


        if (($this->configuration->getConfig('websocket.enabled') ?? false) == true) {
            $this->logger->log(LogLevel::INFO, 'Initializing websocket...', identifier: 'framework');
            $this->webSocketRegistry = $this->classContainer->get(WebSocketRegistry::class);
        }


        $this->logger->log(LogLevel::INFO, 'Initializing module registry...', identifier: 'framework');
        $this->moduleRegistry = $this->classContainer->get(ModuleRegistry::class);

        Coroutine::set(['hook_flags' => Runtime::HOOK_ALL]);
        Coroutine::run(function () {
            $this->init = new Init($this);
            $this->init->start();

            // Load modules.
            foreach ($this->moduleRegistry->getAllModules() as $module) {
                $this->logger->log(LogLevel::INFO, 'Loading module \'' . $module->getName() . '\'...', identifier: 'framework');
                try {
                    $this->moduleRegistry->loadModule($module);
                } catch (Throwable $e) {
                    $this->logger->log(LogLevel::ERROR, $e->getMessage(), identifier: 'framework');
                    $this->logger->log(LogLevel::ERROR, $e->getTraceAsString(), identifier: 'framework');
                }
            }
        });

        // Reset database object.
        $this->classContainer->set($this->classContainer->get(Database::class, $databaseParams, useCache: false));


        $this->logger->log(LogLevel::INFO, 'Starting HTTP server...', identifier: 'framework');
        $this->server = $this->classContainer->get(Server::class, [$this->ip, $this->port, Server::POOL_MODE, $swooleSock]);
        $this->server->set($serverOptions);

        $this->server->on('request', function (Request $request, Response $response) {
            \OpenSwoole\Core\Psr\Response::emit($response, $this->router->process(ServerRequest::from($request)));
        });

        $this->server->on('open', function (Server $server, Request $request) {
            $event = $this->EventDispatcher->dispatch(new WebSocketOpenEvent($server, $request));
            if ($event->isPropagationStopped()) {
                $server->close($request->fd);
                return;
            }
        });

        $this->server->on('message', function (Server $server, Frame $frame) {
            if (($this->configuration->getConfig('websocket.enabled') ?? false) == true) {
                $frame = $this->webSocketRegistry->getMessageHandler()->handle($server, $frame);
                $server->push($frame->fd, $frame->data, $frame->opcode);
            }
        });

        $this->server->on('close', function (Server $server, int $fd) {
            $this->EventDispatcher->dispatch(new WebSocketCloseEvent($server, $fd));
        });

        $this->server->on('Start', function () {
            $this->EventDispatcher->dispatch(new HttpStartEvent($this));
            $this->logger->log(LogLevel::INFO, 'Framework server is ready. Listening on: ' . $this->ip . ' ' . $this->port . ', Load time: ' . round(microtime(true) - $this->startTime, 2) . 's', identifier: 'framework');
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
    public function stop(): void {
        $this->logger->log(LogLevel::INFO, 'Stopping server...', identifier: 'framework');

        // Cancel all timers
        foreach (Timer::list() as $timer) {
            Timer::clear($timer);
        }

        // Cancel all coroutines.
        foreach (Coroutine::list() as $cid) {
            Coroutine::cancel($cid);
        }

        foreach (array_reverse($this->moduleRegistry->getModules()) as $module) {
            $this->logger->log(LogLevel::INFO, 'Unloading module \'' . $module->getName() . '\'...', identifier: 'framework');
            $this->moduleRegistry->unloadModule($module);
        }

        $this->logger->log(LogLevel::INFO, 'Server stopped!', identifier: 'framework');
        $this->init->stop();

        $this->server->shutdown();
    }

    public function getServer(): Server {
        return $this->server;
    }

    public function getDatabase(): Database {
        return $this->database;
    }

    public function getClassContainer(): ClassContainer {
        return $this->classContainer;
    }

    public function getEventDispatcher(): EventDispatcherInterface {
        return $this->EventDispatcher;
    }

    public function getEventListenerProvider(): EventListenerProvider {
        return $this->classContainer->get(EventListenerProvider::class);
    }

    public function getModuleRegistry(): ModuleRegistry {
        return $this->moduleRegistry;
    }

    public function getConfiguration(): Configuration {
        return $this->configuration;
    }

    public function getWebsocket(): WebSocketRegistry {
        return $this->webSocketRegistry;
    }

    public function getTaskScheduler(): TaskScheduler {
        return $this->classContainer->get(TaskScheduler::class);
    }

    public function getViewRegistry(): ViewRegistry {
        return $this->classContainer->get(ViewRegistry::class);
    }

    public function getRouteRegistry(): RouteRegistry {
        return $this->classContainer->get(RouteRegistry::class);
    }

    public function getMigrations(): Migrations {
        return $this->classContainer->get(Migrations::class);
    }

    public function getCli(): Cli {
        return $this->classContainer->get(Cli::class);
    }

    public function getLogger(): Logger {
        return $this->logger;
    }

    public function getCron(): CronManager {
        return $this->classContainer->get(CronManager::class);
    }

    public function getIp(): string {
        return $this->ip;
    }

    public function getPort(): int {
        return $this->port;
    }

    public function getStartTime(): float {
        return $this->startTime;
    }

    public function sslEnabled(): bool {
        return $this->ssl;
    }
}
