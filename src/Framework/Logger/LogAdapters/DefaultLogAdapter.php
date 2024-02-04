<?php

namespace Framework\Logger\LogAdapters;

use Framework\Logger\LogFormats\DefaultLogFormat;
use Framework\Configuration\Configuration;
use Framework\Logger\LogAdapterSettings;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class DefaultLogAdapter extends AbstractLogger {
    use LogAdapterSettings;

    public function __construct(DefaultLogFormat $defaultLogFormat, private Configuration $configuration) {
        $this->set([
            'format' => $defaultLogFormat,
            'fileName' => 'latest.log',
            'logPath' => BASE_PATH . '/var/log/',
            'debug' => false,
            'fileSize' => 10485760,
            'rotation' => 20
        ]);
    }

    public function log($level, string|\Stringable $message, array $context = [], $identifier = ''): void {
        // Check if the debug log level is enabled
        if ($this->getValue('debug') === false && $level === LogLevel::DEBUG) {
            return;
        }

        $message = $this->getValue('format')->format($level, $message, $context, $identifier);
        $logPath = $this->getValue('logPath');
        $fileNameFull = $logPath . $this->getValue('fileName');
        if (!file_exists($logPath)) {
            mkdir($logPath, 0666, true);
        }

        if (file_exists($fileNameFull)) {
            if (filesize($fileNameFull) >= $this->getValue('fileSize')) {
                $this->rotateLogFile();
            }
        } else {
            touch($fileNameFull);
        }

        file_put_contents($fileNameFull, preg_replace("/\x1b\[[0-9;]*m/", '', $message) . "\n", FILE_APPEND);
        clearstatcache();
        fwrite(STDOUT, $message . "\n");
    }

    protected function rotateLogFile() {
        // Rename current log file with a timestamp and create a new log file
        $timestamp = date('Y-m-d');
        $newLogFilePath = $this->getValue('logPath') . $timestamp . '.log';
        $suffix = 1;
        while (file_exists($newLogFilePath)) {
            $newLogFilePath = $this->getValue('logPath') . $timestamp . '-' . $suffix . '.log';
            $suffix++;
        }

        rename($this->getValue('logPath') . $this->getValue('fileName'), $newLogFilePath);
    }
}
