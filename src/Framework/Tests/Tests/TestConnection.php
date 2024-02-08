<?php

namespace Framework\Tests\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use Framework\Http\Session\Session;
use Framework\Configuration\Configuration;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

class TestConnection extends TestCase {
    private static Configuration $configuration;
    private static string $url;
    private static $webDriver;
    private static Session $session;

    public static function setUpBeforeClass(): void {
        self::$session = FRAMEWORK->getClassContainer()->get(Session::class);
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
        } catch (Exception $e) {
            $this->fail('Failed to connect to ' . self::$url . '. Error: ' . $e->getMessage());
        }
    }

    public function testSession() {
        try {
            $cookieName = self::$session->getCookieName();

            // Ensure session cookie is set.
            self::$webDriver->get(self::$url);
            $cookie = $this->getCookie($cookieName, self::$webDriver->manage()->getCookies());
            $this->assertNotFalse($cookie);

            // Ensure session cookie has not changed with a new request.
            self::$webDriver->get(self::$url);
            $cookie2 = $this->getCookie($cookieName, self::$webDriver->manage()->getCookies());
            $this->assertNotFalse($cookie);
            $this->assertEquals($cookie->getValue(), $cookie2->getValue());
        } catch (Exception $e) {
            $this->fail('Failed to test session. Error: ' . $e->getMessage());
        }
    }

    public static function tearDownAfterClass(): void {
        self::$webDriver->quit();
    }

    private function getCookie($cookieName, $cookies): bool|object  {
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $cookieName) {
                return $cookie;
            }
        }

        return false;
    }
}
