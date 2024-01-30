<?php

namespace Framework\Exception;

use Framework\Logger\Logger;
use Psr\Log\LogLevel;

class ErrorHandler implements ErrorHandlerInterface {
    /**
     * @param Logger $logger Logger instance used for logging the error.
     */
    public function __construct(private Logger $logger) {
    }

    /**
     * Handle errors.
     *
     * @param int $errorNumber Error number.
     * @param string $errorMessage Error message.
     * @param string $errorFile File where the error occurred.
     * @param int $errorLine Line where the error occurred.
     *
     * @return void
     */
    public function handle(int $errorNumber, string $errorMessage, string $errorFile, int $errorLine): void {
        $logMessage = $errorNumber . ': ' . $errorMessage . PHP_EOL . ' In ' . $errorFile . ' on line ' . $errorLine;
        $this->logger->log(LogLevel::ERROR, $logMessage, identifier: 'framework');
    }
}
