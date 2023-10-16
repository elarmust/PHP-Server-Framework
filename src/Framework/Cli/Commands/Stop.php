<?php

/**
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Cli\Commands;

use Framework\Framework;
use Framework\Container\ClassContainer;
use Framework\Cli\CommandInterface;

class Stop implements CommandInterface {
    public function __construct(private ClassContainer $classContainer) {}

    public function run(array $commandArgs): string {
        $server = $this->classContainer->get(Framework::class);
        $server->stop();
        return '';
    }

    public function getDescription(?array $commandArgs = null): string {
        return 'Stop the server process.';
    }
}
