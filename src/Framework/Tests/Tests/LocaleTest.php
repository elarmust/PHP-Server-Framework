<?php

namespace Framework\Tests\Tests;

use PHPUnit\Framework\TestCase;
use Framework\Localization\Locale;

class LocaleTest extends TestCase {

    public function testAddLocale() {
        $locale = new Locale('locale1', 'en_US');
        $childLocale = new Locale('locale2', 'en_GB');

        $locale->addLocale($childLocale);

        $this->assertEquals($childLocale, $locale->getLocales()['locale2'] ?? '');
    }

    public function testAddTranslations() {
        $locale = new Locale('locale1', 'en_US');
        $translations = [
            'hello' => 'Hello',
            'world' => 'World'
        ];

        $locale->addTranslations($translations);

        $this->assertEquals($translations, $locale->getTranslationData()['en_US']['translations']);
    }

    public function testGetTranslation() {
        $locale = new Locale('locale1', 'en_US');
        $translations = [
            'hello' => 'Hello',
            'world' => 'World'
        ];

        $locale->addTranslations($translations);

        $this->assertEquals('Hello', $locale->get('hello'));
        $this->assertEquals('World', $locale->get('world'));
    }

    public function testGetTranslationWithVariables() {
        $locale = new Locale('locale1', 'en_US');
        $translations = [
            'variables1' => 'Test variable 1: {var1}, Test variable 2: {var2}',
            'variables2' => 'Test variable 3: {var3}, Test variable 4: {var4}'
        ];

        $locale->addTranslations($translations);

        $this->assertEquals('Test variable 1: variable 1, Test variable 2: variable 2', $locale->get('variables1', ['var1' => 'variable 1', 'var2' => 'variable 2']));
        $this->assertEquals('Test variable 3: variable 3, Test variable 4: variable 4', $locale->get('variables2', ['var3' => 'variable 3', 'var4' => 'variable 4']));
    }

    public function testGetTranslationWithLocale() {
        $locale = new Locale('locale1', 'en_US');
        $translations1 = [
            'test' => 'test1'
        ];

        $translations2 = [
            'test' => 'test2'
        ];

        $locale->addTranslations($translations1);
        $locale->addTranslations($translations2, 'en_GB');

        $this->assertEquals('test1', $locale->get('test'));
        $this->assertEquals('test2', $locale->get('test', [], 'en_GB'));
    }

    public function testGetNestedTranslation() {
        $locale = new Locale('locale1', 'en_US');
        $translations = [
            'test' => [
                'test1' => 'test1',
                'test2' => 'test2'
            ]
        ];

        $locale->addTranslations($translations);

        $this->assertEquals('test1', $locale->get('test.test1'));
        $this->assertEquals('test2', $locale->get('test.test2'));
    }

    public function testGetDeepNestedTranslationsWithMultipleChildLocales() {
    }
}
