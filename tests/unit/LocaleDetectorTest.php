<?php
/**
 * LocaleDetector Unit Test
 * 
 * Tests for the LocaleDetector class
 * 
 * @version 1.0
 * @since 1.0
 */

use PHPUnit\Framework\TestCase;

class LocaleDetectorTest extends TestCase
{
    private $detector;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock for ConfigurationDao
        if (!class_exists('ConfigurationDao')) {
            $this->markTestSkipped('ConfigurationDao class not found');
        }
        
        $this->detector = new LocaleDetector();
    }

    protected function tearDown(): void
    {
        // Clean up session/cookie
        if (isset($_SESSION['scriptlog_locale'])) {
            unset($_SESSION['scriptlog_locale']);
        }
        if (isset($_COOKIE['scriptlog_locale'])) {
            setcookie('scriptlog_locale', '', time() - 3600, '/');
        }
        
        parent::tearDown();
    }

    public function testIsValidLocaleWithValidLocale()
    {
        $this->detector->setAvailableLocales(['en', 'es', 'fr']);
        
        $this->assertTrue($this->detector->isValidLocale('en'));
        $this->assertTrue($this->detector->isValidLocale('es'));
        $this->assertTrue($this->detector->isValidLocale('fr'));
    }

    public function testIsValidLocaleWithInvalidLocale()
    {
        $this->detector->setAvailableLocales(['en', 'es', 'fr']);
        
        $this->assertFalse($this->detector->isValidLocale('de'));
        $this->assertFalse($this->detector->isValidLocale('xx'));
        $this->assertFalse($this->detector->isValidLocale(''));
    }

    public function testGetAvailableLocales()
    {
        $locales = ['en', 'es', 'fr'];
        $this->detector->setAvailableLocales($locales);
        
        $this->assertEquals($locales, $this->detector->getAvailableLocales());
    }

    public function testSetAvailableLocales()
    {
        $locales = ['de', 'it', 'pt'];
        $this->detector->setAvailableLocales($locales);
        
        $this->assertEquals($locales, $this->detector->getAvailableLocales());
    }

    public function testGetDefaultLocale()
    {
        $this->assertEquals('en', $this->detector->getDefaultLocale());
    }

    public function testSetLocaleWithValidLocale()
    {
        $this->detector->setAvailableLocales(['en', 'es']);
        
        // Start session for testing
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $result = $this->detector->setLocale('es');
        
        $this->assertTrue($result);
        $this->assertEquals('es', $_SESSION['scriptlog_locale'] ?? null);
    }

    public function testSetLocaleWithInvalidLocale()
    {
        $this->detector->setAvailableLocales(['en', 'es']);
        
        $result = $this->detector->setLocale('xx');
        
        $this->assertFalse($result);
    }

    public function testIsAutoDetectEnabled()
    {
        $this->assertTrue($this->detector->isAutoDetectEnabled());
    }

    public function testExtractFromUrlWithValidLocale()
    {
        $_SERVER['REQUEST_URI'] = '/es/blog/my-post';
        
        $detector = new LocaleDetector();
        $detector->setAvailableLocales(['en', 'es', 'fr']);
        
        // Test the detect method which uses extractFromUrl internally
        $locale = $detector->detect();
        
        $this->assertEquals('es', $locale);
    }

    public function testExtractFromUrlWithInvalidLocale()
    {
        $_SERVER['REQUEST_URI'] = '/xx/blog/my-post';
        
        $detector = new LocaleDetector();
        $detector->setAvailableLocales(['en', 'es', 'fr']);
        
        // Should return default locale since xx is not valid
        $locale = $detector->detect();
        
        $this->assertEquals('en', $locale);
    }

    public function testDetectFromSession()
    {
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['scriptlog_locale'] = 'fr';
        unset($_GET['locale']); // Clear any GET params
        
        $detector = new LocaleDetector();
        $detector->setAvailableLocales(['en', 'es', 'fr']);
        
        $locale = $detector->detect();
        
        $this->assertEquals('fr', $locale);
    }

    public function testDetectFromCookie()
    {
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear session
        unset($_SESSION['scriptlog_locale']);
        
        // Set cookie
        $_COOKIE['scriptlog_locale'] = 'de';
        
        $detector = new LocaleDetector();
        $detector->setAvailableLocales(['en', 'es', 'de']);
        
        $locale = $detector->detect();
        
        $this->assertEquals('de', $locale);
    }

    public function testDetectFromBrowserAcceptLanguage()
    {
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear session and cookie
        unset($_SESSION['scriptlog_locale']);
        unset($_COOKIE['scriptlog_locale']);
        
        // Set browser accept-language header
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es-ES,es;q=0.9,en;q=0.8';
        
        $detector = new LocaleDetector();
        $detector->setAvailableLocales(['en', 'es', 'fr']);
        
        $locale = $detector->detect();
        
        $this->assertEquals('es', $locale);
    }

    public function testDetectReturnsDefaultWhenNoMatch()
    {
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Clear session, cookie, and unset browser header
        unset($_SESSION['scriptlog_locale']);
        unset($_COOKIE['scriptlog_locale']);
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $_SERVER['REQUEST_URI'] = '/';
        
        $detector = new LocaleDetector();
        
        $locale = $detector->detect();
        
        $this->assertEquals('en', $locale);
    }

    public function testDetectWithFullLocaleFormat()
    {
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION['scriptlog_locale']);
        $_SERVER['REQUEST_URI'] = '/en-US/blog/my-post';
        
        $detector = new LocaleDetector();
        $detector->setAvailableLocales(['en', 'es']);
        
        $locale = $detector->detect();
        
        $this->assertEquals('en', $locale);
    }
}
