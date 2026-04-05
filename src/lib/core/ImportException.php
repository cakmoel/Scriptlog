<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class ImportException
 *
 * Custom exception for import operations
 *
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ImportException extends CoreException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
