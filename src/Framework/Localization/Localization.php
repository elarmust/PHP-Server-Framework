<?php

namespace Framework\Localization;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class Localization {
    private array $translations = [];
    private string $fallbackLocale;

    public function __construct(private string $defaultLocale = 'en_US') {
        $this->fallbackLocale = $defaultLocale;
    }

    public function addTranslations(string $locale, array $translations): void {
        $translations = $this->flatten($translations);
        $this->translations[$locale] = array_merge($this->translations[$locale] ?? [], $translations);
    }

    public function get(string $key, array $variables = [], ?string $locale = null): string {
        $locale = $locale ?? $this->defaultLocale;
        $string = $this->translations[$locale][$key] ?? $this->translations[$this->fallbackLocale][$key] ?? $key;
        foreach ($variables as $variable => $value) {
            $string = str_replace('{' . $variable . '}', $value, $string);
        }

        return $string;
    }

    public function setDefaultLocale(string $locale, ?string $fallbackLocale = null): void {
        $this->defaultLocale = $locale;
        if ($fallbackLocale) {
            $this->fallbackLocale = $fallbackLocale;
        }
    }

    public function getDefaultLocale(): string {
        return $this->defaultLocale;
    }

    public function setNumberFormat(string $format, ?string $locale = null): void {
        $locale = $locale ?? $this->defaultLocale;
        $this->translations[$locale]['numeric_format'] = $format;
    }

    protected function flatten($array): array {
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($array));
        $result = [];
        foreach ($iterator as $value) {
            $keys = [];
            foreach (range(0, $iterator->getDepth()) as $depth) {
                $keys[] = $iterator->getSubIterator($depth)->key();
            }
            $result[ join('.', $keys) ] = $value;
        }

        return $result;
    }
}
