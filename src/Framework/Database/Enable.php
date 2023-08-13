<?php

/**
 * This class will be called when module is enabled
 *
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Database;

use Framework\Core\ClassContainer;
use Framework\Cli\Cli;
use Framework\Core\Module\ModuleEnableInterface;
use Framework\Database\Commands\Migrate;

class Enable implements ModuleEnableInterface {
    private ClassContainer $classContainer;
    private Cli $cli;

    public function __construct(ClassContainer $classContainer, Cli $cli) {
        $this->classContainer = $classContainer;
        $this->cli = $cli;
    }

    public function onEnable() {
        $command = $this->classContainer->get(Migrate::class, cache: false);
        $this->cli->registerCommandHandler('migrate', $command);
    }

    public function onDisable() {
        // TODO: Implement onDisable() method.
    }
}
