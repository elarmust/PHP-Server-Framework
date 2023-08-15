<?php

namespace Framework\Logger;

use Framework\Core\ClassContainer;
use Framework\Logger\LogAdapters\GenericLogAdapter;
use Psr\Log\LogLevel;
use Psr\Log\LoggerTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\InvalidArgumentException;

class Logger extends LogLevel implements LoggerInterface {
    use LoggerTrait;

    protected array $loggerAdapters = [];
    protected array $loggerAdaptersByIdentifier = [];
    private ClassContainer $classContainer;

    public function __construct(ClassContainer $classContainer) {
        $this->classContainer = $classContainer;
        // Load generic logger;
        $this->registerLogAdapter(GenericLogAdapter::class, 'default');
    }

    /**
     * Write to logs using default logger adapter.
     * 
     * @param $level
     * @param string|\Stringable $message
     * @param array $context = []
     * @param string $identifier = '' Optional log identifier.
     */
    public function log($level, string|\Stringable $message, array $context = [], string $identifier = ''): void {
        if (!defined(LogLevel::class . '::' . strtoupper($level))) {
            throw new InvalidArgumentException('Invalid log level \'' . $level . '\'');
        }

        $this->loggerAdapters['default']->log($level, $message, $context, $identifier);
    }

    public function debug(string|\Stringable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::DEBUG, $message, $context, $identifier);
    }

    public function info(string|\Stringable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::INFO, $message, $context, $identifier);
    }

    public function notice(string|\Stringable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::NOTICE, $message, $context, $identifier);
    }

    public function warning(string|\Stringable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::WARNING, $message, $context, $identifier);
    }

    public function error(string|\Stringable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::ERROR, $message, $context, $identifier);
    }

    public function critical(string|\Stringable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::CRITICAL, $message, $context, $identifier);
    }

    public function alert(string|\Stringable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::ALERT, $message, $context, $identifier);
    }

    public function emergency(string|\Stringable $message, array $context = [], string $identifier = ''): void {
        $this->log(LogLevel::EMERGENCY, $message, $context, $identifier);
    }

    public function getLogger(?string $loggerName = null) {
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

    public function registerLogAdapter(string $loggerClassName, string $name): void {
        if (!isset(class_implements($loggerClassName)[LoggerInterface::class])) {
            throw new InvalidArgumentException('Logger \'' . $loggerClassName . '\' must implement \'' . LoggerInterface::class . '\'!');
        }

        $this->loggerAdapters[$name] = $this->classContainer->get($loggerClassName, cache: false);
    }
}