<?php

namespace Framework\Logger\LogAdapters;

use Psr\Log\AbstractLogger;
use Framework\Logger\LogAdapterSettings;
use Framework\Logger\LogFormats\DefaultLogFormat;

class DefaultLogAdapter extends AbstractLogger {
    use LogAdapterSettings;

    public function __construct(DefaultLogFormat $defaultLogFormat) {
        $this->set([
            'format' => $defaultLogFormat,
            'fileName' => 'latest.log',
            'logPath' => BASE_PATH . '/var/log/',
            'fileSize' => 10485760,
            'rotation' => 20
        ]);
    }

    public function log($level, string|\Stringable $message, array $context = [], $identifier = ''): void {
        $message = $this->getValue('format')->format($level, $message, $context, $identifier);
        $fileNameFull = $this->getValue('logPath') . $this->getValue('fileName');
        if (file_exists($fileNameFull) && filesize($fileNameFull) >= $this->getValue('fileSize')) {
            $this->rotateLogFile();
        }

        file_put_contents($fileNameFull, preg_replace("/\x1b\[[0-9;]*m/", '', $message) . "\n", FILE_APPEND);
        clearstatcache();
        echo $message . "\n";
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