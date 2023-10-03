<?php

/**
 * This class contains enable and disable methods.
 *
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework;

use Framework\Core\Module\ModuleEnableInterface;
use Framework\Layout\Controllers\BasicPage;
use Framework\Http\RequestHandler;
use Framework\Http\RouteRegistry;
use Framework\Event\EventListenerProvider;
use Framework\Http\Session\Events\BeforePageLoad;
use Framework\Cron\CronManager;
use Framework\Core\Commands\Maintenance;
use Framework\Core\Commands\Stop;
use Framework\Cron\Commands\Cron;
use Framework\Core\Module\ModuleManager;
use Framework\Http\RouteRegister;
use Framework\View\ViewRegistry;
use Framework\Cli\Cli;
use Framework\ClI\HttpStart;
use Framework\Cron\HttpStart as CronStart;
use Framework\Core\ClassContainer;
use Framework\Database\Commands\Migrate;
use Framework\Event\Events\BeforePageLoadEvent;
use Framework\Event\Events\HttpStartEvent;
use Framework\Http\Session\Cron\SessionCleanup;
use Framework\Http\Session\SessionMiddleware;
use Framework\View\View;
use OpenSwoole\Event;
use OpenSwoole\Coroutine\System;

class Enable implements ModuleEnableInterface {
    public RouteRegistry $routeRegistry;
    public ViewRegistry $viewRegistry;
    public ModuleManager $moduleManager;
    private ClassContainer $classContainer;
    private Cli $cli;
    private CronManager $cronManager;
    private EventListenerProvider $eventListenerProvider;

    /**
     * @param ClassContainer $classContainer
     * @param RouteRegister $register
     * @param ViewRegistry $viewRegistry
     * @param ModuleManager $moduleManager
     * @param Cli $cli
     * @param CronManager $cronManager
     * @param EventListenerProvider $eventListenerProvider
     */
    public function __construct(
        ClassContainer $classContainer,
        RouteRegistry $routeRegistry,
        ViewRegistry $viewRegistry,
        ModuleManager $moduleManager,
        Cli $cli,
        CronManager $cronManager,
        EventListenerProvider $eventListenerProvider
    ) {
        $this->routeRegistry = $routeRegistry;
        $this->viewRegistry = $viewRegistry;
        $this->moduleManager = $moduleManager;
        $this->classContainer = $classContainer;
        $this->cli = $cli;
        $this->cronManager = $cronManager;
        $this->eventListenerProvider = $eventListenerProvider;
    }

    /**
     * Register necessary core features.
     *
     * @return void
     */
    public function onEnable() {
        // Register / root path.
        $route = $this->routeRegistry->registerRoute('/', RequestHandler::class);
        // Add the BasicPage controller.
        $route->setControllerStack([BasicPage::class]);
        // Include session middleware.
        $route->addMiddlewares([SessionMiddleware::class]);

        // Create a new default page view.
        $view = new View();
        $view->setView(System::readFile(BASE_PATH . '/src/Framework/Layout/Views/BasicPage.php'));
        $this->viewRegistry->registerView('basicPage', $view);

        // Register built in commands.
        $this->cli->registerCommandHandler('stop', $this->classContainer->get(Stop::class, cache: false));
        $this->cli->registerCommandHandler('maintenance', $this->classContainer->get(Maintenance::class, cache: false));
        $this->cli->registerCommandHandler('cron', $this->classContainer->get(Cron::class, cache: false));
        $this->cli->registerCommandHandler('migrate', $this->classContainer->get(Migrate::class, cache: false));

        // Register built in cron job.
        $this->cronManager->registerCronJob($this->classContainer->get(SessionCleanup::class, cache: false));

        // Register built in event listeners.
        $this->eventListenerProvider->registerEventListener(HttpStartEvent::class, $this->classContainer->get(HttpStart::class, cache: false));
        $this->eventListenerProvider->registerEventListener(HttpStartEvent::class, $this->classContainer->get(CronStart::class, cache: false));
    }

    /**
     * Unregister previously registered core features.
     *
     * @return void
     */
    public function onDisable() {
        $this->routeRegistry->unregisterRoute('/');
        $this->viewRegistry->unregisterView('basicPage');
        $this->cli->unregisterCommand('stop');
        $this->cli->unregisterCommand('cron');
        $this->cli->unregisterCommand('migrate');
        $this->cronManager->unregisterCronJob('session_cleanup');
        $this->eventListenerProvider->unregisterEventListener(HttpStartEvent::class, HttpStart::class);
        $this->eventListenerProvider->unregisterEventListener(HttpStartEvent::class, CronStart::class);
        Event::del($this->cli->stdin);
    }
 }
