<?php
namespace Scriptlog\Validator;
defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Dto\PostRequestDto;

/**
 * Validates post form field values.
 *
 * Pure validation rules with no HTTP or database dependencies.
 * Can be tested without a web server or database connection.
 *
 * @category Validator
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 */
class PostValidator
{
    /** @var array Field length limits */
    private $formFieldLimits = [
        'post_title' => 200,
        'post_summary' => 320,
        'post_tags' => 200,
        'post_content' => 500000
    ];

    /**
     * Validate post data.
     *
     * @param PostRequestDto $dto
     * @return ValidationResult
     */
    public function validate(PostRequestDto $dto)
    {
        $result = ValidationResult::success();

        // Required fields
        if (empty($dto->postTitle) || empty($dto->postContent)) {
            $result->addError("Please enter a required field");
        }

        // Field length limits
        if (true === form_size_validation($this->formFieldLimits)) {
            $result->addError("Form data is longer than allowed");
        }

        // Status validation
        if (!in_array($dto->postStatus, ['publish', 'draft'])) {
            $result->addError(MESSAGE_INVALID_SELECTBOX);
        }

        // Comment status validation
        if (!in_array($dto->commentStatus, ['open', 'closed'])) {
            $result->addError(MESSAGE_INVALID_SELECTBOX);
        }

        // Visibility validation
        if (!in_array($dto->visibility, ['public', 'private', 'protected'])) {
            $result->addError(MESSAGE_INVALID_SELECTBOX);
        }

        // Date validation
        if (!empty($dto->postDate) && validate_date($dto->postDate) === false) {
            $result->addError("Please fix your date format");
        }

        // Modified date validation
        if (!empty($dto->postModified) && validate_date($dto->postModified) === false) {
            $result->addError("Please fix your date format");
        }

        return $result;
    }
}
