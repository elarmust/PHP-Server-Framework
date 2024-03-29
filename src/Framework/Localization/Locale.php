<?php

namespace Framework\Localization;

use DateTime;
use Exception;
use DateTimeInterface;
use RecursiveArrayIterator;
use InvalidArgumentException;
use RecursiveIteratorIterator;

class Locale implements LocaleInterface {
    private array $locales = [];
    private array $data = [];

    /**
     * Class representing a locale.
     *
     * @param string $identifier The identifier of the locale.
     * @param string $defaultLocale The default locale.
     */
    public function __construct(private string $identifier, private string $defaultLocale) {
    }

    /**
     * Adds a child Locale class to the current Locale class.
     *
     * @param LocaleInterface $localeClass Child Locale class to add.
     *
     * @return void
     */
    public function addLocale(LocaleInterface $localeClass): void {
        $this->locales[$localeClass->getIdentifier()] = $localeClass;
    }

    /**
     * Adds translations to the Locale class.
     *
     * @param array $translations Translations to add.
     * @param string|null $locale Locale name to add translations to, defaults to the default locale name.
     *
     * @return void
     */
    public function addTranslations(array $translations, ?string $locale = null): void {
        $locale = $locale ?? $this->defaultLocale;
        $translations = $this->flatten($translations);
        $this->data[$locale]['translations'] = array_merge($this->data[$locale]['translations'] ?? [], $translations);
    }

    /**
     * Retrieves all translation data for the locale.
     *
     * @return array Translation data array.
     */
    public function getTranslationData(): array {
        return $this->data;
    }

    /**
     * Retrieves the translated string with the given key.
     *
     * @param string $key Translation key.
     * @param array $variables Variables to be replaced in the translated string.
     * @param string|null $locale Locale name to use for translation, defaults to the default locale name.
     *
     * @return string Translated string.
     */
    public function get(string $key, array $variables = [], ?string $locale = null): string {
        $locale = $locale ?? $this->defaultLocale;
        $string = $this->data[$locale]['translations'][$key] ?? $key;
        foreach ($variables as $variable => $value) {
            $string = str_replace('{' . $variable . '}', $value, $string);
        }

        return $string;
    }

    /**
     * Returns locale identifier.
     *
     * @return string Local identifier.
     */
    public function getIdentifier(): string {
        return $this->identifier;
    }

    /**
     * Sets the default locale name.
     *
     * @param string $locale Default locale name.
     * @return void
     */
    public function setDefaultLocale(string $locale): void {
        $this->defaultLocale = $locale;
    }

    /**
     * Retrieves the default locale name.
     *
     * @return string Default locale name.
     */
    public function getLocale(): string {
        return $this->defaultLocale;
    }

    /**
     * Returns the child Locale classes associated with the Locale class.
     *
     * @return array Child Locale classes.
     */
    public function getLocales(): array {
        return $this->locales;
    }

    /**
     * Removes a Locale class from the list of available Locale classes.
     *
     * @param string $identifier Locale class by identifier to remove.
     *
     * @return void
     */
    public function removeLocale(string $identifier): void {
        unset($this->locales[$identifier]);
        $this->build();
    }

    /**
     * Returns an array of child Locale class identifiers.
     *
     * @return array An array of child Locale class identifiers.
     */
    public function getLocaleIdentifiers(): array {
        return array_keys($this->locales);
    }

    /**
     * Sets the number format for a specific locale.
     *
     * @param int $decimals The number of decimal places to display, defaults to 2.
     * @param string|null $decimalSeparator The character used as the decimal separator. Defaults to '.'.
     * @param string|null $thousandsSeparator The character used as the thousands separator. Defaults to ','.
     * @param string $formatName The name of the number format to set, defaults to 'default'.
     * @param string|null $locale The locale for which to set the number format. If null, the default locale will be used.
     *
     * @return void
     */
    public function addNumberFormat(int $decimals = 2, ?string $decimalSeparator = '.', ?string $thousandsSeparator = ',', string $formatName = 'default', ?string $locale = null): void {
        $locale = $locale ?? $this->defaultLocale;
        $this->data[$locale]['numberFormat'][$formatName] = [
            'decimals' => $decimals,
            'decimalSeparator' => $decimalSeparator,
            'thousandsSeparator' => $thousandsSeparator
        ];
    }

    /**
     * Formats a number according to the specified locale.
     *
     * @param int|float $number The number to format.
     * @param string $formatName The name of the number format to use, defaults to 'default'.
     * @param string|null $locale The locale to use for formatting. If not provided, the default locale will be used.
     *
     * @return string The formatted number.
     */
    public function numberFormat(int|float $number, string $formatName = 'default', ?string $locale = null): string {
        $locale = $locale ?? $this->defaultLocale;
        $format = $this->data[$locale]['numberFormat'][$formatName] ?? [];
        $decimals = $format['decimals'] ?? 2;
        $decimalSeparator = $format['decimalSeparator'] ?? '.';
        $thousandsSeparator = $format['thousandsSeparator'] ?? ',';
        return number_format($number, $decimals, $decimalSeparator, $thousandsSeparator);
    }

    /**
     * Retrieves the number format for the specified locale.
     *
     * @param string|null $locale The locale for which to retrieve the number format. If null, returns all number formats.
     * @param string $formatName The name of the number format to retrieve, defaults to 'default'.
     *
     * @return array The number format for the specified locale, or an empty array if the locale is not found.
     */
    public function getNumberFormat(string $formatName = 'default', ?string $locale = null): array {
        $locale = $locale ?? $this->defaultLocale;
        return $this->data[$locale]['numberFormat'][$formatName] ?? [];
    }

    /**
     * Sets the date format for a specific locale.
     *
     * @param string $format The date format to set.
     * @param string $formatName The name of the date format to set, defaults to 'default'.
     *
     * @param string|null $locale The locale for which to set the date format. If null, the default locale will be used.
     *
     * @return void
     */
    public function addDateFormat(string $format, string $formatName = 'default', ?string $locale = null): void {
        $locale = $locale ?? $this->defaultLocale;
        $this->data[$locale]['dateFormats'][$formatName] = $format;
    }

    /**
     * Returns an array of date formats.
     * 
     * @param string $formatName The name of the date format to retrieve, defaults to 'default'.
     * @param string|null $locale The locale for which to retrieve the date format, defaults to default locale.
     *
     * @return array The array of date formats.
     */
    public function getDateFormat(string $formatName = 'default', ?string $locale = null): array {
        $locale = $locale ?? $this->defaultLocale;
        return $this->data[$locale]['dateFormats'][$formatName] ?? [];
    }

    /**
     * Formats a DateTimeInterface object according to the specified format and locale name.
     *
     * @param DateTimeInterface|string|null $date DateTimeInterface object, date string or null for current date to format.
     * @param string $formatName Format name use for date formatting, as defined in the Locale class dateFormats array.
     * @param string|null $locale Locale name to be used for formatting, defaults to the default locale.
     *
     * @return string Formatted date string.
     */
    public function formatDate(DateTimeInterface|string|null $date = null, string $formatName = 'default', ?string $locale = null): string {
        $locale = $locale ?? $this->defaultLocale;
        $format = $this->data[$locale]['dateFormats'][$formatName] ?? 'Y-m-d H:i:s';
        if (!$date) {
            $date = new DateTime();
        }

        if (is_string($date)) {
            try {
                $date = new DateTime($date);
            } catch (Exception $e) {
                throw new InvalidArgumentException('Invalid date: ' . $date);
            }
        }

        return $date->format($format);
    }

    /**
     * Builds the translation data by iterating over the child Locale classes
     * and merging their translation data recursively.
     */
    public function build(): void {
        foreach ($this->locales as $package) {
            $package->build();
            $this->data = array_replace_recursive($this->data, $package->getTranslationData());
        }
    }

    /**
     * Flattens a multidimensional array into a single-dimensional array using dot notation.
     *
     * @param array $array Multidimensional array to be flattened.
     * @return array Flattened array.
     */
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
