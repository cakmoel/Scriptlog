<?php

use PHPUnit\Framework\TestCase;

class GetMimeGroupTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../lib/utility/media-helpers.php';
    }

    public function testDocumentGroup(): void
    {
        $this->assertSame('doc', get_mime_group('application/pdf'));
        $this->assertSame('doc', get_mime_group('application/msword'));
        $this->assertSame('doc', get_mime_group('application/vnd.openxmlformats-officedocument.wordprocessingml.document'));
        $this->assertSame('doc', get_mime_group('application/vnd.ms-excel'));
        $this->assertSame('doc', get_mime_group('application/rtf'));
        $this->assertSame('doc', get_mime_group('application/vnd.ms-powerpoint'));
        $this->assertSame('doc', get_mime_group('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
        $this->assertSame('doc', get_mime_group('application/vnd.openxmlformats-officedocument.presentationml.presentation'));
        $this->assertSame('doc', get_mime_group('application/vnd.oasis.opendocument.text'));
        $this->assertSame('doc', get_mime_group('application/vnd.oasis.opendocument.spreadsheet'));
    }

    public function testImageGroup(): void
    {
        $this->assertSame('image', get_mime_group('image/jpeg'));
        $this->assertSame('image', get_mime_group('image/png'));
        $this->assertSame('image', get_mime_group('image/gif'));
        $this->assertSame('image', get_mime_group('image/webp'));
        $this->assertSame('image', get_mime_group('image/tiff'));
        $this->assertSame('image', get_mime_group('image/x-icon'));
    }

    public function testVideoGroup(): void
    {
        $this->assertSame('video', get_mime_group('video/mp4'));
        $this->assertSame('video', get_mime_group('video/webm'));
        $this->assertSame('video', get_mime_group('video/ogg'));
        $this->assertSame('video', get_mime_group('video/mpeg'));
        $this->assertSame('video', get_mime_group('video/quicktime'));
        $this->assertSame('video', get_mime_group('video/x-msvideo'));
    }

    public function testAudioGroup(): void
    {
        $this->assertSame('audio', get_mime_group('audio/mpeg'));
        $this->assertSame('audio', get_mime_group('audio/ogg'));
        $this->assertSame('audio', get_mime_group('audio/wav'));
        $this->assertSame('audio', get_mime_group('audio/aac'));
        $this->assertSame('audio', get_mime_group('audio/flac'));
    }

    public function testDocGroupArchiveEntries(): void
    {
        $this->assertSame('doc', get_mime_group('application/zip'));
        $this->assertSame('doc', get_mime_group('application/x-rar-compressed'));
        $this->assertSame('doc', get_mime_group('application/x-zip'));
        $this->assertSame('doc', get_mime_group('multipart/x-zip'));
        $this->assertSame('doc', get_mime_group('application/x-zip-compressed'));
        $this->assertSame('doc', get_mime_group('application/rar'));
        $this->assertSame('doc', get_mime_group('application/vnd.microsoft.portable-executable'));
        $this->assertSame('doc', get_mime_group('application/octet-stream'));
    }

    public function testImageBorderlineMimesReturnNull(): void
    {
        $this->assertNull(get_mime_group('image/svg+xml'));
        $this->assertNull(get_mime_group('image/avif'));
    }

    public function testUnknownMimeReturnsNull(): void
    {
        $this->assertNull(get_mime_group('application/x-unknown'));
        $this->assertNull(get_mime_group('text/html'));
        $this->assertNull(get_mime_group('text/plain'));
        $this->assertNull(get_mime_group('text/csv'));
        $this->assertNull(get_mime_group(''));
    }

    public function testImageGroupWithBmp(): void
    {
        $this->assertSame('image', get_mime_group('image/bmp'));
    }
}
