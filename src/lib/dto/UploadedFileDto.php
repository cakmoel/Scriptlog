<?php
namespace Scriptlog\Dto;
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Value object for a single uploaded file.
 *
 * Encapsulates $_FILES array entry with validation helpers.
 *
 * @category DTO
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 */
class UploadedFileDto
{
    /** @var string */
    public $tmpName;

    /** @var string */
    public $type;

    /** @var string */
    public $name;

    /** @var int */
    public $size;

    /** @var int */
    public $error;

    public function __construct(array $file)
    {
        $this->tmpName = isset($file['tmp_name']) ? $file['tmp_name'] : '';
        $this->type = isset($file['type']) ? $file['type'] : '';
        $this->name = isset($file['name']) ? $file['name'] : '';
        $this->size = isset($file['size']) ? (int)$file['size'] : 0;
        $this->error = isset($file['error']) ? (int)$file['error'] : UPLOAD_ERR_NO_FILE;
    }

    /**
     * Factory method from $_FILES global.
     *
     * @return self
     */
    public static function fromGlobals()
    {
        $file = isset($_FILES['media']) ? $_FILES['media'] : [];
        return new self($file);
    }

    /**
     * Check whether the file was uploaded successfully.
     *
     * @return bool
     */
    public function isValid()
    {
        return ($this->error === UPLOAD_ERR_OK && !empty($this->tmpName));
    }

    /**
     * Check whether an upload error occurred.
     *
     * @return bool
     */
    public function hasUploadError()
    {
        return ($this->error !== UPLOAD_ERR_OK);
    }

    /**
     * Return the file extension.
     *
     * @return string
     */
    public function getExtension()
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    /**
     * Generate safe filename array using existing utility.
     *
     * @return array
     */
    public function getGeneratedFilename()
    {
        return generate_filename($this->name);
    }
}
