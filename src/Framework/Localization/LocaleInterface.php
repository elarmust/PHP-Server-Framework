<?php

namespace Framework\Localization;

interface LocaleInterface {

    /**
     * Adds a child Locale class to the current Locale class.
     *
     * @param LocaleInterface $localeClass Child Locale class to add.
     *
     * @return void
     */
    public function addLocale(LocaleInterface $localeClass): void;

    /**
     * Adds translations to the Locale class.
     *
     * @param array $translations Translations to add.
     * @param string|null $locale Locale name to add translations to, defaults to the default locale name.
     *
     * @return void
     */
    public function addTranslations(array $translations, ?string $locale = null): void;

    /**
     * Retrieves all translation data for the locale.
     *
     * @return array Translation data array.
     */
    public function getTranslationData(): array;

    /**
     * Retrieves the translated string with the given key.
     *
     * @param string $key Translation key.
     * @param array $variables Variables to be replaced in the translated string.
     * @param string|null $locale Locale name to use for translation, defaults to the default locale name.
     *
     * @return string Translated string.
     */
    public function get(string $key, array $variables = [], ?string $locale = null): string;

    /**
     * Returns locale identifier.
     *
     * @return string Local identifier.
     */
    public function getIdentifier(): string;

    /**
     * Sets the default locale name.
     *
     * @param string $locale Default locale name.
     * @return void
     */
    public function setDefaultLocale(string $locale): void;

    /**
     * Retrieves the default locale name.
     *
     * @return string Default locale name.
     */
    public function getLocale(): string;

    /**
     * Returns the child Locale classes associated with the Locale class.
     *
     * @return array Child Locale classes.
     */
    public function getLocales(): array ;

    /**
     * Removes a Locale class from the list of available Locale classes.
     *
     * @param string $identifier Locale class by identifier to remove.
     *
     * @return void
     */
    public function removeLocale(string $identifier): void;

    /**
     * Returns an array of child Locale class identifiers.
     *
     * @return array An array of child Locale class identifiers.
     */
    public function getLocaleIdentifiers(): array;

    /**
     * Builds the translation data by iterating over the child Locale classes
     * and merging their translation data recursively.
     */
    public function build(): void;
}
