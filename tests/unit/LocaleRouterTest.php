<?php
/**
 * LocaleRouter Unit Test
 * 
 * Tests for the LocaleRouter class
 * 
 * @version 1.0
 * @since 1.0
 */

use PHPUnit\Framework\TestCase;

class LocaleRouterTest extends TestCase
{
    private $router;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->router = new LocaleRouter([
            'default' => 'en',
            'available' => ['en', 'es', 'fr'],
            'auto_detect' => '1'
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testExtractLocaleWithValidLocale()
    {
        $this->assertEquals('en', $this->router->extractLocale('/en/blog'));
        $this->assertEquals('es', $this->router->extractLocale('/es/blog/my-post'));
        $this->assertEquals('fr', $this->router->extractLocale('/fr/category/test'));
    }

    public function testExtractLocaleWithInvalidLocale()
    {
        $this->assertNull($this->router->extractLocale('/de/blog'));
        $this->assertNull($this->router->extractLocale('/blog'));
        $this->assertNull($this->router->extractLocale('/'));
    }

    public function testStripLocalePrefix()
    {
        $this->assertEquals('/blog', $this->router->stripLocalePrefix('/en/blog'));
        $this->assertEquals('/blog/my-post', $this->router->stripLocalePrefix('/es/blog/my-post'));
        $this->assertEquals('/category/test', $this->router->stripLocalePrefix('/fr/category/test'));
    }

    public function testStripLocalePrefixWithNoLocale()
    {
        $this->assertEquals('/blog', $this->router->stripLocalePrefix('/blog'));
    }

    public function testBuildUrl()
    {
        $url = $this->router->buildUrl('/blog');
        
        $this->assertEquals('/en/blog', $url);
    }

    public function testBuildUrlWithLocale()
    {
        $url = $this->router->buildUrl('/blog', 'es');
        
        $this->assertEquals('/es/blog', $url);
    }

    public function testBuildCurrentUrl()
    {
        $url = $this->router->buildCurrentUrl('/blog');
        
        $this->assertIsString($url);
        $this->assertStringStartsWith('/', $url);
    }

    public function testGetRoutes()
    {
        $routes = $this->router->getRoutes();
        
        $this->assertIsArray($routes);
        $this->assertArrayHasKey('home', $routes);
        $this->assertArrayHasKey('blog', $routes);
    }

    public function testAddRoute()
    {
        $this->router->addRoute('custom', '/custom/(?<id>\d+)');
        $routes = $this->router->getRoutes();
        
        $this->assertArrayHasKey('custom', $routes);
        $this->assertEquals('/custom/(?<id>\d+)', $routes['custom']);
    }

    public function testHasLocalePrefix()
    {
        $this->assertTrue($this->router->hasLocalePrefix('/en/blog'));
        $this->assertTrue($this->router->hasLocalePrefix('/es/blog/my-post'));
        $this->assertFalse($this->router->hasLocalePrefix('/blog'));
        $this->assertFalse($this->router->hasLocalePrefix('/'));
    }

    public function testMatchHomeRoute()
    {
        $result = $this->router->match('/en/');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('route', $result);
        $this->assertEquals('home', $result['route']);
        $this->assertEquals('en', $result['locale']);
    }

    public function testMatchBlogRoute()
    {
        $result = $this->router->match('/en/blog');
        
        $this->assertIsArray($result);
        $this->assertEquals('blog', $result['route']);
    }

    public function testMatchReturnsNullForInvalidPath()
    {
        $result = $this->router->match('/invalid/path');
        
        $this->assertNull($result);
    }

    public function testGetDetector()
    {
        $detector = $this->router->getDetector();
        
        $this->assertInstanceOf(LocaleDetector::class, $detector);
    }

    public function testBuildUrlPreservesPath()
    {
        $url = $this->router->buildUrl('/category/test');
        
        $this->assertStringContainsString('/category/test', $url);
    }

    public function testBuildUrlWithLeadingSlash()
    {
        $url = $this->router->buildUrl('blog');
        
        $this->assertStringStartsWith('/en/', $url);
    }

    public function testMatchCategoryRoute()
    {
        $result = $this->router->match('/en/category/test-category');
        
        $this->assertIsArray($result);
        $this->assertEquals('category', $result['route']);
    }

    public function testMatchSinglePostRoute()
    {
        $result = $this->router->match('/en/post/123/test-post');
        
        $this->assertIsArray($result);
        $this->assertEquals('single', $result['route']);
    }
}
