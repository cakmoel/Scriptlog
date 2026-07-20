<?php
namespace Scriptlog\Validator;
defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Dto\UploadedFileDto;

/**
 * Validates uploaded file data.
 *
 * Pure validation rules for file uploads. No $_FILES dependency.
 *
 * @category Validator
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 */
class FileUploadValidator
{
    /**
     * Validate an uploaded file.
     *
     * @param UploadedFileDto|null $file  Null means no file was submitted (passes validation)
     * @return ValidationResult
     */
    public function validate($file)
    {
        if ($file === null) {
            return ValidationResult::success();
        }

        $result = ValidationResult::success();

        if (is_array($file->error)) {
            return ValidationResult::failure("Invalid parameter");
        }

        switch ($file->error) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ValidationResult::failure("Exceeded filesize limit");
            default:
                return ValidationResult::failure("Unknown errors");
        }

        if ($file->size > APP_FILE_SIZE) {
            $result->addError(
                "Exceeded file size limit. Maximum file size is. "
                . format_size_unit(APP_FILE_SIZE)
            );
        }

        if (false === check_file_name($file->tmpName)) {
            $result->addError("File name is not valid");
        }

        if (true === check_file_length($file->tmpName)) {
            $result->addError("File name is too long");
        }

        if ((false === check_mime_type(mime_type_dictionary(), $file->tmpName))
            || (false === check_file_extension($file->name))
        ) {
            $result->addError("Invalid file format");
        }

        return $result;
    }
}
