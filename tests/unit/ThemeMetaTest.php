<?php

use PHPUnit\Framework\TestCase;

class ThemeMetaTest extends TestCase
{
    private string $utilityPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->utilityPath = __DIR__ . '/../../src/lib/utility/theme-meta.php';
        
        if (!function_exists('theme_meta')) {
            require_once $this->utilityPath;
        }
    }

    public function testThemeMetaFunctionExists(): void
    {
        $this->assertTrue(function_exists('theme_meta'));
    }

    public function testMetatagByPathFunctionExists(): void
    {
        $this->assertTrue(function_exists('metatag_by_path'));
    }

    public function testMetatagByQueryFunctionExists(): void
    {
        $this->assertTrue(function_exists('metatag_by_query'));
    }

    public function testMetatagByPathReturnsArrayWhenUriIsNull(): void
    {
        $result = metatag_by_path(
            'http://example.com/image.jpg',
            'http://example.com/thumb.jpg',
            null
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('site_schema', $result);
        $this->assertArrayHasKey('site_meta_tags', $result);
        $this->assertSame('', $result['site_schema']);
        $this->assertSame('', $result['site_meta_tags']);
    }

    public function testMetatagByPathReturnsArrayWhenUriIsString(): void
    {
        $result = metatag_by_path(
            'http://example.com/image.jpg',
            'http://example.com/thumb.jpg',
            'invalid-string'
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('site_schema', $result);
        $this->assertArrayHasKey('site_meta_tags', $result);
        $this->assertSame('', $result['site_schema']);
        $this->assertSame('', $result['site_meta_tags']);
    }

    public function testMetatagByQueryThrowsExceptionForEmptyPostValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        metatag_by_query('p', '', 'img.jpg', 'thumb.jpg');
    }

    public function testMetatagByQueryThrowsExceptionForEmptyPageValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        metatag_by_query('pg', '', 'img.jpg', 'thumb.jpg');
    }

    public function testMetatagByQueryThrowsExceptionForEmptyCategoryValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        metatag_by_query('cat', '', 'img.jpg', 'thumb.jpg');
    }

    public function testMetatagByQueryThrowsExceptionForEmptyTagValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        metatag_by_query('tag', '', 'img.jpg', 'thumb.jpg');
    }

    public function testMetatagByQueryThrowsExceptionForEmptyArchiveValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        metatag_by_query('a', '', 'img.jpg', 'thumb.jpg');
    }

    public function testSourceFileIsValidPhpSyntax(): void
    {
        $output = [];
        $returnCode = 0;
        exec('php -l ' . escapeshellarg($this->utilityPath) . ' 2>&1', $output, $returnCode);
        $this->assertEquals(0, $returnCode, 'PHP syntax check failed: ' . implode("\n", $output));
    }

    public function testMetatagByPathUsesInstanceofInsteadOfIsA(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('$uri instanceof RequestPath', $source);
        $this->assertStringNotContainsString("is_a(\$uri, 'RequestPath')", $source);
    }

    public function testMetatagByPathHasReturnTypeHint(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('function metatag_by_path(', $source);
        $this->assertMatchesRegularExpression('/function metatag_by_path\(.*\): array/', $source);
    }

    public function testMetatagByQueryHasReturnTypeHint(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertMatchesRegularExpression('/function metatag_by_query\(.*\): array/', $source);
    }

    public function testThemeMetaHasReturnTypeHint(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertMatchesRegularExpression('/function theme_meta\(\): array/', $source);
    }

    public function testRemovedRedundantVariablesInMetatagByPath(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringNotContainsString('$created_at = null;', $source);
        $this->assertStringNotContainsString('$modified_at = null;', $source);
    }

    public function testMetatagByPathCastsFrontHelperResultToArray(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('(array) FrontHelper::grabPreparedFrontPostById', $source);
        $this->assertStringContainsString('(array) FrontHelper::grabPreparedFrontPageBySlug', $source);
        $this->assertStringContainsString('(array) FrontHelper::grabPreparedFrontTopicBySlug', $source);
    }

    public function testMetatagByPathPerformsNullCheckOnUri(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('if ($uri === null)', $source);
    }

    public function testMetatagByPathRemovedIsAInFavorOfInstanceof(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('($uri instanceof RequestPath)', $source);
    }
}
