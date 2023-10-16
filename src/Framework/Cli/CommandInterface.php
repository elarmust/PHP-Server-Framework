<?php

/**
 * Copyright @ WW Byte OÜ.
 */

namespace Framework\CLI;

interface CommandInterface {
    public function run(array $commandArgs): null|string;
    public function getDescription(?array $commandArgs = null): string;
}
