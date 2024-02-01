<?php

/**
 * This class contains enable and disable methods.
 *
 * Copyright @ Elar Must.
 */

namespace Framework;

use Framework\Model\EventListeners\ModelRestore;
use Framework\Http\Session\Cron\SessionCleanup;
use Framework\Model\EventListeners\ModelCreate;
use Framework\Model\EventListeners\ModelDelete;
use Framework\Http\Session\SessionMiddleware;
use Framework\Model\EventListeners\ModelLoad;
use Framework\Model\EventListeners\ModelSave;
use Framework\Model\Events\ModelRestoreEvent;
use Framework\Model\EventListeners\ModelSet;
use Framework\Model\Events\ModelDeleteEvent;
use Framework\Model\Events\ModelCreateEvent;
use Framework\Layout\Controllers\BasicPage;
use Framework\Model\Events\ModelSaveEvent;
use Framework\Model\Events\ModelLoadEvent;
use Framework\Model\Events\ModelSetEvent;
use Framework\Database\Commands\Migrate;
use Framework\Cli\Commands\Maintenance;
use Framework\Cron\Task\CronTaskDelay;
use Framework\Cli\Commands\Reload;
use Framework\Tests\Commands\Test;
use Framework\Cron\Commands\Cron;
use Framework\Cli\Commands\Stop;
use Framework\Utils\TimeUtils;
use Framework\Http\Route;
use Framework\View\View;
use OpenSwoole\Event as SwooleEvent;
use DateTime;

class Init {
    /**
     * @param Framework $framework
     */
    public function __construct(private Framework $framework) {
    }

    public function onLoad() {
        $classContainer = $this->framework->getClassContainer();
        $this->framework->getEventListenerProvider()->registerEventListener(ModelCreateEvent::class, $classContainer->get(ModelCreate::class, useCache: false));
        $this->framework->getEventListenerProvider()->registerEventListener(ModelLoadEvent::class, $classContainer->get(ModelLoad::class, useCache: false));
        $this->framework->getEventListenerProvider()->registerEventListener(ModelSetEvent::class, $classContainer->get(ModelSet::class, useCache: false));
        $this->framework->getEventListenerProvider()->registerEventListener(ModelSaveEvent::class, $classContainer->get(ModelSave::class, useCache: false));
        $this->framework->getEventListenerProvider()->registerEventListener(ModelDeleteEvent::class, $classContainer->get(ModelDelete::class, useCache: false));
        $this->framework->getEventListenerProvider()->registerEventListener(ModelRestoreEvent::class, $classContainer->get(ModelRestore::class, useCache: false));
    }

    /**
     * Register necessary Container features.
     *
     * @return void
     */
    public function onWorkerStart() {
        // Register / root path.
        $route = new Route('/');
        $route->setControllerStack([BasicPage::class]);
        $route->addMiddlewares([SessionMiddleware::class]);
        $this->framework->getRouteRegistry()->registerRoute($route);

        // Create a new default page view.
        $view = new View();
        $view->setView(BASE_PATH . '/src/Framework/Layout/Views/BasicPage.php');
        $this->framework->getViewRegistry()->registerView('basicPage', $view);
    }

    public function onTaskWorkerStart() {
        $classContainer = $this->framework->getClassContainer();

        // Register built in commands.
        $cli = $this->framework->getCli();
        $cli->registerCommandHandler('stop', $classContainer->get(Stop::class, useCache: false));
        $cli->registerCommandHandler('reload', $classContainer->get(Reload::class, useCache: false));
        $cli->registerCommandHandler('maintenance', $classContainer->get(Maintenance::class, useCache: false));
        $cli->registerCommandHandler('cron', $classContainer->get(Cron::class, useCache: false));
        $cli->registerCommandHandler('migrate', $classContainer->get(Migrate::class, useCache: false));
        $cli->registerCommandHandler('test', $classContainer->get(Test::class, useCache: false));

        $cli->stdin = fopen('php://stdin', 'r');
        stream_set_blocking($cli->stdin, 0);
        SwooleEvent::add($cli->stdin, function () use ($cli) {
            $line = trim(fgets($cli->stdin));

            if ($line !== '') {
                $cli->runCommand(explode(' ', $line));
                readline_add_history($line);
                readline_write_history();
            }
        });

        $nextMinute = new DateTime();
        $nextMinute->modify('+1 minute');
        $nextMinute->setTime($nextMinute->format('H'), $nextMinute->format('i'), 0);
        $cronTaskDelay = $classContainer->get(CronTaskDelay::class);
        $this->framework->getTaskScheduler()->schedule($cronTaskDelay, TimeUtils::getMillisecondsToDateTime($nextMinute));

        // Register built in cron job.
        $this->framework->getCron()->registerCronJob($classContainer->get(SessionCleanup::class, useCache: false));
    }

    public function onUnload() {
    }

    public function onWorkerStop() {
        $this->framework->getRouteRegistry()->unregisterRoute('/');
        $this->framework->getViewRegistry()->unregisterView('basicPage');
    }

    public function onTaskWorkerStop() {
        $cli = $this->framework->getCli();
        $cli->unregisterCommand('stop');
        $cli->unregisterCommand('reload');
        $cli->unregisterCommand('cron');
        $cli->unregisterCommand('migrate');
        $cli->unregisterCommand('test');
        $this->framework->getCron()->unregisterCronJob('session_cleanup');
    }
}
