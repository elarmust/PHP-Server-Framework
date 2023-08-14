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
    private ClassContainer $classContainer;

    public function __construct(ClassContainer $classContainer) {
        $this->classContainer = $classContainer;
        // Load generic logger;
        $this->registerLogAdapter(GenericLogAdapter::class, 'genericLogger');
    }

    public function log($level, string|\Stringable $message, array $context = [], string $identifier = ''): void {
        if (!defined(LogLevel::class . '::' . strtoupper($level))) {
            throw new InvalidArgumentException('Invalid log level \'' . $level . '\'');
        }

        if (isset($this->loggerAdapters['genericLogger'])) {
            $this->loggerAdapters['genericLogger']->log(LogLevel::class . '::' . strtoupper($level), $message, $context);
        }
    }

    public function getLogger(string $loggerName = null) {
        return new $this->loggerAdapters[$loggerName ?? 'genericLogger'];
    }

    public function getLogAdapterList(): array {
        return array_keys($this->loggerAdapters);
    }

    public function unregisterLogAdapter(string $name): void {
        unset($this->loggerAdapters[$name]);
    }

    public function registerLogAdapter(string $loggerClassName, string $name): void {
        if (!isset(class_implements($loggerClassName)[LoggerInterface::class])) {
            throw new InvalidArgumentException('Logger \'' . $loggerClassName . '\' must implement \'' . LoggerInterface::class . '\'!');
        }

        $this->loggerAdapters[$name] = $this->classContainer->get($loggerClassName, cache: false);
    }
}