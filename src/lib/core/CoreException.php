<?php defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * CoreException Class extends Exception implements ICoreThrowable
 *
 * @category  Core Class
 * @link      https://secure.php.net/manual/en/language.exceptions.php#91159
 * @see       https://www.php.net/manual/en/language.exceptions.extending.php
 * @version   1.0
 * @since     Since Release 1.0
 */

use Exception;

class CoreException extends Exception implements ICoreThrowable
{
    /**
     * Previous exception
     * @var Exception|null
     */
    protected ?Exception $previous = null;

    /**
     * CoreException constructor
     *
     * @param string|null $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct(?string $message = null, int $code = 0, ?Exception $previous = null)
    {
        if (is_null($message)) {
            $message = 'Unknown ' . get_class($this);
        }

        parent::__construct($message, $code, $previous);
        
        $this->previous = $previous;
    }

    /**
     * Convert Exception to String
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf(
            "%s: '%s' in %s(%d)\n%s",
            get_class($this),
            $this->getMessage(),
            $this->getFile(),
            $this->getLine(),
            $this->getTraceAsString()
        );
    }
}
