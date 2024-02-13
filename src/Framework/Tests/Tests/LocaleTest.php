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
        $locale = new Locale('en', 'us');
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
        
        $locale2 = new Locale('en', 'us');
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
        
        $locale3 = new Locale('en', 'us');
        $translations = [
            'common' => [
                'greeting' => 'Hey there, welcome!',
                'navigation' => [
                    'about' => 'About Our Company',
                ],
            ],
        ];
        $locale3->addTranslations($translations);
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
        $locale3->addTranslations($translations, 'fr');
        
        $locale4 = new Locale('es', 'es');
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
        
        $locale2->addLocale($locale3);
        $locale->addLocale($locale2);
        $locale2->addLocale($locale4);
        
        $locale->build();

        $this->assertEquals('Hey there, welcome!', $locale->get('common.greeting'));
    }
}
