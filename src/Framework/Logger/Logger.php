<?php

namespace Framework\Logger;

use Framework\Core\ClassContainer;
use Framework\Logger\LogAdapters\GenericLogAdapter;
use InvalidArgumentException;

class Logger {
    protected array $loggerAdapters = [];
    private ClassContainer $classContainer;

    /**
     * RFC 5424 log levels
     */
    const LOG_EMERG = LOG_EMERG;
    const LOG_ALERT = LOG_ALERT;
    const LOG_CRIT = LOG_CRIT;
    const LOG_ERR = LOG_ERR;
    const LOG_WARNING = LOG_WARNING;
    const LOG_NOTICE = LOG_NOTICE;
    const LOG_INFO = LOG_INFO;
    const LOG_DEBUG = LOG_DEBUG;

    protected static array $logLevelList = [
        LOG_EMERG => 'emergency',
        LOG_ALERT => 'alert',
        LOG_CRIT => 'critical',
        LOG_ERR => 'error',
        LOG_WARNING => 'warning',
        LOG_NOTICE => 'notice',
        LOG_INFO => 'info',
        LOG_DEBUG => 'debug'
    ];

    public function __construct(ClassContainer $classContainer) {
        $this->classContainer = $classContainer;
        // Load generic logger;
        $this->registerLogAdapter('genericLogger', GenericLogAdapter::class);
    }

    public function log(int $level, string $logMessage, string $context, string $type = 'genericLogger') {
        if (!isset($level, $this::$logLevelList)) {
            throw new InvalidArgumentException('Invalid log level \'' . $level . '\'');
        }

        if (isset($this->loggerAdapters[$type])) {
            $this->loggerAdapters[$type]->log($this::$logLevelList[$level], $logMessage, $context);
        }
    }

    public function registerLogAdapter(string $name, string $className): void {
        $this->loggerAdapters[$name] = $this->classContainer->getTransientClass($className);
    }
}