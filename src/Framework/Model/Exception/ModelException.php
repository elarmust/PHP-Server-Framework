<?php

namespace Framework\Model\Exception;

use Throwable;
use Framework\Logger\Logger;
use RuntimeException;

class ModelException extends RuntimeException {
    /**
     * @param Logger $logger Logger instance used for logging the exception.
     * @param string $message Error message.
     * @param int $code Error code.
     * @param Throwable|null $previous Previous exception used for chaining.
     */
    public function __construct(string $message = '', int $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
