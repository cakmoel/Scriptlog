<?php
/**
 * PerformanceOptimizationTest
 * 
 * Unit tests for Phase 1 & Phase 2 Performance Optimization implementations
 */

use PHPUnit\Framework\TestCase;

class PerformanceOptimizationTest extends TestCase
{
    private $headerContent;
    private $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // Load required files
        require_once __DIR__ . '/../../src/lib/common.php';
        require_once __DIR__ . '/../../src/lib/utility-loader.php';
        
        // Read header.php content (it's in src/public/themes/blog/)
        $headerPath = dirname(__FILE__) . '/../../src/public/themes/blog/header.php';
        if (file_exists($headerPath)) {
            $this->headerContent = file_get_contents($headerPath);
        }
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
    }

    // ==================== PHASE 1: Page Caching ====================
    
    public function testPageCacheFunctionsExist()
    {
        $this->assertTrue(function_exists('page_cache_key'), 'page_cache_key function should exist');
        $this->assertTrue(function_exists('page_cache_path'), 'page_cache_path function should exist');
        $this->assertTrue(function_exists('page_cache_exists'), 'page_cache_exists function should exist');
        $this->assertTrue(function_exists('page_cache_serve'), 'page_cache_serve function should exist');
        $this->assertTrue(function_exists('page_cache_start'), 'page_cache_start function should exist');
        $this->assertTrue(function_exists('page_cache_finish'), 'page_cache_finish function should exist');
        $this->assertTrue(function_exists('page_cache_clear'), 'page_cache_clear function should exist');
    }

    public function testPageCacheKeyIsMd5Hash()
    {
        $_SERVER['REQUEST_URI'] = '/test-page';
        $_SERVER['HTTP_HOST'] = 'example.com';
        
        $key = page_cache_key();
        
        $this->assertNotEmpty($key);
        $this->assertEquals(32, strlen($key));
        $this->assertTrue(ctype_xdigit($key), 'Key should be hexadecimal');
    }

    public function testPageCacheKeyVariesByProtocol()
    {
        $_SERVER['REQUEST_URI'] = '/test';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTPS'] = 'off';
        
        $keyHttp = page_cache_key();
        
        $_SERVER['HTTPS'] = 'on';
        $keyHttps = page_cache_key();
        
        $this->assertNotEquals($keyHttp, $keyHttps, 'HTTPS should generate different cache key');
    }

    public function testPageCacheConstantsDefined()
    {
        $this->assertTrue(defined('APP_CACHE'), 'APP_CACHE constant should be defined');
        $this->assertTrue(defined('APP_CACHE_DIR'), 'APP_CACHE_DIR constant should be defined');
        $this->assertTrue(defined('APP_CACHE_LIFETIME'), 'APP_CACHE_LIFETIME constant should be defined');
        $this->assertIsInt(APP_CACHE_LIFETIME, 'APP_CACHE_LIFETIME should be an integer');
    }

    public function testPageCacheExcludesPostRequests()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        $this->assertFalse(page_cache_exists(), 'POST requests should not be cached');
    }

    public function testPageCacheExcludesSearchRequests()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['s'] = 'test query';
        
        $this->assertFalse(page_cache_exists(), 'Search requests should not be cached');
        
        unset($_GET['s']);
        $_GET['search'] = 'test query';
        
        $this->assertFalse(page_cache_exists(), 'Search requests with search= should not be cached');
    }

    public function testPageCacheExcludesLoggedInUsers()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_COOKIE['scriptlog_auth'] = 'some-auth-token';
        
        $this->assertFalse(page_cache_exists(), 'Logged-in users should not get cached pages');
    }

    public function testPageCacheClearRemovesAllFiles()
    {
        if (!is_dir(APP_CACHE_DIR)) {
            mkdir(APP_CACHE_DIR, 0755, true);
        }
        
        // Create test files
        $file1 = APP_CACHE_DIR . 'test1.html';
        $file2 = APP_CACHE_DIR . 'test2.html';
        file_put_contents($file1, 'test1');
        file_put_contents($file2, 'test2');
        
        $this->assertFileExists($file1);
        $this->assertFileExists($file2);
        
        page_cache_clear();
        
        $this->assertFileDoesNotExist($file1);
        $this->assertFileDoesNotExist($file2);
    }

    // ==================== PHASE 2: Critical CSS & Resource Hints ====================

    public function testHeaderFileContainsCriticalCss()
    {
        $this->assertNotEmpty($this->headerContent, 'Header file should exist');
        
        // Check for critical CSS in <style> tag
        $this->assertStringContainsString('<style>', $this->headerContent, 'Should contain <style> tag');
        
        // Check for critical CSS content (navigation styles)
        $this->assertStringContainsString('sina-nav', $this->headerContent, 'Should contain navigation CSS');
        $this->assertStringContainsString('.main-footer', $this->headerContent, 'Should contain footer CSS');
    }

    public function testHeaderFileContainsResourceHints()
    {
        $this->assertNotEmpty($this->headerContent, 'Header file should exist');
        
        // Check for preconnect hints
        $this->assertStringContainsString('rel="preconnect"', $this->headerContent, 'Should contain preconnect hint');
        
        // Check for Google Fonts preconnect
        $this->assertStringContainsString('fonts.googleapis.com', $this->headerContent, 'Should preconnect to Google Fonts');
        $this->assertStringContainsString('fonts.gstatic.com', $this->headerContent, 'Should preconnect to Google Fonts static');
        
        // Check for preload hint
        $this->assertStringContainsString('rel="preload"', $this->headerContent, 'Should contain preload hint');
    }

    public function testHeaderFileUsesDeferredStylesheetLoading()
    {
        $this->assertNotEmpty($this->headerContent, 'Header file should exist');
        
        // Check for print media trick to defer CSS
        $this->assertStringContainsString('media="print" onload="this.media=', $this->headerContent, 
            'Should use print media trick for deferred CSS loading');
        
        // Check main style is deferred
        $this->assertStringContainsString('style.sea.min.css', $this->headerContent, 
            'Should include main style file');
    }

    public function testMinifiedCssFilesExist()
    {
        $cssFiles = [
            'style.sea.min.css',
            'sina-nav.min.css',
            'custom.min.css',
            'cookie-consent.min.css',
            'comment.min.css',
            'rtl.min.css'
        ];
        
        $basePath = dirname(__FILE__) . '/../../src/public/themes/blog/assets/css/';
        
        foreach ($cssFiles as $file) {
            $this->assertFileExists($basePath . $file, "Minified CSS file $file should exist");
        }
    }

    public function testMinifiedJsFilesExist()
    {
        $jsFiles = [
            'front.min.js',
            'sina-nav.min.js',
            'search.min.js',
            'cookie-consent.min.js',
            'jquery.marquee.min.js',
            'wow.min.js'
        ];
        
        $basePath = dirname(__FILE__) . '/../../src/public/themes/blog/assets/js/';
        
        foreach ($jsFiles as $file) {
            $this->assertFileExists($basePath . $file, "Minified JS file $file should exist");
        }
    }

    public function testVendorMinifiedFilesExist()
    {
        $vendorFiles = [
            'vendor/bootstrap/css/bootstrap.min.css',
            'vendor/font-awesome/css/font-awesome.min.css',
            'vendor/@fancyapps/fancybox/jquery.fancybox.min.css',
            'vendor/jquery/jquery.min.js',
            'vendor/bootstrap/js/bootstrap.min.js'
        ];
        
        $basePath = dirname(__FILE__) . '/../../src/public/themes/blog/assets/';
        
        foreach ($vendorFiles as $file) {
            $this->assertFileExists($basePath . $file, "Vendor minified file $file should exist");
        }
    }

    // ==================== Database Indexes ====================

    public function testDatabaseIndexesDefined()
    {
        // Check if dbtable.php contains the index definitions
        $dbtablePath = dirname(__FILE__) . '/../../src/install/include/dbtable.php';
        
        if (file_exists($dbtablePath)) {
            $content = file_get_contents($dbtablePath);
            
            // Check for post_slug index
            $this->assertStringContainsString('idx_post_slug', $content, 
                'Should contain idx_post_slug index definition');
            
            // Check for topic_slug index
            $this->assertStringContainsString('idx_topic_slug', $content, 
                'Should contain idx_topic_slug index definition');
        } else {
            $this->markTestSkipped('dbtable.php not found');
        }
    }

    // ==================== Web Server Interoperability ====================

    public function testDispatcherUsesApplicationLevelRouting()
    {
        $dispatcherPath = dirname(__FILE__) . '/../../lib/core/Dispatcher.php';
        
        if (file_exists($dispatcherPath)) {
            $content = file_get_contents($dispatcherPath);
            
            // Verify application-level routing is used
            $this->assertStringContainsString('validateContentExists', $content, 
                'Dispatcher should have content validation method');
            
            // Verify 404 handling is at application level
            $this->assertStringContainsString('errorNotFound', $content, 
                'Dispatcher should have errorNotFound method');
            
            // Verify no .htaccess or Nginx config required (PHP-based routing)
            $this->assertStringContainsString('handleSeoFriendlyUrl', $content, 
                'Dispatcher should handle SEO-friendly URLs');
        } else {
            $this->markTestSkipped('Dispatcher.php not found');
        }
    }

    public function testDispatcherValidatesContentBeforeRendering()
    {
        $dispatcherPath = dirname(__FILE__) . '/../../lib/core/Dispatcher.php';
        
        if (file_exists($dispatcherPath)) {
            $content = file_get_contents($dispatcherPath);
            
            // Verify content validation for different route types
            $this->assertStringContainsString("case 'single'", $content, 
                'Should validate single post content');
            $this->assertStringContainsString("case 'page'", $content, 
                'Should validate page content');
            $this->assertStringContainsString("case 'category'", $content, 
                'Should validate category content');
            $this->assertStringContainsString("case 'tag'", $content, 
                'Should validate tag content');
            $this->assertStringContainsString("case 'archive'", $content, 
                'Should validate archive content');
        }
    }
}
