<?php

/**
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\Cli;

interface CommandInterface {
    public function run(array $commandArgs): null|string;
    public function getDescription(?array $commandArgs = null): string;
}
