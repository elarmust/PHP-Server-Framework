<?php

namespace Framework\Logger\LoggerFormatters;

class GenericLogFormat {
    protected string $dateFormat = 'Y-m-d H:i:s';

    public function format(string $level, $logMessage, string $context): string {
        if (is_array($logMessage)) {
            $logMessage = print_r($logMessage, true);
        }

        return '[' . date($this->dateFormat) . ' ' . strtoupper($level) . '] [' . $context . ']: ' . $logMessage;
    }
}