<?php

/**
 * call_htmlpurifier
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return string
 *
 */
function call_htmlpurifier()
{
    // REM-7: idempotent loader. The first call registers the HTMLPurifier
    // autoloader; repeat calls within the same request are free no-ops.
    if (defined('SCRIPTLOG_HTMLPURIFIER_LOADED')) {
        return true;
    }

    $call_htmlpurifier = require_once __DIR__ . '/../../lib/core/HTMLPurifier.auto.php';

    define('SCRIPTLOG_HTMLPURIFIER_LOADED', true);

    return $call_htmlpurifier;
}
