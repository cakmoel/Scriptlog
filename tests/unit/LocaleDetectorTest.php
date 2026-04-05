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
        
        if (isset($_SESSION['scriptlog_locale'])) {
            unset($_SESSION['scriptlog_locale']);
        }
        
        $this->detector = new LocaleDetector([
            'default' => 'en',
            'available' => ['en', 'es', 'fr'],
            'auto_detect' => '1'
        ]);
    }

    protected function tearDown(): void
    {
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
        $this->assertTrue($this->detector->isValidLocale('en'));
        $this->assertTrue($this->detector->isValidLocale('es'));
        $this->assertTrue($this->detector->isValidLocale('fr'));
    }

    public function testIsValidLocaleWithInvalidLocale()
    {
        $this->assertFalse($this->detector->isValidLocale('de'));
        $this->assertFalse($this->detector->isValidLocale('xx'));
        $this->assertFalse($this->detector->isValidLocale(''));
    }

    public function testGetAvailableLocales()
    {
        $locales = ['en', 'es', 'fr'];
        $this->assertEquals($locales, $this->detector->getAvailableLocales());
    }

    public function testSetAvailableLocales()
    {
        $locales = ['de', 'it', 'pt'];
        $this->detector->setAvailableLocales($locales);
        
        $this->assertEquals($locales, $this->detector->getAvailableLocales());
        $this->assertTrue($this->detector->isValidLocale('de'));
        $this->assertTrue($this->detector->isValidLocale('it'));
        $this->assertTrue($this->detector->isValidLocale('pt'));
    }

    public function testGetDefaultLocale()
    {
        $this->assertEquals('en', $this->detector->getDefaultLocale());
    }

    public function testSetDefaultLocale()
    {
        $this->detector->setDefaultLocale('es');
        $this->assertEquals('es', $this->detector->getDefaultLocale());
    }

    public function testSetLocaleWithValidLocale()
    {
        if (headers_sent()) {
            $this->markTestSkipped('Cannot test setLocale with cookies in PHPUnit context (headers already sent)');
        }
        
        $result = $this->detector->setLocale('es');
        
        $this->assertTrue($result);
    }

    public function testSetLocaleWithInvalidLocale()
    {
        $result = $this->detector->setLocale('xx');
        
        $this->assertFalse($result);
    }

    public function testIsAutoDetectEnabled()
    {
        $this->assertTrue($this->detector->isAutoDetectEnabled());
    }

    public function testSetAutoDetect()
    {
        $this->detector->setAutoDetect(false);
        $this->assertFalse($this->detector->isAutoDetectEnabled());
        
        $this->detector->setAutoDetect(true);
        $this->assertTrue($this->detector->isAutoDetectEnabled());
    }

    public function testConstructorWithEmptySettingsUsesDefaults()
    {
        $detector = new LocaleDetector();
        
        $this->assertEquals('en', $detector->getDefaultLocale());
        $this->assertContains('en', $detector->getAvailableLocales());
    }

    public function testConstructorWithPartialSettings()
    {
        $detector = new LocaleDetector([
            'default' => 'es'
        ]);
        
        $this->assertEquals('es', $detector->getDefaultLocale());
    }

    public function testExtractFromUrlLogic()
    {
        if (headers_sent()) {
            $this->markTestSkipped('Cannot test detect with URL locale in PHPUnit context (headers already sent)');
        }
        
        $detector = new LocaleDetector([
            'default' => 'en',
            'available' => ['en', 'es', 'fr'],
            'auto_detect' => '0'
        ]);
        
        $_SERVER['REQUEST_URI'] = '/es/blog/my-post';
        $locale = $detector->detect();
        $this->assertEquals('es', $locale);
    }

    public function testExtractFromUrlWithInvalidLocale()
    {
        $detector = new LocaleDetector([
            'default' => 'en',
            'available' => ['en', 'es', 'fr'],
            'auto_detect' => '0'
        ]);
        
        $_SERVER['REQUEST_URI'] = '/xx/blog/my-post';
        $locale = $detector->detect();
        
        $this->assertEquals('en', $locale);
    }

    public function testDetectFromBrowserAcceptLanguage()
    {
        $detector = new LocaleDetector([
            'default' => 'en',
            'available' => ['en', 'es', 'fr'],
            'auto_detect' => '1'
        ]);
        
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'es-ES,es;q=0.9,en;q=0.8';
        $_SERVER['REQUEST_URI'] = '/';
        
        $locale = $detector->detect();
        
        $this->assertEquals('es', $locale);
    }

    public function testDetectReturnsDefaultWhenNoMatch()
    {
        $detector = new LocaleDetector([
            'default' => 'en',
            'available' => ['en', 'es', 'fr'],
            'auto_detect' => '1'
        ]);
        
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE,de;q=0.9';
        $_SERVER['REQUEST_URI'] = '/';
        
        $locale = $detector->detect();
        
        $this->assertEquals('en', $locale);
    }

    public function testDetectWithFullLocaleFormat()
    {
        $detector = new LocaleDetector([
            'default' => 'en',
            'available' => ['en', 'es'],
            'auto_detect' => '0'
        ]);
        
        $_SERVER['REQUEST_URI'] = '/en-US/blog/my-post';
        $locale = $detector->detect();
        
        $this->assertEquals('en', $locale);
    }
}
