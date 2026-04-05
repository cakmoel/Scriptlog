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

    // Load the .env file if it exists
    if (file_exists(APP_ROOT . '.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
        $dotenv->load();
    }
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
    require __DIR__ . '/core/Bootstrap.php';
}


if (!file_exists(APP_ROOT . 'config.php')) {
    if (is_dir(APP_ROOT . 'install')) {
        header("Location: " . APP_PROTOCOL . "://" . APP_HOSTNAME . dirname(dirname(htmlspecialchars($_SERVER['PHP_SELF']))) . DS . 'install');
        exit();
    }
} else {
    $app = Bootstrap::initialize(APP_ROOT);

    // Note: The Security functions (x_frame_option, etc.) are now called inside Bootstrap::applySecurity()
}

// Session setup now uses the $app object instead of $sessionMaker
if (isset($app->sessionMaker)) {
    session_save_path(sys_get_temp_dir());

    session_set_save_handler($app->sessionMaker, true);
    register_shutdown_function('session_write_close');

    if (function_exists('start_session_on_site')) {
        start_session_on_site($app->sessionMaker);
    }
}
