<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Cli\Commands;

use Framework\Framework;
use Framework\Container\ClassContainer;
use Framework\Cli\CommandInterface;

class Reload implements CommandInterface {
    public function __construct(private ClassContainer $classContainer) {
    }

    public function run(array $commandArgs): string {
        $server = $this->classContainer->get(Framework::class);
        $server->reload();
        return '';
    }

    public function getDescription(?array $commandArgs = null): string {
        return 'Reload server workers.';
    }
}
