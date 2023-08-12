<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Core\Commands;

use Framework\Server;
use Framework\Core\ClassManager;
use Framework\Cli\CommandInterface;

class Stop implements CommandInterface {
    private ClassManager $classManager;

    public function __construct(ClassManager $classManager) {
        $this->classManager = $classManager;
    }

    public function run(array $commandArgs): string {
        $server = $this->classManager->getClassInstance(Server::class);
        $server->stop();
        return '';
    }

    public function getDescription(?array $commandArgs = null): string {
        return 'Stop the server process.';
    }
}
