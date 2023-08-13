<?php

/**
 * This class contains enable and disable methods.
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework;

use Framework\Core\Module\ModuleEnableInterface;
use Swoole\Event;

use Swoole\Coroutine\System;
use Framework\EventManager\EventManager;
use Framework\Http\Session\Events\BeforePageLoad;
use Framework\Cron\CronManager;
use Framework\Core\Commands\Maintenance;
use Framework\Core\Commands\Stop;
use Framework\Cron\Commands\Cron;
use Framework\Core\Module\ModuleManager;
use Framework\Http\RouteRegister;
use Framework\Layout\Controllers\BasicPage;
use Framework\Layout\Controllers\Index;
use Framework\ViewManager\ViewManager;
use Framework\Cli\Cli;
use Framework\ClI\HttpStart;
use Framework\Cron\HttpStart as CronStart;
use Framework\Core\ClassManager;
use Framework\Database\Commands\Migrate;
use Framework\Http\Session\Cron\SessionCleanup;

class Enable implements ModuleEnableInterface {
    public RouteRegister $register;
    public ViewManager $viewManager;
    public ModuleManager $moduleManager;
    private ClassManager $classManager;
    private Cli $cli;
    private CronManager $cronManager;
    private EventManager $eventManager;

    /**
     * @param ClassManager $classManager
     * @param RouteRegister $register
     * @param ViewManager $viewManager
     * @param ModuleManager $moduleManager
     * @param CronManager $cronManager
     * @param EventManager $eventManager
     * @param Cli $cli
     */
    public function __construct(
        ClassManager $classManager,
        RouteRegister $register,
        ViewManager $viewManager,
        ModuleManager $moduleManager,
        Cli $cli,
        CronManager $cronManager,
        EventManager $eventManager
    ) {
        $this->register = $register;
        $this->viewManager = $viewManager;
        $this->moduleManager = $moduleManager;
        $this->classManager = $classManager;
        $this->cli = $cli;
        $this->cronManager = $cronManager;
        $this->eventManager = $eventManager;
    }

    /**
     * This will register necessary core features.
     *
     * @return void
     */
    public function onEnable() {
        $this->register->registerRouteHandler('/', Index::class);
        $this->viewManager->registerView('EmptyView');
        $this->viewManager->registerView('BasicPage', BasicPage::class, System::readFile(BASE_PATH . '/src/Framework/Layout/Views/BasicPage.php'));
        $this->cli->registerCommandHandler('stop', $this->classManager->getTransientClass(Stop::class));
        $this->cli->registerCommandHandler('maintenance', $this->classManager->getTransientClass(Maintenance::class));
        $this->cli->registerCommandHandler('cron', $this->classManager->getTransientClass(Cron::class));
        $this->cli->registerCommandHandler('migrate', $this->classManager->getTransientClass(Migrate::class));
        $this->cronManager->registerCronJob($this->classManager->getTransientClass(SessionCleanup::class));
        $this->eventManager->registerEventListener('beforePageLoad', BeforePageLoad::class);
        $this->eventManager->registerEventListener('httpStart', HttpStart::class);
        $this->eventManager->registerEventListener('httpStart', CronStart::class);
    }

    /**
     * This will unregister previously registered core features.
     *
     * @return void
     */
    public function onDisable() {
        $this->register->unregisterRoute('/');
        $this->viewManager->unregisterView('EmptyView');
        $this->viewManager->unregisterView('BasicPage');
        $this->cli->unregisterCommand('stop');
        $this->cli->unregisterCommand('cron');
        $this->cli->unregisterCommand('migrate');
        $this->cronManager->unregisterCronJob('session_cleanup');
        $this->eventManager->unregisterEventListener('beforePageLoad', BeforePageLoad::class);
        Event::del($this->cli->stdin);
    }
 }
