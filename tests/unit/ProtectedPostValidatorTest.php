<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class ProtectedPostValidatorTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new \Scriptlog\Validator\ProtectedPostValidator();
    }

    public function testValidatePassesForNonProtectedPost(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto(
            ['visibility' => 'public'],
            []
        );
        $result = $this->validator->validate($dto);
        $this->assertTrue($result->isValid());
    }

    public function testValidatePassesForProtectedWithEmptyPassword(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto(
            ['visibility' => 'protected', 'post_password' => ''],
            []
        );
        $result = $this->validator->validate($dto);
        $this->assertTrue($result->isValid());
    }

    public function testValidateFailsForCommonPassword(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto(
            ['visibility' => 'protected', 'post_password' => 'password'],
            []
        );
        $result = $this->validator->validate($dto);
        $this->assertFalse($result->isValid());
    }

    public function testValidateFailsForWeakPassword(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto(
            ['visibility' => 'protected', 'post_password' => 'abc'],
            []
        );
        $result = $this->validator->validate($dto);
        $this->assertFalse($result->isValid());
    }

    public function testValidatePassesForStrongPassword(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto(
            ['visibility' => 'protected', 'post_password' => 'Str0ng!P@ssw0rd#2026'],
            []
        );
        $result = $this->validator->validate($dto);
        $this->assertTrue($result->isValid());
    }

    public function testValidateReturnsMultipleErrorsForWeakCommon(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto(
            ['visibility' => 'protected', 'post_password' => 'password'],
            []
        );
        $result = $this->validator->validate($dto);
        $this->assertTrue($result->hasErrors());
    }
}
