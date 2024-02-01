<?php

/**
 * Server class that initializes all modules and starts
 * necessary processes for http server, cli and scheduler.
 *
 * Copyright @ Elar Must.
 */

namespace Framework;

use Framework\Logger\LogAdapters\DefaultLogAdapter;
use Framework\Exception\ExceptionHandlerInterface;
use Framework\Event\Events\WebSocketCloseEvent;
use Framework\Exception\ErrorHandlerInterface;
use Framework\Event\Events\WebSocketOpenEvent;
use Framework\Event\Events\ServerReadyEvent;
use Framework\Configuration\Configuration;
use Framework\WebSocket\WebSocketRegistry;
use Framework\Event\EventListenerProvider;
use Framework\Exception\ExceptionHandler;
use Framework\Container\ClassContainer;
use Framework\Exception\ErrorHandler;
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
use OpenSwoole\Server\Task;
use OpenSwoole\Coroutine;
use OpenSwoole\Constant;
use OpenSwoole\Runtime;
use OpenSwoole\Atomic;
use OpenSwoole\Timer;
use OpenSwoole\Lock;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LogLevel;
use Throwable;

class Framework extends Server {
    private readonly Atomic $workersStarted;
    private readonly Lock $lock;
    private ModuleRegistry $moduleRegistry;
    private ClassContainer $classContainer;
    private Configuration $configuration;
    private HttpRouter $router;
    private Logger $logger;
    private EventDispatcher $eventDispatcher;
    private WebSocketRegistry $webSocketRegistry;
    private Database $database;
    private Init $init;
    private ExceptionHandlerInterface $exceptionHandler;
    private ErrorHandlerInterface $errorHandler;
    private bool $isTestingEnvironment = false;
    private bool $maintenance = false;
    private bool $ssl = false;
    private float $startTime;
    private string $ip;

    public function __construct() {
        define('FRAMEWORK', $this);
        Coroutine::set(['hook_flags' => Runtime::HOOK_ALL]);
        $this->workersStarted = new Atomic();
        $this->lock = new Lock();

        $this->startTime = microtime(true);
        $this->classContainer = new ClassContainer();
        $this->classContainer->set($this);
        $this->classContainer->set($this->classContainer);
        $this->logger = $this->classContainer->get(Logger::class, [$this->classContainer->get(DefaultLogAdapter::class)]);
        $this->logger->info('Starting Framework server...', identifier: 'framework');

        // Set error and exception handlers.
        $this->addExceptionHandler($this->classContainer->get(ExceptionHandler::class));
        $this->addErrorHandler($this->classContainer->get(ErrorHandler::class));

        $this->logger->info('Loading configuration...', identifier: 'framework');
        $this->configuration = $this->classContainer->get(Configuration::class);
        $this->configuration->loadConfiguration(BASE_PATH . '/config.json', 'json');
        if ($this->configuration->getConfig('testing')) {
            $this->isTestingEnvironment = true;
            $this->logger->log(LogLevel::NOTICE, 'Using test configuration.', identifier: 'framework');
            $this->configuration->loadConfiguration(BASE_PATH . '/config_test.json', 'json');
        }

        error_reporting(-1);
        $displayErrors = (bool) $this->configuration->getConfig('displayPHPErrors') ?: true;
        $this->logger->info('PHP error displaying: ' . ($displayErrors ? 'true' : 'false'), identifier: 'framework');
        ini_set('display_errors', $displayErrors);
        $timeZone = $this->configuration->getConfig('defaultTimeZone') ?: 'UTC';
        $this->logger->info('Default timezone: ' . $timeZone, identifier: 'framework');
        date_default_timezone_set($timeZone);

        $this->logger->info('Initializing event dispatcher...', identifier: 'framework');
        $this->eventDispatcher = $this->classContainer->get(EventDispatcher::class, [$this->classContainer->get(EventListenerProvider::class)]);


        $this->logger->info('Initializing HTTP router...', identifier: 'framework');
        $this->router = $this->classContainer->get(HttpRouter::class);


        if ($this->configuration->getConfig('websocket.enabled') === true) {
            $this->logger->info('Initializing websocket...', identifier: 'framework');
            $this->webSocketRegistry = $this->classContainer->get(WebSocketRegistry::class);
        }

        $this->logger->info('Initializing module registry...', identifier: 'framework');
        $this->moduleRegistry = $this->classContainer->get(ModuleRegistry::class);


        $this->logger->info('Preparing HTTP server...', identifier: 'framework');
        $this->ip = $this->configuration->getConfig('ip');
        $this->port = $this->configuration->getConfig('port');

        if ($this->configuration->getConfig('cert.cert') && $this->configuration->getConfig('cert.key')) {
            $swooleSock = Constant::SOCK_TCP | Constant::SSL;
            $this->ssl = true;
        } else {
            $swooleSock = Constant::SOCK_TCP;
        }

        $workerNum = $this->configuration->getConfig('workerNum') ?: 8;
        $workerNum = is_int($workerNum) && $workerNum > 0 ? $workerNum : 8;

        $taskWorkerNum = $this->configuration->getConfig('taskWorkerNum') ?: 1;
        $taskWorkerNum = is_int($taskWorkerNum) && $taskWorkerNum > 0 ? $taskWorkerNum : 1;

        $coroutineNum = $this->configuration->getConfig('maxCoroutines') ?: 4096;
        $coroutineNum = is_int($coroutineNum) && $coroutineNum > 0 ? $coroutineNum : 4096;

        $serverOptions = [
            'enable_coroutine' => true,
            'task_enable_coroutine' => true,
            'pid_file' => BASE_PATH . '/var/server.pid',
            'worker_num' => $workerNum,
            'task_worker_num' => $taskWorkerNum,
            'max_wait_time' => 10,
            'max_coroutine' => $coroutineNum,
            'open_http2_protocol' => true,
            'ssl_cert_file' => $this->configuration->getConfig('cert.cert') ?: '',
            'ssl_key_file' => $this->configuration->getConfig('cert.key') ?: ''
        ];
        parent::__construct(...$this->classContainer->prepareFunctionArguments(parent::class, parameters: [$this->ip, $this->port, Server::POOL_MODE, $swooleSock]));
        $this->set($serverOptions);

        $this->on('workerStart', $this->onWorkerStart(...));
        $this->on('request', $this->onRequest(...));
        $this->on('message', $this->onMessage(...));
        $this->on('open', $this->onConnectionOpen(...));
        $this->on('close', $this->onConnectionClose(...));
        $this->on('start', $this->onServerStart(...));
        $this->on('workerStart', $this->onWorkerStart(...));
        $this->on('workerStop', $this->onWorkerStop(...));
        $this->on('workerExit', $this->onWorkerExit(...));
        $this->on('task', $this->onTask(...));
        $this->start();
    }

    private function onWorkerStart(Framework $framework, int $workerId): void {
        // Start workers one by one.
        if ($this->lock->lock()) {
            $workerType = $framework->isTaskWorker() ? 'task ' : '';
            $this->logger->info('Starting ' . $workerType . 'worker ' . $workerId . '.', identifier: 'framework');
            $this->logger->info('Initializing database for ' . $workerType . 'worker ' . $workerId, identifier: 'framework');
            $databaseInfo = $this->configuration->getConfig('databases.main');
            $this->database = $this->classContainer->get(Database::class, $this->classContainer->prepareFunctionArguments(Database::class, parameters: [
                $databaseInfo['host'],
                $databaseInfo['port'],
                $databaseInfo['database'],
                $databaseInfo['username'],
                $databaseInfo['password'],
                $databaseInfo['charset'],
                $databaseInfo['poolSize']
            ]));

            // Initialize built in features.
            $this->init = new Init($this);
            $this->init->onLoad();
            if ($this->isTaskWorker()) {
                $this->init->onTaskWorkerStart();
            } else {
                $this->init->onWorkerStart();
            }

            // Load modules.
            foreach ($this->moduleRegistry->getAllModules() as $module) {
                $this->logger->info('Loading module \'' . $module->getName() . '\'...', identifier: 'framework');
                try {
                    $this->moduleRegistry->loadModule($this, $module);
                } catch (Throwable $e) {
                    $this->logger->log(LogLevel::ERROR, $e, identifier: 'framework');
                }
            }

            $this->logger->info(ucfirst($workerType . 'worker ') . $workerId . ' is ready.', identifier: 'framework');
            $this->workersStarted->add(1);
            if ($this->workersStarted->get() === $this->setting['worker_num'] + $this->setting['task_worker_num']) {
                $this->eventDispatcher->dispatch(new ServerReadyEvent($this));
                $this->logger->info('Framework server is ready. Listening on: ' . $this->ip . ' ' . $this->port . ', Load time: ' . round(microtime(true) - $this->startTime, 2) . 's', identifier: 'framework');
            }

            $this->lock->unlock();
        }
    }

    private function onWorkerStop(Framework $framework, int $workerId): void {
        // Stop workers one by one.
        if ($this->lock->lock()) {
            $this->logger->info('Stopping worker ' . $workerId . '.', identifier: 'framework');

            // Unload built in features.
            $this->init->onUnload();
            if ($this->isTaskWorker()) {
                $this->init->onTaskWorkerStop();
            } else {
                $this->init->onWorkerStop();
            }

            // Unload modules
            foreach ($this->moduleRegistry->getAllModules() as $module) {
                $this->logger->info('Unloading module \'' . $module->getName() . '\'...', identifier: 'framework');
                try {
                    $this->moduleRegistry->unloadModule($this, $module);
                } catch (Throwable $e) {
                    $this->logger->log(LogLevel::ERROR, $e, identifier: 'framework');
                }
            }

            $this->logger->info('Worker ' . $workerId . ' has stopped.', identifier: 'framework');
            $this->workersStarted->sub(1);
            if ($this->workersStarted->get() === 0) {
                $this->logger->info('Server stopped!', identifier: 'framework');
            }

            $this->lock->unlock();
        }
    }

    private function onWorkerExit(Framework $framework, int $workerId): void {
        // Clear all timers.
        Timer::clearAll();
    }

    private function onServerStart(): void {
        $this->logger->info('Starting server workers...', identifier: 'framework');
    }

    private function onMessage(Framework $framework, Frame $frame): void {
        if ($this->configuration->getConfig('websocket.enabled') === true) {
            $frame = $this->webSocketRegistry->getMessageHandler()->handle($framework, $frame);
            $framework->push($frame->fd, $frame->data, $frame->opcode);
        }
    }

    private function onConnectionOpen(Framework $framework, Request $request): void {
        $event = $this->eventDispatcher->dispatch(new WebSocketOpenEvent($framework, $request));
        if ($event->isPropagationStopped()) {
            $framework->close($request->fd);
            return;
        }
    }

    private function onConnectionClose(Framework $framework, int $fd): void {
        $this->eventDispatcher->dispatch(new WebSocketCloseEvent($framework, $fd));
    }

    private function onRequest(Request $request, Response $response): void {
        \OpenSwoole\Core\Psr\Response::emit($response, $this->router->process(ServerRequest::from($request)));
    }

    public function onTask(Framework $framework, Task $serverTask): void {
        //
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
            $this->logger->info('Pausing server activities...', identifier: 'framework');
        } else {
            $this->logger->info('Resuming server activity...', identifier: 'framework');
        }

        // TODO: pause request and task processing
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
    public function shutdown(): bool {
        $this->logger->info('Stopping server...', identifier: 'framework');
        return parent::shutdown();
    }

    public function addExceptionHandler(ExceptionHandlerInterface $exceptionHandler): void {
        $this->exceptionHandler = $exceptionHandler;

        set_exception_handler([$this->exceptionHandler, 'handle']);
    }

    public function getExcetpionHandler(): ExceptionHandlerInterface {
        return $this->exceptionHandler;
    }

    public function addErrorHandler(ErrorHandlerInterface $errorHandler): void {
        $this->errorHandler = $errorHandler;

        set_error_handler([$this->errorHandler, 'handle']);
    }

    public function reload(): bool {
        $this->logger->info('Reloading server workers...', identifier: 'framework');
        $return = parent::reload();
        $this->logger->info('Server workers have been reloaded!', identifier: 'framework');
        return $return;
    }

    public function getErrorHandler(): ErrorHandlerInterface {
        return $this->errorHandler;
    }

    public function getDatabase(): Database {
        return $this->database;
    }

    public function getClassContainer(): ClassContainer {
        return $this->classContainer;
    }

    public function getEventDispatcher(): EventDispatcherInterface {
        return $this->eventDispatcher;
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

    public function isTaskWorker(): bool {
        # Workers which are serving http requests have ID lower than worker_num
        # @see https://openswoole.com/docs/modules/swoole-server-on-workerstart
        return $this->getWorkerId() >= $this->setting['worker_num'];
    }

    public function isTestingEnvironment(): bool {
        return $this->isTestingEnvironment;
    }
}
