<?php
namespace Scriptlog\Validator;
defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Dto\PostRequestDto;

/**
 * Aggregate validator that runs multiple sub-validators.
 *
 * Iterates over a list of callables, each receiving the PostRequestDto,
 * and merges all ValidationResult objects into one.
 *
 * @category Validator
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 */
class CompositeValidator
{
    /** @var callable[] */
    private $validators = [];

    /**
     * Register a validator callable.
     *
     * @param callable $validator  Receives PostRequestDto, returns ValidationResult
     * @return self
     */
    public function add(callable $validator)
    {
        $this->validators[] = $validator;
        return $this;
    }

    /**
     * Run all registered validators and merge results.
     *
     * @param PostRequestDto $dto
     * @return ValidationResult
     */
    public function validate(PostRequestDto $dto)
    {
        $result = ValidationResult::success();

        foreach ($this->validators as $validator) {
            /** @var ValidationResult $subResult */
            $subResult = call_user_func($validator, $dto);
            $result->merge($subResult);
        }

        return $result;
    }

    /**
     * Return the number of registered validators.
     *
     * @return int
     */
    public function count()
    {
        return count($this->validators);
    }
}