<?php

/**
 * Copyright @ Elar Must.
 */

namespace Framework\Configuration;

use InvalidArgumentException;

class Configuration {
    private array $config = [];
    private string $configRaw = '';
    private string $fileName = '';
    private string $format = '';
    private array $supportedFormats = ['json'];
    public function __construct() {
    }

    public function loadConfiguration($filePath, string $format) {
        if (!in_array($format, $this->supportedFormats)) {
            throw new InvalidArgumentException('Configuration format is not one of the supported formats: ' . implode(', ', $this->supportedFormats) . '!');
        }

        if (file_exists($filePath)) {
            $this->configRaw = file_get_contents($filePath);
            $this->fileName = $filePath;
            $function = 'decode' . $format;
            $this->config = $this->$function();
        } else {
            throw new InvalidArgumentException('Configuration file ' . $filePath . ' does not exist!');
        }
    }

    private function decodeJson(): array {
        return json_decode($this->configRaw, true);
    }

    /*
     * TODO
     */
    private function decodeXML() {
    }

    /*
     * TODO
     */
    private function decodeYML() {
    }

    public function getConfig(string $key = ''): null|bool|int|array|string {
        $getKeys = explode('.', $key);
        if ($key === '' || count($getKeys) < 1) {
            return $this->config;
        }

        $configSection = $this->config;
        foreach ($getKeys as $id => $key) {
            if (isset($configSection[$key])) {
                $configSection = $configSection[$key];
                if (array_key_last($getKeys) == $id) {
                    if ($configSection == (int)$configSection) {
                        return (int)$configSection;
                    } else if (is_bool($configSection)) {
                        return (bool)$configSection;
                    } else if (is_float($configSection)) {
                        return (float)$configSection;
                    }

                    return $configSection;
                }

                continue;
            }

            return $default;
        }

        return $default;
    }

    /*
     * TODO
     */
    public function setConfig(array $keyValue): void {
    }

    public function saveConfig(): void {
        file_put_contents($this->fileName, $this->configRaw);
    }

    public function getConfigFile(): string {
        return $this->fileName;
    }

    public function getRawConfig(): string {
        return $this->configRaw;
    }
}
