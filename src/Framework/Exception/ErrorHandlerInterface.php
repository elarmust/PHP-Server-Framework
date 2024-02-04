<?php

namespace Framework\Exception;

interface ErrorHandlerInterface {
    /**
     * Handle uncaught errors.
     *
     * @param int $errorNumber Error number.
     * @param string $errorMessage Error message.
     * @param string $errorFile File where the error occurred.
     * @param int $errorLine Line where the error occurred.
     *
     * @return void
     */
    public function handle(int $errorNumber, string $errorMessage, string $errorFile, int $errorLine): void;
}