<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Cli;

interface CommandInterface {
    public function run(array $commandArgs): null|string;
    public function getDescription(?array $commandArgs = null): string;
}
