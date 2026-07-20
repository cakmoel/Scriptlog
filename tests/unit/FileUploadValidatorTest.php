<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class FileUploadValidatorTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new \Scriptlog\Validator\FileUploadValidator();
    }

    public function testValidatePassesWhenNull(): void
    {
        $result = $this->validator->validate(null);
        $this->assertTrue($result->isValid());
    }

    public function testValidatePassesWithValidFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test') . '.jpg';
        // Minimal JPEG SOI marker so finfo detects image/jpeg
        file_put_contents($tmpFile, "\xFF\xD8\xFF\xE0\x00\x10\x4A\x46\x49\x46\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xDB\x00\x43\x00\x08\x06\x06\x07\x06\x05\x08\x07\x07\x07\x09\x09\x08\x0A\x0C\x14\x0D\x0C\x0B\x0B\x0C\x19\x12\x13\x0F\x14\x1D\x1A\x1F\x1E\x1D\x1A\x1C\x1C\x20\x24\x2E\x27\x20\x22\x2C\x23\x1C\x1C\x28\x37\x29\x2C\x30\x31\x34\x34\x34\x1F\x27\x39\x3D\x38\x32\x3C\x2E\x33\x34\x32\xFF\xC0\x00\x0B\x08\x00\x01\x00\x01\x01\x01\x11\x00\xFF\xC4\x00\x1F\x00\x00\x01\x05\x01\x01\x01\x01\x01\x01\x00\x00\x00\x00\x00\x00\x01\x02\x03\x04\x05\x06\x07\x08\x09\x0A\x0B\xFF\xC4\x00\xB5\x10\x00\x02\x01\x03\x03\x02\x04\x03\x05\x05\x04\x04\x00\x00\x00\x00\x00\x00\x01\x02\x03\x11\x04\x12\x21\x31\x41\x05\x13\x51\x61\x22\x71\x81\x32\x06\x14\x91\xA1\xB1\xC1\xD1\x23\x24\xF0\xE1\xF1\x15\x33\x62\x72\x82\x09\x0A\x16\x17\x18\x19\x1A\x25\x26\x27\x28\x29\x2A\x34\x35\x36\x37\x38\x39\x3A\x43\x44\x45\x46\x47\x48\x49\x4A\x53\x54\x55\x56\x57\x58\x59\x5A\x63\x64\x65\x66\x67\x68\x69\x6A\x73\x74\x75\x76\x77\x78\x79\x7A\x83\x84\x85\x86\x87\x88\x89\x8A\x92\x93\x94\x95\x96\x97\x98\x99\x9A\xA2\xA3\xA4\xA5\xA6\xA7\xA8\xA9\xAA\xB2\xB3\xB4\xB5\xB6\xB7\xB8\xB9\xBA\xC2\xC3\xC4\xC5\xC6\xC7\xC8\xC9\xCA\xD2\xD3\xD4\xD5\xD6\xD7\xD8\xD9\xDA\xE1\xE2\xE3\xE4\xE5\xE6\xE7\xE8\xE9\xEA\xF1\xF2\xF3\xF4\xF5\xF6\xF7\xF8\xF9\xFA\xFF\xDA\x00\x08\x01\x01\x00\x00\x3F\x00\x7B\x94\x11\x5A\x0D\x5E\x80\x1D\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\x00\x14\x51\x45\xFF\xD9");
        $originalName = 'photo.jpg';

        $dto = new \Scriptlog\Dto\UploadedFileDto([
            'tmp_name' => $tmpFile,
            'type' => 'image/jpeg',
            'name' => $originalName,
            'size' => filesize($tmpFile),
            'error' => UPLOAD_ERR_OK,
        ]);

        $result = $this->validator->validate($dto);
        unlink($tmpFile);

        $this->assertTrue($result->isValid());
    }

    public function testValidateFailsWithArrayError(): void
    {
        $dto = new \Scriptlog\Dto\UploadedFileDto([
            'error' => UPLOAD_ERR_OK,
        ]);
        $dto->error = [UPLOAD_ERR_OK];

        $ref = new ReflectionClass($dto);
        $prop = $ref->getProperty('error');
        $prop->setAccessible(true);
        $prop->setValue($dto, [UPLOAD_ERR_OK]);

        $result = $this->validator->validate($dto);
        $this->assertFalse($result->isValid());
    }

    public function testValidateFailsWithIniSizeError(): void
    {
        $dto = new \Scriptlog\Dto\UploadedFileDto([
            'tmp_name' => '',
            'type' => '',
            'name' => '',
            'size' => 0,
            'error' => UPLOAD_ERR_INI_SIZE,
        ]);

        $result = $this->validator->validate($dto);
        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('filesize', implode(' ', $result->getErrors()));
    }

    public function testValidateFailsWithFormSizeError(): void
    {
        $dto = new \Scriptlog\Dto\UploadedFileDto([
            'tmp_name' => '',
            'type' => '',
            'name' => '',
            'size' => 0,
            'error' => UPLOAD_ERR_FORM_SIZE,
        ]);

        $result = $this->validator->validate($dto);
        $this->assertFalse($result->isValid());
    }

    public function testValidateFailsWithUnknownError(): void
    {
        $dto = new \Scriptlog\Dto\UploadedFileDto([
            'tmp_name' => '',
            'type' => '',
            'name' => '',
            'size' => 0,
            'error' => UPLOAD_ERR_PARTIAL,
        ]);

        $result = $this->validator->validate($dto);
        $this->assertFalse($result->isValid());
    }
}
