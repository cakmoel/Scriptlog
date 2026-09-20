<?php

/**
 * unreg_globals()
 *
 * Emulate register_globals off
 *
 * @category function
 * @return void
 *
 */
function unreg_globals()
{
    // register_globals was removed in PHP 5.4; nothing to emulate on newer versions
    if (PHP_VERSION_ID >= 50400) {
        return;
    }

    if (!ini_get('register_globals')) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.register_globalsDeprecatedRemoved
        return;
    }

    if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
        scriptlog_error("GLOBALS overwrite attempt detected");
    }

    // variable that should not be unset
    $no_unset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

    $input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());

    foreach ($input as $k => $v) {
        if (!in_array($k, $no_unset) && isset($GLOBALS[$k])) {
            unset($GLOBALS[$k]);
        }
    }
}
