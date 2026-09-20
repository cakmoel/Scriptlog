<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;
use Scriptlog\Dto\PostRequestDto;
use Scriptlog\Validator\ProtectedPostValidator;

class ProtectedPostValidatorTest extends TestCase
{
    public function testNonProtectedPasses(): void
    {
        $dto = new PostRequestDto(['visibility' => 'public'], []);
        $validator = new ProtectedPostValidator();
        $result = $validator->validate($dto);
        $this->assertTrue($result->isValid());
    }

    public function testProtectedWithEmptyPasswordPasses(): void
    {
        $dto = new PostRequestDto(['visibility' => 'protected', 'post_password' => ''], []);
        $validator = new ProtectedPostValidator();
        $result = $validator->validate($dto);
        $this->assertTrue($result->isValid());
    }

    public function testWeakPasswordFails(): void
    {
        $dto = new PostRequestDto(['visibility' => 'protected', 'post_password' => 'weak'], []);
        $validator = new ProtectedPostValidator();
        $result = $validator->validate($dto);
        $this->assertFalse($result->isValid());
    }

    public function testStrongPasswordPasses(): void
    {
        $dto = new PostRequestDto(['visibility' => 'protected', 'post_password' => 'Str0ng!Pass#'], []);
        $validator = new ProtectedPostValidator();
        $result = $validator->validate($dto);
        $this->assertTrue($result->isValid());
    }
}