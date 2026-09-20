<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;
use Scriptlog\Dto\UploadedFileDto;

class UploadedFileDtoTest extends TestCase
{
    public function testValidUpload(): void
    {
        $file = [
            'tmp_name' => '/tmp/php1234',
            'type' => 'image/jpeg',
            'name' => 'photo.jpg',
            'size' => 1024,
            'error' => UPLOAD_ERR_OK,
        ];
        $dto = new UploadedFileDto($file);
        $this->assertTrue($dto->isValid());
    }

    public function testHasUploadError(): void
    {
        $file = [
            'tmp_name' => '',
            'type' => '',
            'name' => '',
            'size' => 0,
            'error' => UPLOAD_ERR_NO_FILE,
        ];
        $dto = new UploadedFileDto($file);
        $this->assertTrue($dto->hasUploadError());
        $this->assertFalse($dto->isValid());
    }

    public function testGetExtension(): void
    {
        $file = [
            'tmp_name' => '/tmp/php5678',
            'type' => 'image/png',
            'name' => 'screenshot.png',
            'size' => 2048,
            'error' => UPLOAD_ERR_OK,
        ];
        $dto = new UploadedFileDto($file);
        $this->assertSame('png', $dto->getExtension());
    }

    public function testGetExtensionNoExtension(): void
    {
        $file = [
            'tmp_name' => '/tmp/php5678',
            'type' => 'image/jpeg',
            'name' => 'noext',
            'size' => 2048,
            'error' => UPLOAD_ERR_OK,
        ];
        $dto = new UploadedFileDto($file);
        $this->assertSame('', $dto->getExtension());
    }
}
