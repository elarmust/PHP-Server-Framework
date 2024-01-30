<?php

namespace Framework\Exception;

use Throwable;

interface ExceptionHandlerInterface {
    /**
     * Handle uncaught exception.
     *
     * @param Throwable $e Exception to handle.
     *
     * @return void
     */
    public function handle(Throwable $e): void;
}