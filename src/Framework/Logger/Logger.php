<?php

namespace Framework\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;
use Psr\Log\InvalidArgumentException;
use Throwable;

class Logger extends AbstractLogger {
    protected array $loggerAdapters = [];

    public function __construct(LoggerInterface $defaultLogger) {
        $this->registerLogAdapter($defaultLogger, 'default');
    }

    /**
     * Write to logs using default logger adapter.
     *
     * @param $level
     * @param string|\Stringable $message
     * @param array $context = []
     * @param string $identifier = '' Optional log identifier.
     */
    public function log($level, string|\Stringable|Throwable $message, array $context = [], string $identifier = ''): void {
        if (!defined(LogLevel::class . '::' . strtoupper($level))) {
            throw new InvalidArgumentException('Invalid log level \'' . $level . '\'');
        }

        if ($message instanceof Throwable) {
            $message = get_class($message) . ': ' . $message->getMessage() . PHP_EOL . 'In ' . $message->getFile() . ' on line ' . $message->getLine() . PHP_EOL . $message->getTraceAsString();
        }

        $this->loggerAdapters['default']->log($level, $message, $context, $identifier);
    }

    public function debug(string|\Stringable|Throwable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::DEBUG, $message, $context, $identifier);
    }

    public function info(string|\Stringable|Throwable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::INFO, $message, $context, $identifier);
    }

    public function notice(string|\Stringable|Throwable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::NOTICE, $message, $context, $identifier);
    }

    public function warning(string|\Stringable|Throwable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::WARNING, $message, $context, $identifier);
    }

    public function error(string|\Stringable|Throwable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::ERROR, $message, $context, $identifier);
    }

    public function critical(string|\Stringable|Throwable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::CRITICAL, $message, $context, $identifier);
    }

    public function alert(string|\Stringable|Throwable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::ALERT, $message, $context, $identifier);
    }

    public function emergency(string|\Stringable|Throwable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::EMERGENCY, $message, $context, $identifier);
    }

    public function getLogger(?string $loggerName = null): LoggerInterface {
        return $this->loggerAdapters[$loggerName] ?? $this->loggerAdapters['default'];
    }

    public function getLogAdapterList(): array {
        return array_keys($this->loggerAdapters);
    }

    public function unregisterLogAdapter(string $loggerName): void {
        if ($loggerName == 'default') {
            throw new InvalidArgumentException('Default logger may not be unregistered!');
        }

        unset($this->loggerAdapters[$loggerName]);
    }

    public function registerLogAdapter(LoggerInterface $loggerClass, string $name): void {
        if (!$loggerClass instanceof LoggerInterface) {
            throw new InvalidArgumentException('Logger \'' . $loggerClass::class . '\' must implement \'' . LoggerInterface::class . '\'!');
        }

        $this->loggerAdapters[$name] = $loggerClass;
    }
}
