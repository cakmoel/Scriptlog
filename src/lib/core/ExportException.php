<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class ExportException
 *
 * Custom exception for export operations
 *
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ExportException extends CoreException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
