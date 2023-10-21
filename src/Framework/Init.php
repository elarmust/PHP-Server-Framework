<?php

/**
 * This class contains enable and disable methods.
 *
 * Copyright @ WW Byte OÜ.
 */

namespace Framework;

use Framework\Layout\Controllers\BasicPage;
use Framework\Cli\Commands\Maintenance;
use Framework\Cli\Commands\Stop;
use Framework\Cron\Commands\Cron;
use Framework\ClI\HttpStart;
use Framework\Cron\HttpStart as CronStart;
use Framework\Database\Commands\Migrate;
use Framework\Event\Events\HttpStartEvent;
use Framework\Http\Route;
use Framework\Http\Session\Cron\SessionCleanup;
use Framework\Http\Session\SessionMiddleware;
use Framework\View\View;
use OpenSwoole\Event;
use OpenSwoole\Coroutine\System;

class Init {
    /**
     * @param Framework $framework
     */
    public function __construct(private Framework $framework) {
    }

    /**
     * Register necessary Container features.
     *
     * @return void
     */
    public function start() {
        $classContainer = $this->framework->getClassContainer();

        // Register / root path.
        $route = new Route('/');
        $route->setControllerStack([BasicPage::class]);
        $route->addMiddlewares([SessionMiddleware::class]);
        $this->framework->getRouteRegistry()->registerRoute($route);

        // Create a new default page view.
        $view = new View();
        $view->setView(System::readFile(BASE_PATH . '/src/Framework/Layout/Views/BasicPage.php'));
        $this->framework->getViewRegistry()->registerView('basicPage', $view);

        // Register built in commands.
        $cli = $this->framework->getCli();
        $cli->registerCommandHandler('stop', $classContainer->get(Stop::class, singleton: false));
        $cli->registerCommandHandler('maintenance', $classContainer->get(Maintenance::class, singleton: false));
        $cli->registerCommandHandler('cron', $classContainer->get(Cron::class, singleton: false));
        $cli->registerCommandHandler('migrate', $classContainer->get(Migrate::class, singleton: false));

        // Register built in cron job.
        $this->framework->getCron()->registerCronJob($classContainer->get(SessionCleanup::class, singleton: false));

        // Register built in event listeners.
        $this->framework->getEventListenerProvider()->registerEventListener(HttpStartEvent::class, $classContainer->get(HttpStart::class, singleton: false));
        $this->framework->getEventListenerProvider()->registerEventListener(HttpStartEvent::class, $classContainer->get(CronStart::class, singleton: false));
    }

    /**
     * Unregister previously registered Container features.
     *
     * @return void
     */
    public function stop() {
        $this->framework->getRouteRegistry()->unregisterRoute('/');
        $this->framework->getViewRegistry()->unregisterView('basicPage');
        $cli = $this->framework->getCli();
        $cli->unregisterCommand('stop');
        $cli->unregisterCommand('cron');
        $cli->unregisterCommand('migrate');
        $this->framework->getCron()->unregisterCronJob('session_cleanup');
        $this->framework->getEventListenerProvider()->unregisterEventListener(HttpStartEvent::class, HttpStart::class);
        $this->framework->getEventListenerProvider()->unregisterEventListener(HttpStartEvent::class, CronStart::class);
        Event::del($cli->stdin);
    }
}
