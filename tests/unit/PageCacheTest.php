<?php
/**
 * PageCacheTest
 * 
 * Unit tests for Page Cache utility functions.
 */

use PHPUnit\Framework\TestCase;

class PageCacheTest extends TestCase
{
    private $originalServer;
    private $originalCookie;
    private $originalGet;

    protected function setUp(): void
    {
        // Backup globals
        $this->originalServer = $_SERVER;
        $this->originalCookie = $_COOKIE;
        $this->originalGet = $_GET;

        // Set default server variables for CLI
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTPS']);

        // Reset memoized settings cache so tests are isolated
        if (function_exists('reset_app_settings_cache')) {
            reset_app_settings_cache();
        }
    }

    protected function tearDown(): void
    {
        // Restore globals
        $_SERVER = $this->originalServer;
        $_COOKIE = $this->originalCookie;
        $_GET = $this->originalGet;

        // Cleanup any generated test cache files
        if (defined('APP_CACHE_DIR') && is_dir(APP_CACHE_DIR)) {
            $files = glob(APP_CACHE_DIR . '*.html');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        if (function_exists('reset_app_settings_cache')) {
            reset_app_settings_cache();
        }
    }

    public function testPageCacheKeyGeneration()
    {
        $_SERVER['REQUEST_URI'] = '/test-page';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        $key1 = page_cache_key();
        $this->assertNotEmpty($key1);
        $this->assertEquals(32, strlen($key1));

        $_SERVER['HTTPS'] = 'on';
        $key2 = page_cache_key();
        $this->assertNotEquals($key1, $key2, 'HTTPS should generate a different key');
    }

    public function testPageCachePath()
    {
        $key = 'testkey';
        $path = page_cache_path($key);
        
        $this->assertStringContainsString($key, $path);
        $this->assertStringEndsWith('.html', $path);
        $this->assertStringStartsWith(APP_CACHE_DIR, $path);
    }

    public function testPageCacheExistsExclusions()
    {
        // APP_CACHE is false by default in common.php
        if (!defined('APP_CACHE') || APP_CACHE !== true) {
            $this->assertFalse(page_cache_exists(), 'Should return false if APP_CACHE is disabled');
            return;
        }

        // Test POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertFalse(page_cache_exists(), 'POST requests should not be cached');
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Test search request
        $_GET['s'] = 'query';
        $this->assertFalse(page_cache_exists(), 'Search requests (s=) should not be cached');
        unset($_GET['s']);

        $_GET['search'] = 'query';
        $this->assertFalse(page_cache_exists(), 'Search requests (search=) should not be cached');
        unset($_GET['search']);

        // Test logged-in user
        $_COOKIE['scriptlog_auth'] = 'some-token';
        $this->assertFalse(page_cache_exists(), 'Logged-in users should not be served from cache');
        unset($_COOKIE['scriptlog_auth']);
    }

    public function testPageCacheClear()
    {
        if (!is_dir(APP_CACHE_DIR)) {
            mkdir(APP_CACHE_DIR, 0755, true);
        }

        $testFile = APP_CACHE_DIR . 'test_cache_file.html';
        $result = @file_put_contents($testFile, 'test content');
        
        if ($result === false) {
            $this->markTestSkipped('Cache directory is not writable: ' . APP_CACHE_DIR);
        }
        
        $this->assertFileExists($testFile);
        
        page_cache_clear();
        
        $this->assertFileDoesNotExist($testFile);
    }

    // ─── page_cache_is_enabled ─────────────────────────────────

    public function testPageCacheIsEnabledFunctionExists(): void
    {
        $this->assertTrue(function_exists('page_cache_is_enabled'));
    }

    public function testPageCacheIsEnabledReturnsFalseByDefault(): void
    {
        // APP_CACHE is false by default, and no DB setting is set
        reset_app_settings_cache();
        $this->assertFalse(page_cache_is_enabled());
    }

    public function testPageCacheIsEnabledReturnsTrueWhenAppCacheConstantIsTrue(): void
    {
        // APP_CACHE is false by default in this test env, but we verify
        // the logic path: the function checks APP_CACHE === true first.
        // Since we can't redefine the constant, we test the fallback path.
        reset_app_settings_cache();

        if (defined('APP_CACHE') && APP_CACHE === true) {
            $this->assertTrue(page_cache_is_enabled());
        } else {
            // Without the constant, rely on app_setting
            $this->assertFalse(page_cache_is_enabled());
        }
    }

    public function testPageCacheIsEnabledChecksSettingFallback(): void
    {
        // Simulate the app_setting returning '1' for cache_enabled
        // by pre-populating the settings cache
        if (function_exists('app_settings_cache')) {
            $cache = &app_settings_cache();
            $cache = ['cache_enabled' => '1'];
            $this->assertTrue(page_cache_is_enabled());
        }
    }

    // ─── page_cache_ttl ────────────────────────────────────────

    public function testPageCacheTtlFunctionExists(): void
    {
        $this->assertTrue(function_exists('page_cache_ttl'));
    }

    public function testPageCacheTtlReturnsDefaultConstant(): void
    {
        reset_app_settings_cache();
        $ttl = page_cache_ttl();
        $this->assertIsInt($ttl);
        $this->assertSame(APP_CACHE_LIFETIME, $ttl);
    }

    public function testPageCacheTtlReturnsSettingValueWhenSet(): void
    {
        if (function_exists('app_settings_cache')) {
            $cache = &app_settings_cache();
            $cache = ['cache_lifetime' => '7200'];

            $this->assertSame(7200, page_cache_ttl());
        }
    }

    public function testPageCacheTtlReturnsDefaultForNonNumericSetting(): void
    {
        if (function_exists('app_settings_cache')) {
            $cache = &app_settings_cache();
            $cache = ['cache_lifetime' => 'not-a-number'];

            $this->assertSame(APP_CACHE_LIFETIME, page_cache_ttl());
        }
    }

    public function testPageCacheTtlReturnsDefaultForZeroSetting(): void
    {
        if (function_exists('app_settings_cache')) {
            $cache = &app_settings_cache();
            $cache = ['cache_lifetime' => '0'];

            $this->assertSame(APP_CACHE_LIFETIME, page_cache_ttl());
        }
    }

    public function testPageCacheTtlReturnsDefaultForEmptySetting(): void
    {
        if (function_exists('app_settings_cache')) {
            $cache = &app_settings_cache();
            $cache = ['cache_lifetime' => ''];

            $this->assertSame(APP_CACHE_LIFETIME, page_cache_ttl());
        }
    }

    public function testPageCacheTtlReturnsDefaultForNegativeValue(): void
    {
        if (function_exists('app_settings_cache')) {
            $cache = &app_settings_cache();
            $cache = ['cache_lifetime' => '-100'];

            // ctype_digit('-100') is false, so it falls through to default
            $this->assertSame(APP_CACHE_LIFETIME, page_cache_ttl());
        }
    }

    // ─── page_cache_exists with setting-based enable ────────────

    public function testPageCacheExistsReturnsFalseWhenSettingDisabled(): void
    {
        reset_app_settings_cache();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_GET['search'], $_GET['s'], $_COOKIE['scriptlog_auth']);

        $this->assertFalse(page_cache_exists());
    }
}
