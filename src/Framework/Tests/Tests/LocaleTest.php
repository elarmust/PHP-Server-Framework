<?php

namespace Framework\Tests\Tests;

use DateTime;
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

    public function testNumberFormat() {
        $locale = new Locale('locale1', 'en_US');
        $locale->setNumberFormat(2, '.', ',');

        $this->assertEquals('1,000.00', $locale->numberFormat(1000));
        $this->assertEquals('1,000.00', $locale->numberFormat(1000.00));

        $locale->setNumberFormat(0, '.', ',');
        $this->assertEquals('1,000', $locale->numberFormat(1000));
        $this->assertEquals('1,000', $locale->numberFormat(1000.00));

        // Test a named format
        $locale->setNumberFormat(4, ',', '.', 'test');
        $this->assertEquals('1.000,0000', $locale->numberFormat(1000, 'test'));

        // Test a named format with a different locale
        $locale->setNumberFormat(3, ',', '.', 'test', 'de_DE');
        $this->assertEquals('1.000,000', $locale->numberFormat(1000, 'test', 'de_DE'));
    }

    public function testDateFormat() {
        $locale = new Locale('locale1', 'en_US');
        $locale->setDateFormat('Y-m-d');

        // Test a date string
        $this->assertEquals('2020-01-01', $locale->dateFormat('2020-01-01'));
        $this->assertEquals('2020-01-01', $locale->dateFormat('2020-01-01 01:00:00'));

        // Test a DateTime object
        $date = new DateTime('2020-01-01');
        $this->assertEquals('2020-01-01', $locale->dateFormat($date));

        // Test date and time
        $locale->setDateFormat('Y-m-d H:i:s');
        $this->assertEquals('2020-01-01 15:30:00', $locale->dateFormat('2020-01-01 15:30:00'));
        $this->assertEquals('2020-01-01 00:00:00', $locale->dateFormat('2020-01-01'));

        // Test time
        $locale->setDateFormat('H:i:s');
        $this->assertEquals('15:30:00', $locale->dateFormat('2020-01-01 15:30:00'));

        // Test a named format
        $locale->setDateFormat('d/m/Y', 'test');
        $this->assertEquals('01/01/2020', $locale->dateFormat('2020-01-01', 'test'));

        // Test a named format with a different locale
        $locale->setDateFormat('d/m/Y', 'test', 'de_DE');
        $this->assertEquals('01/01/2020', $locale->dateFormat('2020-01-01', 'test', 'de_DE'));

        // Test a DateTime object with a named format
        $locale->setDateFormat('d/m/Y', 'test');
        $date = new DateTime('01/01/2020');
        $this->assertEquals('01/01/2020', $locale->dateFormat($date, 'test'));
    }

    public function testGetDeepNestedTranslationsWithMultipleChildLocales() {
        $locale = new Locale('locale', 'en_US');
        $translations = [
            'common' => [
                'greeting' => 'Hello, welcome to our application!',
                'navigation' => [
                    'home' => 'Home',
                    'about' => 'About Us',
                    'contact' => 'Contact Us',
                ],
                'settings' => [
                    'account' => [
                        'general' => 'General Settings',
                        'security' => 'Security Settings',
                        'privacy' => 'Privacy Settings',
                    ],
                ],
            ],
            'errors' => [
                '404' => 'Page not found.',
                '500' => 'Internal server error occurred.',
            ],
        ];
        $locale->addTranslations($translations);
        $locale->setNumberFormat(2, '.', ',');
        $locale->setNumberFormat(4, ',', '.', 'test');
        $locale->setDateFormat('Y-m-d');
        $locale->setDateFormat('Y-m-d H:i:s', 'datetime');
        
        $locale2 = new Locale('locale2', 'en_US');
        $translations = [
            'common' => [
                'greeting' => 'Hi, welcome to our app!',
                'navigation' => [
                    'home' => 'Home',
                    'about' => 'About',
                    'contact' => 'Contact',
                ],
            ],
        ];
        $locale2->addTranslations($translations);
        
        $locale3 = new Locale('locale3', 'en_US');
        $translations = [
            'common' => [
                'greeting' => 'Hey there, welcome!',
                'navigation' => [
                    'about' => 'About Our Company',
                ],
            ],
        ];
        $locale3->addTranslations($translations);
        // Add a number format
        $locale3->setNumberFormat(0, '.', ',');
        // Add a date format
        $locale3->setDateFormat('m/d/Y');
        // Add a named date format
        $locale3->setDateFormat('m/d/Y H:i:s', 'datetime');

        $translations = [
            'common' => [
                'greeting' => 'Bonjour, bienvenue sur notre application!',
                'navigation' => [
                    'home' => 'Accueil',
                    'about' => 'À propos de nous',
                    'contact' => 'Contactez-nous',
                ],
                'settings' => [
                    'account' => [
                        'general' => 'Paramètres généraux',
                        'security' => 'Paramètres de sécurité',
                        'privacy' => 'Paramètres de confidentialité',
                    ],
                ],
            ],
            'errors' => [
                '404' => 'Page non trouvée.',
                '500' => 'Une erreur interne du serveur est survenue.',
            ],
        ];
        $locale3->addTranslations($translations, 'fr_FR');
        
        $locale4 = new Locale('locale4', 'es_ES');
        $translations = [
            'common' => [
                'greeting' => '¡Hola, bienvenido a nuestra aplicación!',
                'navigation' => [
                    'home' => 'Inicio',
                    'about' => 'Acerca de Nosotros',
                    'contact' => 'Contáctanos',
                ],
                'settings' => [
                    'account' => [
                        'general' => 'Configuración general',
                        'security' => 'Configuración de seguridad',
                        'privacy' => 'Configuración de privacidad',
                    ],
                ],
            ],
            'errors' => [
                '404' => 'Página no encontrada.',
                '500' => 'Se ha producido un error interno del servidor.',
            ],
        ];
        $locale4->addTranslations($translations);
        // Add a number format
        $locale4->setNumberFormat(0, '.', ',');
        // Add a named number format
        $locale4->setNumberFormat(3, ',', '.', 'test');
        // Add a date format
        $locale4->setDateFormat('d/m/Y');
        // Add a named date format
        $locale4->setDateFormat('d/m/Y H:i:s', 'datetime');
        
        $locale2->addLocale($locale3);
        $locale->addLocale($locale2);
        $locale2->addLocale($locale4);
        
        $locale->build();

        $this->assertEquals('Hey there, welcome!', $locale->get('common.greeting'));
        $this->assertEquals('About Our Company', $locale->get('common.navigation.about'));
        $this->assertEquals('Contact', $locale->get('common.navigation.contact'));
        $this->assertEquals('General Settings', $locale->get('common.settings.account.general'));
        $this->assertEquals('Security Settings', $locale->get('common.settings.account.security'));
        $this->assertEquals('Privacy Settings', $locale->get('common.settings.account.privacy'));
        $this->assertEquals('Page non trouvée.', $locale->get('errors.404', [], 'fr_FR'));
        $this->assertEquals('Se ha producido un error interno del servidor.', $locale->get('errors.500', [], 'es_ES'));

        // Test number and date formats.
        $this->assertEquals('1,000', $locale->numberFormat(1000));
        $this->assertEquals('1.000,0000', $locale->numberFormat(1000, 'test'));
        $this->assertEquals('01/01/2020', $locale->dateFormat('2020-01-01', locale: 'es_ES'));
        $this->assertEquals('01/01/2020 15:30:00', $locale->dateFormat('2020-01-01 15:30:00', 'datetime', 'es_ES'));

        // Set the locale to fr_FR
        $locale->setDefaultLocale('fr_FR');
        $this->assertEquals('Bonjour, bienvenue sur notre application!', $locale->get('common.greeting'));
        $this->assertEquals('À propos de nous', $locale->get('common.navigation.about'));
        $this->assertEquals('Contactez-nous', $locale->get('common.navigation.contact'));
        $this->assertEquals('Paramètres généraux', $locale->get('common.settings.account.general'));
        $this->assertEquals('Paramètres de sécurité', $locale->get('common.settings.account.security'));
        $this->assertEquals('Paramètres de confidentialité', $locale->get('common.settings.account.privacy'));
        $this->assertEquals('Page non trouvée.', $locale->get('errors.404'));
        $this->assertEquals('Une erreur interne du serveur est survenue.', $locale->get('errors.500'));

        // Set the locale to es_ES
        $locale->setDefaultLocale('es_ES'); 
        $this->assertEquals('¡Hola, bienvenido a nuestra aplicación!', $locale->get('common.greeting'));
        $this->assertEquals('Acerca de Nosotros', $locale->get('common.navigation.about'));
        $this->assertEquals('Contáctanos', $locale->get('common.navigation.contact'));
        $this->assertEquals('Configuración general', $locale->get('common.settings.account.general'));
        $this->assertEquals('Configuración de seguridad', $locale->get('common.settings.account.security'));
        $this->assertEquals('Configuración de privacidad', $locale->get('common.settings.account.privacy'));
        $this->assertEquals('Página no encontrada.', $locale->get('errors.404'));
        $this->assertEquals('Se ha producido un error interno del servidor.', $locale->get('errors.500'));

        // Test number and date formats.
        $this->assertEquals('1,000', $locale->numberFormat(1000));
        $this->assertEquals('1.000,000', $locale->numberFormat(1000, 'test'));
        $this->assertEquals('01/01/2020', $locale->dateFormat('2020-01-01'));
        $this->assertEquals('01/01/2020 15:30:00', $locale->dateFormat('2020-01-01 15:30:00', 'datetime'));
    }
}
