<?php

/**
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Cli\Commands;

use Framework\Framework;
use Framework\Container\ClassContainer;
use Framework\Cli\CommandInterface;

class Maintenance implements CommandInterface {
    public function __construct(private ClassContainer $classContainer) {}

    public function run(array $commandArgs): null|string {
        $commandArgs[1] = strtolower($commandArgs[1] ?? '');
        $server = $this->classContainer->get(Framework::class);
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
