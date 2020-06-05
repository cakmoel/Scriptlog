<?php
/**
 * unreg_globals function
 * Emulate register_globals off
 *
 * @return void
 */
function unreg_globals()
{

if(!ini_get('register_globals')) {

  return;

}

if(isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {

  scriptlog_error("GLOBALS overwrite attempt detected");

}

// variable that should not be unset
$no_unset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

$input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, 
                    $_ENV, $_FILES, 
                    isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());


foreach($input as $k => $v) {

    if(!in_array($k, $no_unset) && isset($GLOBALS[$k])) {

        unset($GLOBALS[$k]);

    }

}

}