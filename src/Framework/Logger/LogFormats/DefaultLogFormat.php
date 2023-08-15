<?php

namespace Framework\Logger\LogFormats;

use Framework\Logger\LogFormatInterface;

class DefaultLogFormat implements LogFormatInterface {
    public function format($level, string|\Stringable $message, array $context = [], string $identifier = ''): string {
        $replace = [];

        foreach ($context as $key => $value) {
            if (!is_array($value) && (!is_object($value) || method_exists($value, '__toString'))) {
                if (strpos($message, '{' . $key . '}') !== false) {
                    $replace['{' . $key . '}'] = $value;
                    unset($context[$key]); // Remove the context key used as a placeholder.
                }
            }
        }

        $formattedMessage = strtr($message, $replace);

        if ($identifier) {
            $identifier = '[' . $identifier . '] ';
        }

        $contextString = !empty($context) ? json_encode($context) : '';

        // Apply additional formatting logic here if needed
        return sprintf(
            "[%s] %s: %s%s %s",
            date('Y-m-d H:i:s'),
            $this->colorize($level, strtoupper($level)),
            $identifier,
            $formattedMessage,
            $contextString
        );
    }

    protected function colorize(string $level, string $text) {
        switch (strtolower($level)) {
            case 'emergency':
            case 'alert':
            case 'critical':
                return "\033[1;31m" . $text . "\033[0m"; // Red
            case 'error':
                return "\033[31m" . $text . "\033[0m"; // Light Red
            case 'warning':
                return "\033[33m" . $text . "\033[0m"; // Yellow
            case 'notice':
            case 'info':
                return "\033[32m" . $text . "\033[0m"; // Green
            case 'debug':
                return "\033[30m" . $text . "\033[0m"; // Gray
            default:
                return $text; // No coloring for unknown levels
        }
    }
}