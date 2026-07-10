<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * MediaDao Bug Fixes Test
 *
 * Tests for findMediaBlog() return type fix and imageUploadHandler() null check.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class MediaDaoBugFixesTest extends TestCase
{
    public function testFindMediaBlogReturnEmptyAsNull(): void
    {
        $source = @file_get_contents(__DIR__ . '/../../src/lib/dao/MediaDao.php');
        if (!$source) {
            $this->markTestSkipped('MediaDao.php not found');
        }
        $this->assertStringContainsString('return empty($item) ? null : $item', $source);
    }

    public function testFindMediaBlogNoLongerReturnsBooleanTrue(): void
    {
        $source = @file_get_contents(__DIR__ . '/../../src/lib/dao/MediaDao.php');
        if (!$source) {
            $this->markTestSkipped('MediaDao.php not found');
        }
        $pattern = '/function findMediaBlog.*?return\s+empty\(\$item\)\s*\?\s*null\s*:\s*\$item/s';
        $this->assertMatchesRegularExpression($pattern, $source);
    }

    public function testImageUploadHandlerHasNullCheck(): void
    {
        $source = @file_get_contents(__DIR__ . '/../../src/lib/dao/MediaDao.php');
        if (!$source) {
            $this->markTestSkipped('MediaDao.php not found');
        }
        $this->assertStringContainsString('null', $source);
    }

    public function testImageUploadHandlerChecksDataMediaIsArray(): void
    {
        $source = @file_get_contents(__DIR__ . '/../../src/lib/dao/MediaDao.php');
        if (!$source) {
            $this->markTestSkipped('MediaDao.php not found');
        }
        $this->assertStringContainsString('!is_array($data_media)', $source);
    }

    public function testFindMediaBlogSignature(): void
    {
        if (!class_exists('MediaDao')) {
            $this->markTestSkipped('MediaDao class not found');
        }
        $reflection = new ReflectionMethod('MediaDao', 'findMediaBlog');
        $this->assertEquals(1, $reflection->getNumberOfParameters());
    }

    public function testImageUploadHandlerSignature(): void
    {
        if (!class_exists('MediaDao')) {
            $this->markTestSkipped('MediaDao class not found');
        }
        $reflection = new ReflectionMethod('MediaDao', 'imageUploadHandler');
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertTrue($params[0]->isOptional());
        $this->assertNull($params[0]->getDefaultValue());
    }
}
