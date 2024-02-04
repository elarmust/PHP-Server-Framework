<?php

namespace Framework\Exception;

use Framework\Logger\Logger;
use Psr\Log\LogLevel;
use Throwable;

class ExceptionHandler implements ExceptionHandlerInterface {
    /**
     * @param Logger $logger Logger instance used for logging the exception.
     */
    public function __construct(private Logger $logger) {
    }

    /**
     * Handle uncaught exception.
     * Will pass the exception to the exception's handle method, if it exists.
     *
     * @param Throwable $e Exception to handle.
     *
     * @return void
     */
    public function handle(Throwable $e): void {
        if (method_exists($e, 'handle')) {
            $e->handle();
        } else {
            $this->logger->log(LogLevel::CRITICAL, $e, identifier: 'framework');
        }
    }
}
