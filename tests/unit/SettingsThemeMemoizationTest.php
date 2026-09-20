<?php
/**
 * SettingsThemeMemoizationTest
 *
 * Unit tests for the request-scoped memoization introduced by the
 * single-post performance remediation:
 *   - app_settings()/app_setting() read tbl_settings once per request
 *   - theme_identifier()/theme_dir() resolve the active theme once per request
 *   - page_cache_is_enabled()/page_cache_ttl() honor the cache_enabled and
 *     cache_lifetime settings (P3 toggle)
 *
 * The memoization statics persist for the lifetime of the PHPUnit process,
 * so every case presets the reference holders directly (no DB dependency)
 * and resets them in setUp() to avoid cross-test pollution.
 */

use PHPUnit\Framework\TestCase;

class SettingsThemeMemoizationTest extends TestCase
{
    protected function setUp(): void
    {
        if (function_exists('reset_app_settings_cache')) {
            reset_app_settings_cache();
        }
        if (function_exists('reset_theme_identifier_cache')) {
            reset_theme_identifier_cache();
        }
        if (function_exists('reset_theme_dir_cache')) {
            reset_theme_dir_cache();
        }
    }

    protected function tearDown(): void
    {
        if (function_exists('reset_app_settings_cache')) {
            reset_app_settings_cache();
        }
        if (function_exists('reset_theme_identifier_cache')) {
            reset_theme_identifier_cache();
        }
        if (function_exists('reset_theme_dir_cache')) {
            reset_theme_dir_cache();
        }
    }

    private function presetSettings(array $map): void
    {
        $cache = &app_settings_cache();
        $cache = $map;
    }

    // ==================== app_settings / app_setting ====================

    public function testAppSettingsReturnsPresetMapWithoutQuerying()
    {
        $this->presetSettings(['site_name' => 'Blog', 'app_url' => 'https://example.com']);

        $this->assertSame(
            ['site_name' => 'Blog', 'app_url' => 'https://example.com'],
            app_settings()
        );
    }

    public function testAppSettingsIsMemoizedAcrossCalls()
    {
        $this->presetSettings(['site_name' => 'Blog']);

        $first = app_settings();
        $second = app_settings();

        $this->assertSame($first, $second);
        $this->assertSame('Blog', $first['site_name']);
    }

    public function testAppSettingReturnsStoredValue()
    {
        $this->presetSettings(['site_name' => 'Blog']);

        $this->assertSame('Blog', app_setting('site_name', 'fallback'));
    }

    public function testAppSettingReturnsDefaultForMissingKey()
    {
        $this->presetSettings(['site_name' => 'Blog']);

        $this->assertSame('fallback', app_setting('missing_key', 'fallback'));
    }

    public function testAppSettingReturnsDefaultForBlankValue()
    {
        $this->presetSettings(['site_name' => '']);

        $this->assertSame('fallback', app_setting('site_name', 'fallback'));
    }

    public function testResetAppSettingsCacheClearsMemo()
    {
        $this->presetSettings(['site_name' => 'Blog']);

        reset_app_settings_cache();

        $cache = &app_settings_cache();
        $this->assertNull($cache);
    }

    // ==================== theme_identifier / theme_dir ====================

    public function testThemeIdentifierReturnsPresetRowWithoutQuerying()
    {
        $state = &theme_identifier_cache();
        $state = ['theme' => ['theme_directory' => 'blog'], 'resolved' => true];

        $this->assertSame(['theme_directory' => 'blog'], theme_identifier());
    }

    public function testThemeIdentifierIsMemoizedAcrossCalls()
    {
        $state = &theme_identifier_cache();
        $state = ['theme' => ['theme_directory' => 'blog'], 'resolved' => true];

        $first = theme_identifier();
        $second = theme_identifier();

        $this->assertSame($first, $second);
        $this->assertSame('blog', $first['theme_directory']);
    }

    public function testResetThemeIdentifierCacheClearsMemo()
    {
        $state = &theme_identifier_cache();
        $state = ['theme' => ['theme_directory' => 'blog'], 'resolved' => true];

        reset_theme_identifier_cache();

        $this->assertFalse(theme_identifier_cache()['resolved']);
        $this->assertNull(theme_identifier_cache()['theme']);
    }

    public function testThemeDirReturnsPresetDirectory()
    {
        $cache = &theme_dir_cache();
        $cache = '/var/www/theme/blog/';

        $this->assertSame('/var/www/theme/blog/', theme_dir());
    }

    public function testThemeDirIsMemoizedAcrossCalls()
    {
        $cache = &theme_dir_cache();
        $cache = '/var/www/theme/blog/';

        $this->assertSame('/var/www/theme/blog/', theme_dir());
        $this->assertSame('/var/www/theme/blog/', theme_dir());
    }

    public function testResetThemeDirCacheClearsMemo()
    {
        $cache = &theme_dir_cache();
        $cache = '/var/www/theme/blog/';

        reset_theme_dir_cache();

        $this->assertNull(theme_dir_cache());
    }

    // ==================== page_cache_is_enabled / page_cache_ttl ====================

    public function testPageCacheIsEnabledWhenSettingOn()
    {
        if (defined('APP_CACHE') && APP_CACHE === true) {
            $this->markTestSkipped('APP_CACHE is already enabled in this environment');
        }

        $this->presetSettings(['cache_enabled' => '1']);

        $this->assertTrue(page_cache_is_enabled());
    }

    public function testPageCacheDisabledByDefault()
    {
        if (defined('APP_CACHE') && APP_CACHE === true) {
            $this->markTestSkipped('APP_CACHE is already enabled in this environment');
        }

        $this->presetSettings(['cache_enabled' => '0']);

        $this->assertFalse(page_cache_is_enabled());
    }

    public function testPageCacheDisabledWhenSettingAbsent()
    {
        if (defined('APP_CACHE') && APP_CACHE === true) {
            $this->markTestSkipped('APP_CACHE is already enabled in this environment');
        }

        $this->presetSettings([]);

        $this->assertFalse(page_cache_is_enabled());
    }

    public function testPageCacheTtlHonorsLifetimeSetting()
    {
        $this->presetSettings(['cache_lifetime' => '120']);

        $this->assertSame(120, page_cache_ttl());
    }

    public function testPageCacheTtlFallsBackToConstant()
    {
        $this->presetSettings([]);

        $expected = (defined('APP_CACHE_LIFETIME')) ? APP_CACHE_LIFETIME : 3600;

        $this->assertSame($expected, page_cache_ttl());
    }

    public function testPageCacheTtlFallsBackForZeroLifetime()
    {
        $this->presetSettings(['cache_lifetime' => '0']);

        $expected = (defined('APP_CACHE_LIFETIME')) ? APP_CACHE_LIFETIME : 3600;

        $this->assertSame($expected, page_cache_ttl());
    }

    public function testPageCacheExistsOnlyWhenEnabledAndFresh()
    {
        if (!defined('APP_CACHE_DIR') || (defined('APP_CACHE') && APP_CACHE === true)) {
            $this->markTestSkipped('Requires APP_CACHE_DIR and APP_CACHE disabled in this environment');
        }

        $originalCookie = $_COOKIE;
        $originalGet = $_GET;
        $originalServer = $_SERVER;

        $_SERVER['REQUEST_URI'] = '/some-unique-path';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_COOKIE = [];
        $_GET = [];

        try {
            $this->presetSettings(['cache_enabled' => '0']);

            // Cached file exists but the cache setting is off -> not served.
            $key = page_cache_key();
            $path = page_cache_path($key);
            if (!is_dir(APP_CACHE_DIR)) {
                mkdir(APP_CACHE_DIR, 0755, true);
            }
            file_put_contents($path, 'cached');
            $this->assertFalse(page_cache_exists());

            // Flip the setting on; the fresh file must now be served.
            $this->presetSettings(['cache_enabled' => '1']);
            $this->assertTrue(page_cache_exists());
        } finally {
            @unlink($path);
            $_COOKIE = $originalCookie;
            $_GET = $originalGet;
            $_SERVER = $originalServer;
        }
    }
}
