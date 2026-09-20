<?php

use PHPUnit\Framework\TestCase;

class GetImageFormatTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../lib/utility/media-helpers.php';
    }

    public function testPng(): void
    {
        $this->assertSame('png', get_image_format('image/png'));
    }

    public function testGif(): void
    {
        $this->assertSame('gif', get_image_format('image/gif'));
    }

    public function testJpeg(): void
    {
        $this->assertSame('jpeg', get_image_format('image/jpeg'));
    }

    public function testPjpeg(): void
    {
        $this->assertSame('jpeg', get_image_format('image/pjpeg'));
    }

    public function testJpg(): void
    {
        $this->assertSame('jpeg', get_image_format('image/jpg'));
    }

    public function testWebp(): void
    {
        $this->assertSame('webp', get_image_format('image/webp'));
    }

    public function testBmp(): void
    {
        $this->assertSame('bmp', get_image_format('image/bmp'));
    }

    public function testUnknownReturnsNull(): void
    {
        $this->assertNull(get_image_format('application/pdf'));
        $this->assertNull(get_image_format(''));
        $this->assertNull(get_image_format('text/plain'));
    }

    public function testUnsupportedImageMimesReturnNull(): void
    {
        $this->assertNull(get_image_format('image/tiff'));
        $this->assertNull(get_image_format('image/x-icon'));
        $this->assertNull(get_image_format('image/svg+xml'));
        $this->assertNull(get_image_format('image/avif'));
    }

}
