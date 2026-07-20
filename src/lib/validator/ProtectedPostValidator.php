<?php
namespace Scriptlog\Validator;
defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Dto\PostRequestDto;

/**
 * Validates password for protected posts.
 *
 * Runs password strength checks when the DTO indicates a password-protected post.
 * Passes validation silently for non-protected posts.
 *
 * @category Validator
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 */
class ProtectedPostValidator
{
    /**
     * Validate the password for a protected post.
     *
     * @param PostRequestDto $dto
     * @return ValidationResult
     */
    public function validate(PostRequestDto $dto)
    {
        if (!$dto->isProtected() || empty($dto->postPassword)) {
            return ValidationResult::success();
        }

        $result = ValidationResult::success();
        $password = $dto->postPassword;

        if (check_common_password($password) === true) {
            $result->addError(
                "Your password seems to be the most hacked password, please try another"
            );
        }

        if (false === check_pwd_strength($password)) {
            $result->addError(MESSAGE_WEAK_PASSWORD);
        }

        return $result;
    }
}
