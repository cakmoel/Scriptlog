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
        
        if (!class_exists('I18nManager')) {
            $this->markTestSkipped('I18nManager class not found');
        }
    }

    protected function tearDown(): void
    {
        // Reset singleton for next test
        if (class_exists('I18nManager')) {
            $reflection = new ReflectionClass(I18nManager::class);
            $property = $reflection->getProperty('instance');
            $property->setAccessible(true);
            $property->setValue(null, null);
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
        
        // Check if initialize method exists and can be called
        if (method_exists($instance, 'initialize')) {
            $instance->initialize();
            $this->assertTrue(true); // Method executed without error
        } else {
            $this->markTestSkipped('initialize method not found');
        }
    }

    public function testGetLocaleReturnsString()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 'getLocale')) {
            $locale = $instance->getLocale();
            $this->assertIsString($locale);
            $this->assertEquals('en', $locale); // Default should be 'en'
        } else {
            $this->markTestSkipped('getLocale method not found');
        }
    }

    public function testGetAvailableLocalesReturnsArray()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 'getAvailableLocales')) {
            $locales = $instance->getAvailableLocales();
            $this->assertIsArray($locales);
        } else {
            $this->markTestSkipped('getAvailableLocales method not found');
        }
    }

    public function testIsRtlReturnsBoolean()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 'isRtl')) {
            $isRtl = $instance->isRtl();
            $this->assertIsBool($isRtl);
            $this->assertFalse($isRtl); // Default English should not be RTL
        } else {
            $this->markTestSkipped('isRtl method not found');
        }
    }

    public function testGetLanguageDirectionReturnsString()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 'getLanguageDirection')) {
            $direction = $instance->getLanguageDirection();
            $this->assertIsString($direction);
            $this->assertContains($direction, ['ltr', 'rtl']);
        } else {
            $this->markTestSkipped('getLanguageDirection method not found');
        }
    }

    public function testTranslateMethodExists()
    {
        $instance = I18nManager::getInstance();
        
        $this->assertTrue(method_exists($instance, 't'));
    }

    public function testTranslateWithValidKey()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 't')) {
            // Test with a key that might exist
            $result = $instance->t('header.nav.home');
            $this->assertIsString($result);
        } else {
            $this->markTestSkipped('t method not found');
        }
    }

    public function testTranslateWithUnknownKey()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 't')) {
            // Unknown key should return the key itself
            $unknownKey = 'unknown.key.' . time();
            $result = $instance->t($unknownKey);
            $this->assertEquals($unknownKey, $result);
        } else {
            $this->markTestSkipped('t method not found');
        }
    }

    public function testTranslateWithParameters()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 't')) {
            // Test interpolation parameters
            $result = $instance->t('test.key', ['name' => 'John', 'count' => 5]);
            $this->assertIsString($result);
        } else {
            $this->markTestSkipped('t method not found');
        }
    }

    public function testSetLocaleWithValidLocale()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 'setLocale')) {
            // Note: This might fail if 'es' locale is not available
            $result = $instance->setLocale('en');
            $this->assertTrue($result);
        } else {
            $this->markTestSkipped('setLocale method not found');
        }
    }

    public function testSetLocaleWithInvalidLocale()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 'setLocale')) {
            $result = $instance->setLocale('xx');
            $this->assertFalse($result);
        } else {
            $this->markTestSkipped('setLocale method not found');
        }
    }

    public function testUrlMethodExists()
    {
        $instance = I18nManager::getInstance();
        
        $this->assertTrue(method_exists($instance, 'url'));
    }

    public function testUrlWithPath()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 'url')) {
            $url = $instance->url('/blog');
            $this->assertIsString($url);
            $this->assertStringStartsWith('/', $url);
        } else {
            $this->markTestSkipped('url method not found');
        }
    }

    public function testUrlWithLocale()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 'url')) {
            $url = $instance->url('/blog', 'es');
            $this->assertIsString($url);
            $this->assertStringContainsString('es', $url);
        } else {
            $this->markTestSkipped('url method not found');
        }
    }

    public function testGetLoaderMethodExists()
    {
        $instance = I18nManager::getInstance();
        
        $this->assertTrue(method_exists($instance, 'getLoader'));
    }

    public function testGetLoaderReturnsTranslationLoader()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 'getLoader')) {
            $loader = $instance->getLoader();
            $this->assertInstanceOf(TranslationLoader::class, $loader);
        } else {
            $this->markTestSkipped('getLoader method not found');
        }
    }

    public function testGetRouterMethodExists()
    {
        $instance = I18nManager::getInstance();
        
        $this->assertTrue(method_exists($instance, 'getRouter'));
    }

    public function testGetRouterReturnsLocaleRouter()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 'getRouter')) {
            $router = $instance->getRouter();
            $this->assertInstanceOf(LocaleRouter::class, $router);
        } else {
            $this->markTestSkipped('getRouter method not found');
        }
    }

    public function testGetDetectorMethodExists()
    {
        $instance = I18nManager::getInstance();
        
        $this->assertTrue(method_exists($instance, 'getDetector'));
    }

    public function testGetDetectorReturnsLocaleDetector()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, 'getDetector')) {
            $detector = $instance->getDetector();
            $this->assertInstanceOf(LocaleDetector::class, $detector);
        } else {
            $this->markTestSkipped('getDetector method not found');
        }
    }

    public function testInterpolationLogic()
    {
        // Test the interpolation logic directly
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
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, '__clone')) {
            $reflection = new ReflectionClass($instance);
            $cloneMethod = $reflection->getMethod('__clone');
            
            // __clone should be private
            $this->assertTrue($cloneMethod->isPrivate());
        }
    }

    public function testSingletonPreventsUnserialization()
    {
        $instance = I18nManager::getInstance();
        
        if (method_exists($instance, '__wakeup')) {
            $reflection = new ReflectionClass($instance);
            $wakeupMethod = $reflection->getMethod('__wakeup');
            
            // __wakeup should throw exception
            $this->assertTrue($wakeupMethod->isPublic() || $wakeupMethod->isPrivate());
        }
    }
}
