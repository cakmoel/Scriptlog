<?php
/**
 * AppSettingsTest
 *
 * Unit tests for the centralized settings cache layer (app-settings.php):
 *   - app_settings_cache()
 *   - app_settings()
 *   - app_setting()
 *   - reset_app_settings_cache()
 */

use PHPUnit\Framework\TestCase;

class AppSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('app_settings_cache')) {
            require_once __DIR__ . '/../../src/lib/utility/app-settings.php';
        }
        reset_app_settings_cache();
    }

    protected function tearDown(): void
    {
        reset_app_settings_cache();
    }

    public function testAppSettingsCacheReturnsByReference(): void
    {
        $cache = &app_settings_cache();
        $this->assertNull($cache);

        $cache = ['key' => 'value'];
        $cache2 = &app_settings_cache();
        $this->assertSame('value', $cache2['key']);
    }

    public function testResetAppSettingsCacheClearsState(): void
    {
        $cache = &app_settings_cache();
        $cache = ['cached' => true];

        reset_app_settings_cache();

        $cache2 = &app_settings_cache();
        $this->assertNull($cache2);
    }

    public function testAppSettingsReturnsArray(): void
    {
        $result = app_settings();
        $this->assertIsArray($result);
    }

    public function testAppSettingsIsMemoizedPerRequest(): void
    {
        $first = &app_settings_cache();
        $first = ['site_name' => 'Memoized', 'app_url' => 'http://test.com'];

        $result = app_settings();
        $this->assertSame('Memoized', $result['site_name']);
        $this->assertSame('http://test.com', $result['app_url']);
    }

    public function testAppSettingReturnsValueFromCache(): void
    {
        $cache = &app_settings_cache();
        $cache = ['site_name' => 'My Blog', 'app_url' => 'http://example.com'];

        $this->assertSame('My Blog', app_setting('site_name'));
        $this->assertSame('http://example.com', app_setting('app_url'));
    }

    public function testAppSettingReturnsDefaultWhenMissing(): void
    {
        reset_app_settings_cache();

        $this->assertSame('', app_setting('nonexistent_key'));
        $this->assertSame('fallback', app_setting('nonexistent_key', 'fallback'));
    }

    public function testAppSettingReturnsDefaultForEmptyValue(): void
    {
        $cache = &app_settings_cache();
        $cache = ['empty_key' => ''];

        $this->assertSame('default_val', app_setting('empty_key', 'default_val'));
    }

    public function testAppSettingFunctionExists(): void
    {
        $this->assertTrue(function_exists('app_settings'));
        $this->assertTrue(function_exists('app_setting'));
        $this->assertTrue(function_exists('app_settings_cache'));
        $this->assertTrue(function_exists('reset_app_settings_cache'));
    }

    public function testAppSettingsHandlesBothArrayAndObjectRows(): void
    {
        $cache = &app_settings_cache();
        $cache = [
            'site_name' => 'Test Site',
            'app_url' => 'http://test.dev',
            'cache_enabled' => '1',
            'cache_lifetime' => '7200',
        ];

        $result = app_settings();
        $this->assertCount(4, $result);
        $this->assertSame('1', $result['cache_enabled']);
        $this->assertSame('7200', $result['cache_lifetime']);
    }

    public function testAppSettingWithNullDefault(): void
    {
        reset_app_settings_cache();

        $result = app_setting('missing', null);
        $this->assertNull($result);
    }
}
