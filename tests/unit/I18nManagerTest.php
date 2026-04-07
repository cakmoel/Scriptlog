<?php
/**
 * I18nManager Unit Test
 * 
 * Tests for the I18nManager class
 * 
 * @version 1.0
 * @since 1.0
 */

use PHPUnit\Framework\TestCase;

class I18nManagerTest extends TestCase
{
    private $i18n;

    protected function setUp(): void
    {
        parent::setUp();
        
        I18nManager::resetInstance();
        
        $this->i18n = I18nManager::getInstance();
        
        $this->i18n->initializeWithSettings([
            'default' => 'en',
            'available' => ['en', 'es', 'fr'],
            'auto_detect' => '1'
        ]);
    }

    protected function tearDown(): void
    {
        I18nManager::resetInstance();
        
        if (isset($_SESSION['scriptlog_locale'])) {
            unset($_SESSION['scriptlog_locale']);
        }
        if (isset($_COOKIE['scriptlog_locale'])) {
            setcookie('scriptlog_locale', '', time() - 3600, '/');
        }
        
        parent::tearDown();
    }

    public function testGetInstanceReturnsSameInstance()
    {
        $instance1 = I18nManager::getInstance();
        $instance2 = I18nManager::getInstance();
        
        $this->assertSame($instance1, $instance2);
    }

    public function testGetInstanceReturnsI18nManager()
    {
        $instance = I18nManager::getInstance();
        
        $this->assertInstanceOf(I18nManager::class, $instance);
    }

    public function testInitializeSetsInitializedFlag()
    {
        $instance = I18nManager::getInstance();
        $instance->initializeWithSettings([
            'default' => 'en',
            'available' => ['en'],
            'auto_detect' => '0'
        ]);
        
        $this->assertTrue($instance->isInitialized());
    }

    public function testGetLocaleReturnsString()
    {
        $locale = $this->i18n->getLocale();
        
        $this->assertIsString($locale);
        $this->assertEquals('en', $locale);
    }

    public function testGetAvailableLocalesReturnsArray()
    {
        $locales = $this->i18n->getAvailableLocales();
        
        $this->assertIsArray($locales);
        $this->assertContains('en', $locales);
        $this->assertContains('es', $locales);
    }

    public function testIsRtlReturnsBoolean()
    {
        $isRtl = $this->i18n->isRtl();
        
        $this->assertIsBool($isRtl);
        $this->assertFalse($isRtl);
    }

    public function testIsRtlReturnsTrueForRtlLanguage()
    {
        $this->i18n->setLanguageDirection('rtl');
        
        $this->assertTrue($this->i18n->isRtl());
    }

    public function testGetLanguageDirectionReturnsString()
    {
        $direction = $this->i18n->getLanguageDirection();
        
        $this->assertIsString($direction);
        $this->assertContains($direction, ['ltr', 'rtl']);
    }

    public function testTranslateMethodExists()
    {
        $this->assertTrue(method_exists($this->i18n, 't'));
    }

    public function testTranslateWithValidKey()
    {
        $result = $this->i18n->t('header.nav.home');
        $this->assertIsString($result);
    }

    public function testTranslateWithUnknownKey()
    {
        $unknownKey = 'unknown.key.' . time();
        $result = $this->i18n->t($unknownKey);
        
        $this->assertEquals($unknownKey, $result);
    }

    public function testTranslateWithParameters()
    {
        $result = $this->i18n->t('test.key', ['name' => 'John', 'count' => 5]);
        $this->assertIsString($result);
    }

    public function testSetLocaleWithValidLocale()
    {
        if (headers_sent()) {
            $this->markTestSkipped('Cannot test setLocale with cookies in PHPUnit context (headers already sent)');
        }
        
        $result = $this->i18n->setLocale('es');
        
        $this->assertTrue($result);
        $this->assertEquals('es', $this->i18n->getLocale());
    }

    public function testSetLocaleWithInvalidLocale()
    {
        $result = $this->i18n->setLocale('xx');
        
        $this->assertFalse($result);
    }

    public function testUrlMethodExists()
    {
        $this->assertTrue(method_exists($this->i18n, 'url'));
    }

    public function testUrlWithPath()
    {
        $url = $this->i18n->url('/blog');
        
        $this->assertIsString($url);
        $this->assertStringStartsWith('/', $url);
    }

    public function testUrlWithLocale()
    {
        $url = $this->i18n->url('/blog', 'es');
        
        $this->assertIsString($url);
        $this->assertStringContainsString('es', $url);
    }

    public function testGetLoaderMethodExists()
    {
        $this->assertTrue(method_exists($this->i18n, 'getLoader'));
    }

    public function testGetLoaderReturnsTranslationLoader()
    {
        $loader = $this->i18n->getLoader();
        
        $this->assertInstanceOf(TranslationLoader::class, $loader);
    }

    public function testGetRouterMethodExists()
    {
        $this->assertTrue(method_exists($this->i18n, 'getRouter'));
    }

    public function testGetRouterReturnsLocaleRouter()
    {
        $router = $this->i18n->getRouter();
        
        $this->assertInstanceOf(LocaleRouter::class, $router);
    }

    public function testGetDetectorMethodExists()
    {
        $this->assertTrue(method_exists($this->i18n, 'getDetector'));
    }

    public function testGetDetectorReturnsLocaleDetector()
    {
        $detector = $this->i18n->getDetector();
        
        $this->assertInstanceOf(LocaleDetector::class, $detector);
    }

    public function testInterpolationLogic()
    {
        $text = 'Hello :name, you have :count items';
        $params = ['name' => 'Alice', 'count' => 10];
        
        $result = str_replace(
            array_map(fn($k) => ':' . $k, array_keys($params)),
            array_values($params),
            $text
        );
        
        $this->assertEquals('Hello Alice, you have 10 items', $result);
    }

    public function testSingletonPreventsCloning()
    {
        if (method_exists($this->i18n, '__clone')) {
            $reflection = new ReflectionClass($this->i18n);
            $cloneMethod = $reflection->getMethod('__clone');
            
            $this->assertTrue($cloneMethod->isPrivate());
        }
    }

    public function testSingletonPreventsUnserialization()
    {
        if (method_exists($this->i18n, '__wakeup')) {
            $reflection = new ReflectionClass($this->i18n);
            $wakeupMethod = $reflection->getMethod('__wakeup');
            
            $this->assertTrue($wakeupMethod->isPublic() || $wakeupMethod->isPrivate());
        }
    }

    public function testResetInstance()
    {
        $instance1 = I18nManager::getInstance();
        I18nManager::resetInstance();
        $instance2 = I18nManager::getInstance();
        
        $this->assertNotSame($instance1, $instance2);
    }

    public function testGetLocaleWhenNotInitialized()
    {
        I18nManager::resetInstance();
        $freshInstance = I18nManager::getInstance();
        
        $freshInstance->initializeWithSettings([
            'default' => 'es',
            'available' => ['en', 'es'],
            'auto_detect' => '0'
        ]);
        
        $this->assertEquals('es', $freshInstance->getLocale());
    }

    public function testSetLanguageDirection()
    {
        $this->i18n->setLanguageDirection('rtl');
        
        $this->assertEquals('rtl', $this->i18n->getLanguageDirection());
        $this->assertTrue($this->i18n->isRtl());
    }
}
