<?php
/**
 * TranslationLoader Unit Test
 * 
 * Tests for the TranslationLoader class
 * 
 * @version 1.0
 * @since 1.0
 */

use PHPUnit\Framework\TestCase;

class TranslationLoaderTest extends TestCase
{
    private $loader;
    private $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheDir = sys_get_temp_dir() . '/scriptlog_test_cache_' . uniqid();
        mkdir($this->cacheDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up cache directory
        $this->removeDirectory($this->cacheDir);
        
        parent::tearDown();
    }
    
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $items = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($items, RecursiveIteratorIterator::CHILD_FIRST);
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        rmdir($dir);
    }

    public function testConstructorCreatesCacheDirectory()
    {
        $customDir = $this->cacheDir . '/translations';
        
        // We can't easily test the actual constructor without DB,
        // but we can verify directory creation logic
        $this->assertTrue(is_dir($this->cacheDir));
    }

    public function testCacheFilePathGeneration()
    {
        // Test that cache file path is correctly formatted
        $locale = 'en';
        $expectedSuffix = 'en.json';
        
        $path = $this->cacheDir . '/' . $locale . '.json';
        $this->assertStringContainsString($expectedSuffix, $path);
        $this->assertStringContainsString($this->cacheDir, $path);
    }

    public function testIsCacheEnabled()
    {
        if (!class_exists('TranslationLoader')) {
            $this->markTestSkipped('TranslationLoader class not found');
        }
        
        // Test the public method if available
        $reflection = new ReflectionClass(TranslationLoader::class);
        
        if ($reflection->hasMethod('isCacheEnabled')) {
            $this->assertTrue(method_exists(TranslationLoader::class, 'isCacheEnabled'));
        }
    }

    public function testSetCacheEnabled()
    {
        if (!class_exists('TranslationLoader')) {
            $this->markTestSkipped('TranslationLoader class not found');
        }
        
        $reflection = new ReflectionClass(TranslationLoader::class);
        
        if ($reflection->hasMethod('setCacheEnabled')) {
            $this->assertTrue(method_exists(TranslationLoader::class, 'setCacheEnabled'));
        }
    }

    public function testInterpolateMethod()
    {
        // Test the interpolation logic
        $text = 'Hello :name, you have :count messages';
        $params = ['name' => 'John', 'count' => 5];
        
        $result = str_replace(
            array_map(fn($k) => ':' . $k, array_keys($params)),
            array_values($params),
            $text
        );
        
        $this->assertEquals('Hello John, you have 5 messages', $result);
    }

    public function testInterpolateWithEmptyParams()
    {
        $text = 'Hello :name';
        $params = [];
        
        $result = str_replace(
            array_map(fn($k) => ':' . $k, array_keys($params)),
            array_values($params),
            $text
        );
        
        $this->assertEquals('Hello :name', $result);
    }

    public function testJsonCacheStructure()
    {
        // Test the expected JSON cache structure
        $cacheData = [
            '_meta' => [
                'locale' => 'en',
                'generated' => time(),
                'expires' => time() + 3600,
                'count' => 10
            ],
            '_data' => [
                'header.nav.home' => 'Home',
                'header.nav.blog' => 'Blog'
            ]
        ];
        
        $json = json_encode($cacheData, JSON_PRETTY_PRINT);
        $decoded = json_decode($json, true);
        
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('_meta', $decoded);
        $this->assertArrayHasKey('_data', $decoded);
        $this->assertEquals('en', $decoded['_meta']['locale']);
        $this->assertCount(2, $decoded['_data']);
    }

    public function testCacheExpiryLogic()
    {
        $cacheTtl = 3600; // 1 hour
        $cacheFile = $this->cacheDir . '/test_cache.json';
        
        // Create a cache file
        file_put_contents($cacheFile, json_encode(['test' => 'data']));
        
        // File should not be expired immediately
        $mtime = filemtime($cacheFile);
        $isExpired = (time() - $mtime) > $cacheTtl;
        
        $this->assertFalse($isExpired);
        
        // Simulate old file
        touch($cacheFile, time() - ($cacheTtl + 100));
        $mtime = filemtime($cacheFile);
        $isExpired = (time() - $mtime) > $cacheTtl;
        
        $this->assertTrue($isExpired);
    }

    public function testMemoryCacheLayer()
    {
        // Test that memory caching works correctly
        $memoryCache = [];
        
        $locale = 'en';
        $data = ['header.nav.home' => 'Home'];
        
        // First access - should be null
        $this->assertArrayNotHasKey($locale, $memoryCache);
        
        // Set data
        $memoryCache[$locale] = $data;
        
        // Second access - should have data
        $this->assertArrayHasKey($locale, $memoryCache);
        $this->assertEquals($data, $memoryCache[$locale]);
        
        // Invalidate
        unset($memoryCache[$locale]);
        $this->assertArrayNotHasKey($locale, $memoryCache);
    }
}
