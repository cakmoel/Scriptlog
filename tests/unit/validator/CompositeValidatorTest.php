<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;
use Scriptlog\Dto\PostRequestDto;
use Scriptlog\Validator\CompositeValidator;
use Scriptlog\Validator\ValidationResult;

class CompositeValidatorTest extends TestCase
{
    public function testEmptyValidatorPasses(): void
    {
        $composite = new CompositeValidator();
        $dto = new PostRequestDto([], []);
        $result = $composite->validate($dto);
        $this->assertTrue($result->isValid());
        $this->assertSame(0, $composite->count());
    }

    public function testSingleValidatorPasses(): void
    {
        $composite = new CompositeValidator();
        $composite->add(function (PostRequestDto $dto) {
            return ValidationResult::success();
        });

        $dto = new PostRequestDto([], []);
        $result = $composite->validate($dto);
        $this->assertTrue($result->isValid());
        $this->assertSame(1, $composite->count());
    }

    public function testAnyFailureCausesOverallFailure(): void
    {
        $composite = new CompositeValidator();
        $composite->add(function (PostRequestDto $dto) {
            return ValidationResult::success();
        });
        $composite->add(function (PostRequestDto $dto) {
            return ValidationResult::failure('Second validator failed');
        });

        $dto = new PostRequestDto([], []);
        $result = $composite->validate($dto);
        $this->assertFalse($result->isValid());
        $this->assertContains('Second validator failed', $result->getErrors());
    }

    public function testMultipleValidatorsMergeAllErrors(): void
    {
        $composite = new CompositeValidator();
        $composite->add(function (PostRequestDto $dto) {
            $r = ValidationResult::success();
            $r->addError('Error from first');
            return $r;
        });
        $composite->add(function (PostRequestDto $dto) {
            $r = ValidationResult::success();
            $r->addError('Error from second');
            return $r;
        });

        $dto = new PostRequestDto([], []);
        $result = $composite->validate($dto);
        $this->assertFalse($result->isValid());
        $this->assertCount(2, $result->getErrors());
    }
}