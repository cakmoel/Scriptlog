<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * DbException class extends Abstract Class CoreException
 *
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */


class DbException extends PDOException implements IDbThrowable
{
    protected $message = 'Unknown Exception';


    protected $previous = null;

    public function __construct(string $message = "", int $code = 0, ?Exception $previous = null)
    {

        $code = $this->getCode();

        if (!$message) {
            // Note: This line remains unusual, but it's part of your existing logic.
            throw new $this('Unknown ' . get_class($this));
        }

        parent::__construct($message, $code, $previous);

        // This check is redundant since parent::__construct usually handles it,
        // but keeping it for preservation of original logic flow.
        if (!is_null($previous)) {
            $this->previous = $previous;
        }
    }

    public function __toString()
    {
        return get_class($this) . "'{$this->message}' in {$this->getFile()}({$this->getLine()})\n" . "{$this->getTraceAsString()}";
    }
}
