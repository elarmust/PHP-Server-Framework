<?php

/**
 * This class will be called when module is enabled
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Database;

use Framework\Core\ClassManager;
use Framework\Cli\Cli;
use Framework\Core\Module\ModuleEnableInterface;
use Framework\Database\Commands\Migrate;

class Enable implements ModuleEnableInterface {
    private ClassManager $classManager;
    private Cli $cli;

    public function __construct(ClassManager $classManager, Cli $cli) {
        $this->classManager = $classManager;
        $this->cli = $cli;
    }

    public function onEnable() {
        $command = $this->classManager->getTransientClass(Migrate::class);
        $this->cli->registerCommandHandler('migrate', $command);
    }

    public function onDisable() {
        // TODO: Implement onDisable() method.
    }
}
