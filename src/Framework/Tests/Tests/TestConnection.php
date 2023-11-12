<?php

namespace Framework\Tests\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Framework\Configuration\Configuration;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

class TestConnection extends TestCase {
    private static Configuration $configuration;
    private static string $url;
    private static $webDriver;

    public static function setUpBeforeClass(): void {
        self::$configuration = FRAMEWORK->getConfiguration();

        $protocol = 'http';
        if (FRAMEWORK->sslEnabled()) {
            $protocol = 'https';
        }

        self::$url = $protocol . '://' . self::$configuration->getConfig('hostName');

        $host = self::$configuration->getConfig('testing.seleniumUrl') . ':' . self::$configuration->getConfig('testing.seleniumPort');
        $chromeOptions = new ChromeOptions();
        $chromeOptions->addArguments([
            '--ignore-certificate-errors',
            '--disable-web-security',
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $chromeOptions);

        self::$webDriver = RemoteWebDriver::create($host, $capabilities);
    }

    public function testConnection() {
        try {
            self::$webDriver->get(self::$url);
            $this->assertTrue(true, 'Connection to ' . self::$url . ' successful!');
        } catch(Exception $e) {
            $this->fail('Failed to connect to ' . self::$url . '. Error: ' . $e->getMessage());
        }
    }

    public static function tearDownAfterClass(): void {
        self::$webDriver->quit();
    }
}
