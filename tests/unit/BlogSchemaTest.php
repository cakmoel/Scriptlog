<?php

defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

/**
 * BlogSchema Tests
 *
 * Covers the Scriptlog\Core\BlogSchema JSON-LD generator. The generator
 * delegates to the shared FrontService via the front_service() facade and
 * must fail safely to an empty string when no service is registered.
 */
class BlogSchemaTest extends TestCase
{
    private $source;

    protected function setUp(): void
    {
        $this->source = file_get_contents(__DIR__ . '/../../src/lib/core/BlogSchema.php');
    }

    public function testGenerateBlogPostSchemaReturnsEmptyWithoutService(): void
    {
        if (!class_exists('BlogSchema')) {
            $this->markTestSkipped('BlogSchema class not found');
        }

        $this->assertSame('', BlogSchema::generateBlogPostSchema(1));
    }

    public function testGenerateBlogPostSchemaDelegatesToFrontServiceWithoutRedundantCast(): void
    {
        $this->assertStringContainsString('$frontService->getPublishedPost($post)', $this->source);
        $this->assertStringNotContainsString('getPublishedPost((int)$post)', $this->source);
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('BlogSchema') || class_exists('Scriptlog\\Core\\BlogSchema'));
    }

    public function testSourceFileIsValidPhpSyntax(): void
    {
        $output = [];
        $returnCode = 0;
        exec('php -l ' . escapeshellarg(__DIR__ . '/../../src/lib/core/BlogSchema.php') . ' 2>&1', $output, $returnCode);
        $this->assertEquals(0, $returnCode, 'PHP syntax check failed: ' . implode("\n", $output));
    }
}
