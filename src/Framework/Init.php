<?php

/**
 * This class contains enable and disable methods.
 *
 * Copyright @ Elar Must.
 */

namespace Framework;

use Framework\Model\EventListeners\ModelRestore;
use Framework\Http\Session\Task\SessionGCTask;

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
use Framework\Http\Session\SessionManager;
use Framework\Model\Events\ModelSetEvent;
use Framework\Database\Commands\Migrate;
use Framework\Cli\Commands\Maintenance;
use Framework\Cron\Task\CronTaskDelay;
use Framework\Cli\Commands\Reload;
use Framework\Tests\Commands\Test;
use Framework\Cron\Commands\Cron;
use Framework\Cli\Commands\Stop;
use Framework\Utils\TimeUtils;
use Framework\Vault\Vault;
use Framework\Http\Route;
use Framework\View\View;
use OpenSwoole\Event as SwooleEvent;
use DateTime;
use Framework\Vault\Table;

class Init {
    public static function beforeWorkers(Framework $framework): void {
        if ($framework->getConfiguration()->getConfig('session.enabled') == true) {
            $rowCount = $framework->getConfiguration()->getConfig('sessionCacheRowCount') ?: 1024;
            $rowCount = is_int($rowCount) && $rowCount >= 1024 ? $rowCount : 1024;
            $dataLength = $framework->getConfiguration()->getConfig('sessionCacheDataLengthBytes') ?: 4096;
            $dataLength = is_int($rowCount) && $rowCount >= 4096 ? $rowCount : 4096;
            // Add session table to the vault.
            $table = new Table('session', $rowCount);
            $table->column('data', Table::TYPE_STRING, $dataLength);
            $table->column('timestamp', Table::TYPE_INT, 4);
            $table->create();
            Vault::addTable($table);
        }
    }

    public static function onWorkerStart(Framework $framework): void {
        $classContainer = $framework->getClassContainer();

        $sessionEnabled = $framework->getConfiguration()->getConfig('session.enabled') == true;
        // Set up session manager.
        if ($sessionEnabled) {
            $classContainer->get(SessionManager::class);
        }

        // Register model events.
        $framework->getEventListenerProvider()->registerEventListener(ModelCreateEvent::class, $classContainer->get(ModelCreate::class, useCache: false));
        $framework->getEventListenerProvider()->registerEventListener(ModelLoadEvent::class, $classContainer->get(ModelLoad::class, useCache: false));
        $framework->getEventListenerProvider()->registerEventListener(ModelSetEvent::class, $classContainer->get(ModelSet::class, useCache: false));
        $framework->getEventListenerProvider()->registerEventListener(ModelSaveEvent::class, $classContainer->get(ModelSave::class, useCache: false));
        $framework->getEventListenerProvider()->registerEventListener(ModelDeleteEvent::class, $classContainer->get(ModelDelete::class, useCache: false));
        $framework->getEventListenerProvider()->registerEventListener(ModelRestoreEvent::class, $classContainer->get(ModelRestore::class, useCache: false));

        if (!$framework->isTaskWorker()) {
            // Register / root path.
            $route = new Route('/');
            $route->setControllerStack([BasicPage::class]);
            $route->addMiddlewares([SessionMiddleware::class]);
            $framework->getRouteRegistry()->registerRoute($route);
    
            // Create a new default page view.
            $view = new View();
            $view->setView(BASE_PATH . '/src/Framework/Layout/Views/BasicPage.php');
            $framework->getViewRegistry()->registerView('basicPage', $view);
            return;
        }

        // Register built in commands.
        $cli = $framework->getCli();
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
        $framework->getTaskScheduler()->schedule($cronTaskDelay, TimeUtils::getMillisecondsToDateTime($nextMinute));

        if ($sessionEnabled) {
            // Register built in session GC task.
            $gcMillis = $framework->getConfiguration()->getConfig('session.sessionGCMilliSeconds') ?: 60000;
            $gcMillis = is_int($gcMillis) && $gcMillis >= 1000 ? $gcMillis : 1000;

            $framework->getTaskScheduler()->scheduleRecurring($classContainer->get(SessionGCTask::class, useCache: false), TimeUtils::getMillisecondsToDateTime($nextMinute));
        }
    }

    public static function onWorkerStop(Framework $framework): void {
        if (!$framework->isTaskWorker()) {
            $framework->getRouteRegistry()->unregisterRoute('/');
            $framework->getViewRegistry()->unregisterView('basicPage');
            return;
        }

        $cli = $framework->getCli();
        $cli->unregisterCommand('stop');
        $cli->unregisterCommand('reload');
        $cli->unregisterCommand('cron');
        $cli->unregisterCommand('migrate');
        $cli->unregisterCommand('test');
        $framework->getCron()->unregisterCronJob('session_cleanup');
    }
}
