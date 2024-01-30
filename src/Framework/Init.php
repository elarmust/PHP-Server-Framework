<?php

/**
 * This class contains enable and disable methods.
 *
 * Copyright @ Elar Must.
 */

namespace Framework;

use Framework\Layout\Controllers\BasicPage;
use Framework\Database\Commands\Migrate;
use Framework\Cli\Commands\Maintenance;
use Framework\Tests\Commands\Test;
use Framework\Cron\Commands\Cron;
use Framework\Cli\Commands\Stop;
use Framework\Cli\HttpStart;
use Framework\Cron\HttpStart as CronStart;
use Framework\Event\Events\HttpStartEvent;
use Framework\Http\Session\Cron\SessionCleanup;
use Framework\Http\Session\SessionMiddleware;
use Framework\Http\Route;
use Framework\Model\EventListeners\ModelCreate;
use Framework\Model\EventListeners\ModelDelete;
use Framework\Model\EventListeners\ModelLoad;
use Framework\Model\EventListeners\ModelRestore;
use Framework\Model\EventListeners\ModelSave;
use Framework\Model\EventListeners\ModelSet;
use Framework\Model\Events\ModelRestoreEvent;
use Framework\Model\Events\ModelDeleteEvent;
use Framework\Model\Events\ModelSaveEvent;
use Framework\Model\Events\ModelSetEvent;
use Framework\Model\Events\ModelLoadEvent;
use Framework\Model\Events\ModelCreateEvent;
use Framework\View\View;
use OpenSwoole\Event;

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
        $view->setView(BASE_PATH . '/src/Framework/Layout/Views/BasicPage.php');
        $this->framework->getViewRegistry()->registerView('basicPage', $view);

        // Register built in commands.
        $cli = $this->framework->getCli();
        $cli->registerCommandHandler('stop', $classContainer->get(Stop::class, useCache: false));
        $cli->registerCommandHandler('maintenance', $classContainer->get(Maintenance::class, useCache: false));
        $cli->registerCommandHandler('cron', $classContainer->get(Cron::class, useCache: false));
        $cli->registerCommandHandler('migrate', $classContainer->get(Migrate::class, useCache: false));
        $cli->registerCommandHandler('test', $classContainer->get(Test::class, useCache: false));

        // Register built in cron job.
        $this->framework->getCron()->registerCronJob($classContainer->get(SessionCleanup::class, useCache: false));

        // Register built in event listeners.
        $this->framework->getEventListenerProvider()->registerEventListener(HttpStartEvent::class, $classContainer->get(HttpStart::class, useCache: false));
        $this->framework->getEventListenerProvider()->registerEventListener(HttpStartEvent::class, $classContainer->get(CronStart::class, useCache: false));

        $this->framework->getEventListenerProvider()->registerEventListener(ModelCreateEvent::class, $classContainer->get(ModelCreate::class, useCache: false));
        $this->framework->getEventListenerProvider()->registerEventListener(ModelLoadEvent::class, $classContainer->get(ModelLoad::class, useCache: false));
        $this->framework->getEventListenerProvider()->registerEventListener(ModelSetEvent::class, $classContainer->get(ModelSet::class, useCache: false));
        $this->framework->getEventListenerProvider()->registerEventListener(ModelSaveEvent::class, $classContainer->get(ModelSave::class, useCache: false));
        $this->framework->getEventListenerProvider()->registerEventListener(ModelDeleteEvent::class, $classContainer->get(ModelDelete::class, useCache: false));
        $this->framework->getEventListenerProvider()->registerEventListener(ModelRestoreEvent::class, $classContainer->get(ModelRestore::class, useCache: false));
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
        $cli->unregisterCommand('test');
        $this->framework->getCron()->unregisterCronJob('session_cleanup');
        $this->framework->getEventListenerProvider()->unregisterEventListener(HttpStartEvent::class, HttpStart::class);
        $this->framework->getEventListenerProvider()->unregisterEventListener(HttpStartEvent::class, CronStart::class);
        Event::del($cli->stdin);
    }
}
