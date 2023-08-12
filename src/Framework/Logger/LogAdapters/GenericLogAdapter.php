<?php

namespace Framework\Logger\LogAdapters;

use Framework\Logger\LoggerFormatters\GenericLogFormat;

class GenericLogAdapter {
    private GenericLogFormat $logFormat;
    private array $logConfig = [
        'logPath' => BASE_PATH . '/var/log/',
        'fileName' => 'log'
    ];

    public function __construct(GenericLogFormat $genericLogFormat) {
        $this->logFormat = $genericLogFormat;

        if (!is_dir($this->logConfig['logPath'])) {
            mkdir($this->logConfig['logPath'], 0775, true);
        }
    }

    public function log(string $level, string $message, string $context): void {
        $message = $this->logFormat->format($level, $message, $context);
        $logPath = $this->logConfig['logPath'] . $this->logConfig['fileName'] . '.log';
        file_put_contents($logPath, $message . "\n", FILE_APPEND);
        echo $message . "\n";
    }
}