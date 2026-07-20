<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class UploadedFileDtoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_FILES = [];
        parent::tearDown();
    }

    public function testConstructorPopulatesProperties(): void
    {
        $file = [
            'tmp_name' => '/tmp/phpabc123',
            'type' => 'image/jpeg',
            'name' => 'photo.jpg',
            'size' => 1024,
            'error' => UPLOAD_ERR_OK,
        ];

        $dto = new \Scriptlog\Dto\UploadedFileDto($file);

        $this->assertEquals('/tmp/phpabc123', $dto->tmpName);
        $this->assertEquals('image/jpeg', $dto->type);
        $this->assertEquals('photo.jpg', $dto->name);
        $this->assertEquals(1024, $dto->size);
        $this->assertEquals(UPLOAD_ERR_OK, $dto->error);
    }

    public function testConstructorHandlesMissingFields(): void
    {
        $dto = new \Scriptlog\Dto\UploadedFileDto([]);

        $this->assertEquals('', $dto->tmpName);
        $this->assertEquals('', $dto->type);
        $this->assertEquals('', $dto->name);
        $this->assertEquals(0, $dto->size);
        $this->assertEquals(UPLOAD_ERR_NO_FILE, $dto->error);
    }

    public function testIsValidReturnsTrueWhenUploadOk(): void
    {
        $dto = new \Scriptlog\Dto\UploadedFileDto([
            'tmp_name' => '/tmp/valid.tmp',
            'type' => 'text/plain',
            'name' => 'test.txt',
            'size' => 100,
            'error' => UPLOAD_ERR_OK,
        ]);

        $this->assertTrue($dto->isValid());
    }

    public function testIsValidReturnsFalseWhenNoFile(): void
    {
        $dto = new \Scriptlog\Dto\UploadedFileDto([]);
        $this->assertFalse($dto->isValid());
    }

    public function testIsValidReturnsFalseWhenUploadError(): void
    {
        $dto = new \Scriptlog\Dto\UploadedFileDto([
            'tmp_name' => '',
            'type' => '',
            'name' => '',
            'size' => 0,
            'error' => UPLOAD_ERR_NO_FILE,
        ]);

        $this->assertFalse($dto->isValid());
    }

    public function testHasUploadErrorReturnsTrueForErrors(): void
    {
        $dto = new \Scriptlog\Dto\UploadedFileDto([
            'error' => UPLOAD_ERR_NO_FILE,
        ]);

        $this->assertTrue($dto->hasUploadError());
    }

    public function testHasUploadErrorReturnsFalseForOk(): void
    {
        $dto = new \Scriptlog\Dto\UploadedFileDto([
            'tmp_name' => '/tmp/f.tmp',
            'error' => UPLOAD_ERR_OK,
        ]);

        $this->assertFalse($dto->hasUploadError());
    }

    public function testGetExtensionReturnsExtension(): void
    {
        $dto = new \Scriptlog\Dto\UploadedFileDto([
            'name' => 'document.pdf',
        ]);

        $this->assertEquals('pdf', $dto->getExtension());
    }

    public function testGetExtensionReturnsEmptyForNoExtension(): void
    {
        $dto = new \Scriptlog\Dto\UploadedFileDto([
            'name' => 'README',
        ]);

        $this->assertEquals('', $dto->getExtension());
    }

    public function testFromGlobalsCreatesFromSuperglobal(): void
    {
        $_FILES['media'] = [
            'tmp_name' => '/tmp/global.tmp',
            'type' => 'image/png',
            'name' => 'screenshot.png',
            'size' => 2048,
            'error' => UPLOAD_ERR_OK,
        ];

        $dto = \Scriptlog\Dto\UploadedFileDto::fromGlobals();

        $this->assertEquals('screenshot.png', $dto->name);
        $this->assertEquals('png', $dto->getExtension());
    }

    public function testFromGlobalsReturnsEmptyWhenNoFile(): void
    {
        $dto = \Scriptlog\Dto\UploadedFileDto::fromGlobals();

        $this->assertEquals('', $dto->tmpName);
        $this->assertEquals(UPLOAD_ERR_NO_FILE, $dto->error);
    }
}
