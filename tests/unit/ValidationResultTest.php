<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class ValidationResultTest extends TestCase
{
    public function testSuccessFactoryReturnsValidResult(): void
    {
        $result = \Scriptlog\Validator\ValidationResult::success();
        $this->assertTrue($result->isValid());
        $this->assertFalse($result->hasErrors());
        $this->assertEmpty($result->getErrors());
    }

    public function testFailureFactoryReturnsInvalidResult(): void
    {
        $result = \Scriptlog\Validator\ValidationResult::failure('Something went wrong');
        $this->assertFalse($result->isValid());
        $this->assertTrue($result->hasErrors());
        $this->assertEquals(['Something went wrong'], $result->getErrors());
    }

    public function testAddErrorMarksInvalid(): void
    {
        $result = \Scriptlog\Validator\ValidationResult::success();
        $result->addError('Error 1');
        $this->assertFalse($result->isValid());
        $this->assertCount(1, $result->getErrors());
    }

    public function testAddErrorMultipleErrors(): void
    {
        $result = \Scriptlog\Validator\ValidationResult::success();
        $result->addError('Error 1');
        $result->addError('Error 2');
        $result->addError('Error 3');
        $this->assertCount(3, $result->getErrors());
        $this->assertEquals(['Error 1', 'Error 2', 'Error 3'], $result->getErrors());
    }

    public function testAddErrorReturnsSelfForChaining(): void
    {
        $result = \Scriptlog\Validator\ValidationResult::success();
        $returned = $result->addError('Test');
        $this->assertSame($result, $returned);
    }

    public function testMergeCombinesErrors(): void
    {
        $result1 = \Scriptlog\Validator\ValidationResult::success();
        $result1->addError('Error A');

        $result2 = \Scriptlog\Validator\ValidationResult::failure('Error B');

        $result1->merge($result2);
        $this->assertFalse($result1->isValid());
        $this->assertEquals(['Error A', 'Error B'], $result1->getErrors());
    }

    public function testMergeWithEmptyResult(): void
    {
        $result1 = \Scriptlog\Validator\ValidationResult::failure('Main error');
        $empty = \Scriptlog\Validator\ValidationResult::success();

        $result1->merge($empty);
        $this->assertFalse($result1->isValid());
        $this->assertEquals(['Main error'], $result1->getErrors());
    }

    public function testHasErrorsWhenNoErrors(): void
    {
        $result = \Scriptlog\Validator\ValidationResult::success();
        $this->assertFalse($result->hasErrors());
    }

    public function testHasErrorsWhenErrorsExist(): void
    {
        $result = \Scriptlog\Validator\ValidationResult::failure('Error');
        $this->assertTrue($result->hasErrors());
    }

    public function testIsValidWhenValid(): void
    {
        $result = \Scriptlog\Validator\ValidationResult::success();
        $this->assertTrue($result->isValid());
    }

    public function testIsValidWhenInvalid(): void
    {
        $result = \Scriptlog\Validator\ValidationResult::failure('Error');
        $this->assertFalse($result->isValid());
    }
}
