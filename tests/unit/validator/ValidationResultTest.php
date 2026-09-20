<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;
use Scriptlog\Validator\ValidationResult;

class ValidationResultTest extends TestCase
{
    public function testSuccessIsValid(): void
    {
        $result = ValidationResult::success();
        $this->assertTrue($result->isValid());
    }

    public function testFailureIsNotValid(): void
    {
        $result = ValidationResult::failure('Something went wrong');
        $this->assertFalse($result->isValid());
    }

    public function testAddErrorCollects(): void
    {
        $result = ValidationResult::success();
        $result->addError('Error one');
        $result->addError('Error two');
        $errors = $result->getErrors();
        $this->assertCount(2, $errors);
        $this->assertContains('Error one', $errors);
        $this->assertContains('Error two', $errors);
    }

    public function testMergeCombinesErrors(): void
    {
        $first = ValidationResult::success();
        $first->addError('First error');

        $second = ValidationResult::success();
        $second->addError('Second error');

        $first->merge($second);

        $errors = $first->getErrors();
        $this->assertCount(2, $errors);
        $this->assertContains('First error', $errors);
        $this->assertContains('Second error', $errors);
    }

    public function testHasErrors(): void
    {
        $success = ValidationResult::success();
        $this->assertFalse($success->hasErrors());

        $failure = ValidationResult::failure('Error');
        $this->assertTrue($failure->hasErrors());
    }
}
