<?php

/**
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Core\Commands;

use Framework\FrameworkServer;
use Framework\Core\ClassContainer;
use Framework\Cli\CommandInterface;

class Stop implements CommandInterface {
    private ClassContainer $classContainer;

    public function __construct(ClassContainer $classContainer) {
        $this->classContainer = $classContainer;
    }

    public function run(array $commandArgs): string {
        $server = $this->classContainer->get(FrameworkServer::class);
        $server->stopServer();
        return '';
    }

    public function getDescription(?array $commandArgs = null): string {
        return 'Stop the server process.';
    }
}
