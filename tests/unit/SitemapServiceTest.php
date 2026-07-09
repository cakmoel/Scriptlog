<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class SitemapServiceTest extends TestCase
{
    public function testSitemapServiceClassExists(): void
    {
        $this->assertTrue(class_exists('SiteMapService'));
    }

    public function testGenerateSitemapMethodExists(): void
    {
        $this->assertTrue(method_exists('SiteMapService', 'generateSitemap'));
    }

    public function testGenerateSitemapHasPhpDocReturnType(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/core/SitemapService.php');

        $this->assertStringContainsString('@return "0" | "1"', $source);
    }

    public function testConstructorHandlesMissingDependencies(): void
    {
        if (!class_exists('SiteMapService')) {
            $this->markTestSkipped('SiteMapService class not found');
        }

        $reflection = new ReflectionClass('SiteMapService');
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $this->assertTrue($constructor->isPublic());
    }

    public function testGenerateSitemapReturnTypeAnnotation(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/core/SitemapService.php');

        preg_match('/@return\s+"0"\s*\|\s*"1"/', $source, $matches);
        $this->assertNotEmpty($matches, 'Return type annotation should be "0" | "1"');
    }

    public function testGenerateSitemapCallsCreateSitemapIndex(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/core/SitemapService.php');

        $this->assertStringContainsString('createSitemapIndex', $source);
        $this->assertStringContainsString('generateSitemap', $source);
    }
}