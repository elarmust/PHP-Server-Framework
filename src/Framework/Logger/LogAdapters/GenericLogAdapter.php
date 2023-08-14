<?php

namespace Framework\Logger\LogAdapters;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Framework\Logger\LogFormats\GenericLogFormat;

class GenericLogAdapter extends AbstractLogger implements LoggerInterface {
    private GenericLogFormat $logFormat;

    public function __construct(GenericLogFormat $genericLogFormat) {
        $this->logFormat = $genericLogFormat;

        if (!is_dir(BASE_PATH . '/var/log/')) {
            mkdir(BASE_PATH . '/var/log/', 0775, true);
        }
    }

    public function log($level, string|\Stringable $message, array $context = []): void {
        $message = $this->logFormat->format($level, $message, $context);
        $logPath = BASE_PATH . '/var/log/log.log';
        file_put_contents($logPath, $message . "\n", FILE_APPEND);
        echo $message . "\n";
    }
}