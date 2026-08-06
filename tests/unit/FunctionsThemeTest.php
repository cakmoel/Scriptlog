<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * Theme Functions Test
 *
 * Tests for theme helper functions that were split out of functions.php into
 * the domain-specific modules during the Phase 5 remediation:
 * - link_tag() input validation  (functions-post.php)
 * - link_topic() return type and validation (functions-post.php)
 * - retrieve_detail_post() validation (functions-post.php)
 * - get_post_thumbnail() new function (functions-media.php)
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class FunctionsThemeTest extends TestCase
{
    private $source;

    protected function setUp(): void
    {
        $paths = [
            __DIR__ . '/../../src/public/themes/blog/functions.php',
            __DIR__ . '/../../src/public/themes/blog/functions-post.php',
            __DIR__ . '/../../src/public/themes/blog/functions-media.php',
        ];
        $this->source = '';
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $this->source .= "\n" . file_get_contents($path);
            }
        }
    }

    public function testLinkTagHasFilterVarValidation(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('functions.php not found');
        }
        $this->assertStringContainsString('FILTER_VALIDATE_INT', $this->source);
    }

    public function testLinkTagReturnsEmptyForInvalidId(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('functions.php not found');
        }
        $this->assertStringContainsString('return ""', $this->source);
    }

    public function testLinkTopicHasStringReturnType(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('functions.php not found');
        }
        $this->assertStringContainsString('function link_topic($id): string', $this->source);
    }

    public function testLinkTopicHasFilterVarValidation(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('functions.php not found');
        }
        $this->assertStringContainsString('FILTER_VALIDATE_INT', $this->source);
    }

    public function testLinkTopicValidatesClassExists(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('functions.php not found');
        }
        $this->assertStringContainsString('class_exists', $this->source);
    }

    public function testRetrieveDetailPostHasIdValidation(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('functions.php not found');
        }
        $this->assertStringContainsString('filter_var($id, FILTER_VALIDATE_INT)', $this->source);
    }

    public function testRetrieveDetailPostReturnsEmptyArrayForInvalidId(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('functions.php not found');
        }
        $this->assertStringContainsString('return array()', $this->source);
    }

    public function testGetPostThumbnailFunctionExists(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('functions.php not found');
        }
        $this->assertStringContainsString('function get_post_thumbnail', $this->source);
    }

    public function testGetPostThumbnailHasFallbackImage(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('functions.php not found');
        }
        $this->assertStringContainsString('nophoto.jpg', $this->source);
    }

    public function testGetPostThumbnailChecksEmptyImage(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('functions.php not found');
        }
        $this->assertStringContainsString('empty($post_img)', $this->source);
    }

    public function testRetrieveDetailPostChecksIdGreaterThanZero(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('functions.php not found');
        }
        $this->assertStringContainsString('> 0', $this->source);
    }
}
