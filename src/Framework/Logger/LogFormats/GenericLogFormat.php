<?php

namespace Framework\Logger\LogFormats;

class GenericLogFormat {
    protected string $dateFormat = 'Y-m-d H:i:s';

    public function format(string $level, string $logMessage, array $context, string $identifier): string {
        $formattedMessage = $this->interpolate($logMessage, $context);

        // Apply additional formatting logic here if needed
        $formattedMessage = sprintf("[%s] %s: %s %s", date('Y-m-d H:i:s'), strtoupper($level), $formattedMessage, json_encode($context));

        return $formattedMessage;

        return '[' . date($this->dateFormat) . ' ' . strtoupper($level) . '] [' . $context . ']: [' . $identifier . ']' . $logMessage;
    }

    protected function interpolate($message, array $context = []) {
        $replace = [];

        foreach ($context as $key => $value) {
            $replace['{' . $key . '}'] = $value;
        }

        return strtr($message, $replace);
    }
}