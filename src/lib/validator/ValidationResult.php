<?php
namespace Scriptlog\Validator;
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Value object carrying validation result.
 *
 * Replaces the ($checkError, $errors) tuple pattern used
 * throughout controllers with a single immutable result.
 *
 * @category Validator
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 */
class ValidationResult
{
    /** @var array */
    private $errors = [];

    /** @var bool */
    private $valid = true;

    /**
     * Add a single error message.
     *
     * @param string $message
     * @return self
     */
    public function addError($message)
    {
        $this->errors[] = $message;
        $this->valid = false;
        return $this;
    }

    /**
     * Merge another ValidationResult into this one.
     *
     * @param ValidationResult $other
     * @return self
     */
    public function merge(ValidationResult $other)
    {
        foreach ($other->getErrors() as $error) {
            $this->addError($error);
        }
        return $this;
    }

    /**
     * Whether validation passed.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Return all error messages.
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Factory: successful validation.
     *
     * @return self
     */
    public static function success()
    {
        return new self();
    }

    /**
     * Factory: failed validation with one message.
     *
     * @param string $message
     * @return self
     */
    public static function failure($message)
    {
        $result = new self();
        $result->addError($message);
        return $result;
    }

    /**
     * Whether there are any errors.
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }
}
