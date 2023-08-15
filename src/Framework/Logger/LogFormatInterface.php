<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Logger;

interface LogFormatInterface {
    public function format($level, string|\Stringable $message, array $context = []): string;
}