<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class MediaHelpersFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!function_exists('get_image_format')) {
            require_once __DIR__ . '/../../src/lib/utility/media-helpers.php';
        }
    }

    public function testGetImageFormatReturnsPng(): void
    {
        $this->assertEquals('png', get_image_format('image/png'));
    }

    public function testGetImageFormatReturnsJpeg(): void
    {
        $this->assertEquals('jpeg', get_image_format('image/jpeg'));
    }

    public function testGetImageFormatReturnsJpegForPjpeg(): void
    {
        $this->assertEquals('jpeg', get_image_format('image/pjpeg'));
    }

    public function testGetImageFormatReturnsJpegForJpg(): void
    {
        $this->assertEquals('jpeg', get_image_format('image/jpg'));
    }

    public function testGetImageFormatReturnsGif(): void
    {
        $this->assertEquals('gif', get_image_format('image/gif'));
    }

    public function testGetImageFormatReturnsWebp(): void
    {
        $this->assertEquals('webp', get_image_format('image/webp'));
    }

    public function testGetImageFormatReturnsBmp(): void
    {
        $this->assertEquals('bmp', get_image_format('image/bmp'));
    }

    public function testGetImageFormatReturnsNullForUnknown(): void
    {
        $this->assertNull(get_image_format('image/avif'));
    }

    public function testGetImageFormatReturnsNullForPdf(): void
    {
        $this->assertNull(get_image_format('application/pdf'));
    }

    public function testGetImageFormatReturnsNullForEmptyString(): void
    {
        $this->assertNull(get_image_format(''));
    }

    public function testGetMimeGroupReturnsImage(): void
    {
        $this->assertEquals('image', get_mime_group('image/jpeg'));
        $this->assertEquals('image', get_mime_group('image/png'));
        $this->assertEquals('image', get_mime_group('image/gif'));
    }

    public function testGetMimeGroupReturnsAudio(): void
    {
        $this->assertEquals('audio', get_mime_group('audio/mpeg'));
        $this->assertEquals('audio', get_mime_group('audio/wav'));
        $this->assertEquals('audio', get_mime_group('audio/ogg'));
    }

    public function testGetMimeGroupReturnsVideo(): void
    {
        $this->assertEquals('video', get_mime_group('video/mp4'));
        $this->assertEquals('video', get_mime_group('video/webm'));
        $this->assertEquals('video', get_mime_group('video/mpeg'));
    }

    public function testGetMimeGroupReturnsDoc(): void
    {
        $this->assertEquals('doc', get_mime_group('application/pdf'));
        $this->assertEquals('doc', get_mime_group('application/msword'));
        $this->assertEquals('doc', get_mime_group('application/zip'));
    }

    public function testGetMimeGroupReturnsNullForUnknown(): void
    {
        $this->assertNull(get_mime_group('application/x-unknown'));
    }

    public function testGetMimeGroupReturnsNullForEmptyString(): void
    {
        $this->assertNull(get_mime_group(''));
    }
}
