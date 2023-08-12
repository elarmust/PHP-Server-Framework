<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Core\Commands;

use Framework\Server;
use Framework\Core\ClassManager;
use Framework\Cli\CommandInterface;

class Maintenance implements CommandInterface {
    private ClassManager $classManager;

    public function __construct(ClassManager $classManager) {
        $this->classManager = $classManager;
    }

    public function run(array $commandArgs): null|string {
        $commandArgs[1] = strtolower($commandArgs[1] ?? '');
        $server = $this->classManager->getClassInstance(Server::class);
        if ($commandArgs[1] == 'enable') {
            $server->maintenance(true);
            return null;
        } else if ($commandArgs[1] == 'disable') {
            $server->maintenance(false);
            return null;
        }

        return 'Possible arguments: [enable/disable] - Enable or disable maintenance mode.';
    }

    public function getDescription(?array $commandArgs = null): string {
        return 'Enable to disable maintanance mode.';
    }
}
