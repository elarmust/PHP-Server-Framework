<?php

namespace Framework\Localization;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class LocalePackage {
    private array $localePackages = [];
    private array $translations = [];
    private array $numberFormats = [];

    public function __construct(private string $identifier, private string $defaultLocale) {
    }

    public function addLocalePackage(LocalePackage $localePackage): void {
        $this->localePackages[$localePackage->getIdentifier()] = $localePackage;
    }

    public function addTranslations(array $translations, ?string $locale = null): void {
        $locale = $locale ?? $this->defaultLocale;
        $translations = $this->flatten($translations);
        $this->translations[$locale] = array_merge($this->translations[$locale] ?? [], $translations);
    }

    public function getAllTranslations(): array {
        return $this->translations;
    }

    public function get(string $key, array $variables = [], ?string $locale = null): string {
        $locale = $locale ?? $this->defaultLocale;
        $string = $this->translations[$locale][$key] ?? $key;
        foreach ($variables as $variable => $value) {
            $string = str_replace('{' . $variable . '}', $value, $string);
        }

        return $string;
    }

    public function getIdentifier(): string {
        return $this->identifier;
    }

    public function setDefaultLocale(string $locale): void {
        $this->defaultLocale = $locale;
    }

    public function getDefaultLocale(): string {
        return $this->defaultLocale;
    }

    public function getPackages(): array {
        return $this->localePackages;
    }

    public function removeLocalePackage(string $identifier): void {
        unset($this->localePackages[$identifier]);
        $this->build();
    }

    public function getPackageIdentifiers(): array {
        return array_keys($this->localePackages);
    }

    public function setNumberFormat(int $decimals, ?string $decimalSeparator = null, ?string $thousandsSeparator = null, ?string $locale = null): void {
        $locale = $locale ?? $this->defaultLocale;
        $this->numberFormats[$locale] = [
            'decimals' => $decimals,
            'decimal_separator' => $decimalSeparator,
            'thousands_separator' => $thousandsSeparator
        ];
    }

    public function numberFormat(int|float $number, ?string $locale = null): string {
        $locale = $locale ?? $this->defaultLocale;
        $decimals = $this->numberFormats[$locale]['decimals'] ?? 0;
        $decimalSeparator = $this->numberFormats[$locale]['decimalSeparator'] ?? '.';
        $thousandsSeparator = $this->numberFormats[$locale]['thousandsSeparator'] ?? ',';
        return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    public function getNumberFormat(?string $locale = null): array {
        return $locale ? $this->numberFormats[$locale] ?? [] : $this->numberFormats;
    }

    public function build(): void {
        foreach ($this->localePackages as $package) {
            $package->build();
            $this->translations = array_replace_recursive($this->translations, $package->getAllTranslations());
            $this->numberFormats = array_replace_recursive($this->numberFormats, $package->getNumberFormat());
        }
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
