<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class CompositeValidatorTest extends TestCase
{
    public function testCountReturnsZeroInitially(): void
    {
        $composite = new \Scriptlog\Validator\CompositeValidator();
        $this->assertEquals(0, $composite->count());
    }

    public function testAddIncrementsCount(): void
    {
        $composite = new \Scriptlog\Validator\CompositeValidator();
        $composite->add(function ($dto) {
            return \Scriptlog\Validator\ValidationResult::success();
        });
        $this->assertEquals(1, $composite->count());
    }

    public function testAddMultipleValidators(): void
    {
        $composite = new \Scriptlog\Validator\CompositeValidator();
        $composite->add(function ($dto) {
            return \Scriptlog\Validator\ValidationResult::success();
        });
        $composite->add(function ($dto) {
            return \Scriptlog\Validator\ValidationResult::success();
        });
        $composite->add(function ($dto) {
            return \Scriptlog\Validator\ValidationResult::success();
        });
        $this->assertEquals(3, $composite->count());
    }

    public function testAddReturnsSelfForChaining(): void
    {
        $composite = new \Scriptlog\Validator\CompositeValidator();
        $returned = $composite->add(function ($dto) {
            return \Scriptlog\Validator\ValidationResult::success();
        });
        $this->assertSame($composite, $returned);
    }

    public function testValidateReturnsSuccessWhenAllPass(): void
    {
        $composite = new \Scriptlog\Validator\CompositeValidator();
        $composite->add(function ($dto) {
            return \Scriptlog\Validator\ValidationResult::success();
        });
        $composite->add(function ($dto) {
            return \Scriptlog\Validator\ValidationResult::success();
        });

        $dto = new \Scriptlog\Dto\PostRequestDto([], []);
        $result = $composite->validate($dto);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    public function testValidateCollectsAllErrors(): void
    {
        $composite = new \Scriptlog\Validator\CompositeValidator();
        $composite->add(function ($dto) {
            return \Scriptlog\Validator\ValidationResult::success();
        });
        $composite->add(function ($dto) {
            return \Scriptlog\Validator\ValidationResult::failure('Error from second');
        });
        $composite->add(function ($dto) {
            return \Scriptlog\Validator\ValidationResult::failure('Error from third');
        });

        $dto = new \Scriptlog\Dto\PostRequestDto([], []);
        $result = $composite->validate($dto);

        $this->assertFalse($result->isValid());
        $this->assertCount(2, $result->getErrors());
        $this->assertEquals(['Error from second', 'Error from third'], $result->getErrors());
    }

    public function testValidateWithNoValidators(): void
    {
        $composite = new \Scriptlog\Validator\CompositeValidator();

        $dto = new \Scriptlog\Dto\PostRequestDto([], []);
        $result = $composite->validate($dto);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }
}
