<?php
// lib/main.php

(version_compare(PHP_VERSION, '7.4', '>=')) ? clearstatcache() : clearstatcache(true);

include __DIR__ . '/options.php';
require __DIR__ . '/common.php';

if (file_exists(APP_ROOT . APP_LIBRARY . DIRECTORY_SEPARATOR . 'Autoloader.php')) {
    require __DIR__ . DIRECTORY_SEPARATOR . 'Autoloader.php';
}

if (is_readable(APP_ROOT . APP_LIBRARY . DIRECTORY_SEPARATOR . 'vendor/autoload.php')) {
    require __DIR__ . DIRECTORY_SEPARATOR . 'vendor/autoload.php';
}

if (class_exists('Autoloader')) {
    Autoloader::setBaseDir(APP_ROOT);
    // Configure Autoloader paths
    Autoloader::addClassDir(array(
        APP_LIBRARY . DIRECTORY_SEPARATOR . 'core'       . DIRECTORY_SEPARATOR,
        APP_LIBRARY . DIRECTORY_SEPARATOR . 'dao'        . DIRECTORY_SEPARATOR,
        APP_LIBRARY . DIRECTORY_SEPARATOR . 'service'    . DIRECTORY_SEPARATOR,
        APP_LIBRARY . DIRECTORY_SEPARATOR . 'controller' . DIRECTORY_SEPARATOR,
        APP_LIBRARY . DIRECTORY_SEPARATOR . 'model'      . DIRECTORY_SEPARATOR
    ));
    require __DIR__ . '/core/Bootstrap.php'; // Now load the main class
} else {
    // Fallback if custom autoloader is missing, require Bootstrap directly
    require __DIR__ . '/core/Bootstrap.php'; 
}


if (!file_exists(APP_ROOT . 'config.php')) {

    if (is_dir(APP_ROOT . 'install')) {

        header("Location: " . APP_PROTOCOL . "://" . APP_HOSTNAME . dirname(dirname(htmlspecialchars($_SERVER['PHP_SELF']))) . DS . 'install');
        exit();
    }
    
} else {


    $vars = Bootstrap::initialize(APP_ROOT);

    // This injects $db_host, $app_url, $authenticator, $ubench, etc., back into the global scope.
    if (!empty($vars)) {
        extract($vars);
    }
    
    // Note: The Security functions (x_frame_option, etc.) are now called inside Bootstrap::applySecurity()
}


// Session setup must remain here, using the $sessionMaker object extracted above.
if (isset($sessionMaker)) {
    session_save_path(sys_get_temp_dir());
    
    session_set_save_handler($sessionMaker, true);
    register_shutdown_function('session_write_close');
    
    if (function_exists('start_session_on_site')) {
        start_session_on_site($sessionMaker);
    }
}
