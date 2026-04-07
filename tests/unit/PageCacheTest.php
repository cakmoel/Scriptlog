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
        // Mock APP_CACHE constant behavior via a helper if possible, 
        // but here it's defined in common.php as false by default.
        // For testing purposes, we assume we need to check the logic.
        
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
        file_put_contents($testFile, 'test content');
        
        $this->assertFileExists($testFile);
        
        page_cache_clear();
        
        $this->assertFileDoesNotExist($testFile);
    }
}
