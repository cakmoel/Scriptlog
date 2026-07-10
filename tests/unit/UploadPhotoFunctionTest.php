<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * Upload Photo Function Test
 *
 * Tests for upload_photo() change: skip set_origin_photo when fileinfo is loaded.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class UploadPhotoFunctionTest extends TestCase
{
    private $source;

    protected function setUp(): void
    {
        $path = __DIR__ . '/../../src/lib/utility/upload-photo.php';
        if (file_exists($path)) {
            $this->source = file_get_contents($path);
        }
    }

    public function testUploadPhotoHasFileinfoConditionCheck(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('upload-photo.php not found');
        }
        $this->assertStringContainsString('extension_loaded', $this->source);
    }

    public function testUploadPhotoSkipsSetOriginPhotoWhenFileinfoLoaded(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('upload-photo.php not found');
        }
        $this->assertStringContainsString('!(extension_loaded', $this->source);
    }

    public function testUploadPhotoHasSetOriginPhotoCall(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('upload-photo.php not found');
        }
        $this->assertStringContainsString('set_origin_photo', $this->source);
    }

    public function testUploadPhotoHasSetWebpOriginCall(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('upload-photo.php not found');
        }
        $this->assertStringContainsString('set_webp_origin', $this->source);
    }

    public function testUploadPhotoFileinfoCheckForSaveOrigin(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('upload-photo.php not found');
        }
        $pattern = "/if\s*\(!\s*\(extension_loaded\('fileinfo'\)/";
        $this->assertMatchesRegularExpression($pattern, $this->source);
    }

    public function testUploadPhotoFunctionSignature(): void
    {
        if (!function_exists('upload_photo')) {
            $this->markTestSkipped('upload_photo function not found');
        }
        $reflection = new ReflectionFunction('upload_photo');
        $params = $reflection->getParameters();
        $this->assertCount(4, $params);
        $this->assertEquals('file_location', $params[0]->getName());
        $this->assertEquals('file_size', $params[1]->getName());
        $this->assertEquals('file_type', $params[2]->getName());
        $this->assertEquals('file_name', $params[3]->getName());
    }
}
