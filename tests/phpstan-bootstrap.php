<?php
/**
 * PHPStan Bootstrap
 *
 * Loads composer autoloader and creates global class aliases
 * by loading each namespaced class first via class_exists().
 */
if (!defined('SCRIPTLOG')) {
    define('SCRIPTLOG', 'PHPStanBootstrap');
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('APP_LIBRARY')) {
    define('APP_LIBRARY', 'lib');
}

$autoload = __DIR__ . '/../lib/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

function_exists('t') || require __DIR__ . '/../public/themes/blog/functions.php';

$mapFile = __DIR__ . '/../lib/autoload-aliases-map.php';
if (file_exists($mapFile)) {
    $aliasMap = require $mapFile;
    foreach ($aliasMap as $globalName => $namespacedName) {
        if (!class_exists($globalName, false) && class_exists($namespacedName)) {
            class_alias($namespacedName, $globalName);
        }
    }
}
