<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;
use Scriptlog\Dto\UploadedFileDto;
use Scriptlog\Validator\FileUploadValidator;

class FileUploadValidatorTest extends TestCase
{
    /** @var string|null */
    private $tempFile;

    protected function tearDown(): void
    {
        $_FILES = [];
        if ($this->tempFile !== null && file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
        $this->tempFile = null;
    }

    public function testNullFilePasses(): void
    {
        $validator = new FileUploadValidator();
        $result = $validator->validate(null);
        $this->assertTrue($result->isValid());
    }

    public function testValidFilePasses(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'upl');
        // Minimal valid JPEG binary (SOI marker)
        file_put_contents($this->tempFile, "\xFF\xD8\xFF\xE0");
        $jpgName = $this->tempFile . '.jpg';
        rename($this->tempFile, $jpgName);
        $this->tempFile = $jpgName;

        $file = new UploadedFileDto([
            'tmp_name' => $this->tempFile,
            'type' => 'image/jpeg',
            'name' => 'test.jpg',
            'size' => filesize($this->tempFile),
            'error' => UPLOAD_ERR_OK,
        ]);
        $validator = new FileUploadValidator();
        $result = $validator->validate($file);
        $this->assertTrue($result->isValid());
    }

    public function testExceededSizeFails(): void
    {
        $this->tempFile = tempnam(sys_get_temp_dir(), 'upl');
        file_put_contents($this->tempFile, "\xFF\xD8\xFF\xE0");
        $jpgName = $this->tempFile . '.jpg';
        rename($this->tempFile, $jpgName);
        $this->tempFile = $jpgName;

        $file = new UploadedFileDto([
            'tmp_name' => $this->tempFile,
            'type' => 'image/jpeg',
            'name' => 'large.jpg',
            'size' => APP_FILE_SIZE + 1,
            'error' => UPLOAD_ERR_OK,
        ]);
        $validator = new FileUploadValidator();
        $result = $validator->validate($file);
        $this->assertFalse($result->isValid());
        $messages = $result->getErrors();
        $this->assertStringContainsString('Exceeded file size', implode(' ', $messages));
    }

    public function testIniSizeErrorFails(): void
    {
        $file = new UploadedFileDto([
            'tmp_name' => '',
            'type' => '',
            'name' => '',
            'size' => 0,
            'error' => UPLOAD_ERR_INI_SIZE,
        ]);
        $validator = new FileUploadValidator();
        $result = $validator->validate($file);
        $this->assertFalse($result->isValid());
        $this->assertContains("Exceeded filesize limit", $result->getErrors());
    }
}
