<?php

// lib/main.php

(version_compare(PHP_VERSION, '7.4', '>=')) ? clearstatcache() : clearstatcache(true);

include __DIR__ . '/options.php';
require __DIR__ . '/common.php';

/**
 * Early 404 Short-Circuit
 * 
 * Quick URL pattern check BEFORE loading autoloader and full bootstrap.
 * This significantly reduces 404 response time from ~500ms to <50ms.
 * 
 * Supports both SEO-friendly URLs and query string URLs (permalinks disabled).
 */
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestPath = parse_url($requestUri, PHP_URL_PATH);
$queryString = parse_url($requestUri, PHP_URL_QUERY);

$knownQueryParams = ['p', 'pg', 'cat', 'tag', 'a', 'search'];

if (!empty($queryString)) {
    parse_str($queryString, $parsedQuery);
    foreach ($knownQueryParams as $param) {
        if (isset($parsedQuery[$param]) && !empty($parsedQuery[$param])) {
            $isValidRoute = true;
            break;
        }
    }
    if (!isset($isValidRoute)) {
        $isValidRoute = true; // Let dispatcher handle query strings
    }
} elseif (!empty($requestPath) && $requestPath !== '/') {
    $knownPatterns = [
        '/^$/D', '/^\/admin/i', '/^\/api/i', '/^\/install/i',
        '/^\/category\/[\w\-]+$/D', '/^\/archive(\/[\d]{2}\/[\d]{4})?$/D',
        '/^\/archives$/D', '/^\/blog(\/page\/[\d]+)?$/D',
        '/^\/page\/[\w\-]+$/D', '/^\/post\/[\d]+\/[\w\-]+$/D',
        '/^\/tag\/[\w\-\s]+$/D', '/^\/privacy$/D',
        '/^\/download\/[a-f0-9\-]+(\/file)?$/D',
        '/^\/themes/i', '/^\/files/i', '/^\/rss\.php$/i', '/^\/atom\.php$/i',
    ];
    $isValidRoute = false;
    foreach ($knownPatterns as $pattern) {
        if (preg_match($pattern, $requestPath)) {
            $isValidRoute = true;
            break;
        }
    }
} else {
    $isValidRoute = true;
}

if (isset($isValidRoute) && !$isValidRoute) {
    http_response_code(404);
    die('404 Not Found');
}

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

// Handle frontend language switch
if (isset($_GET['switch-lang']) && !empty($_GET['switch-lang'])) {
    $langCode = preg_replace('/[^a-z]{2}/', '', strtolower($_GET['switch-lang']));
    $validLocales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
    
    if (in_array($langCode, $validLocales)) {
        $_SESSION['scriptlog_locale'] = $langCode;
        setcookie('scriptlog_locale', $langCode, time() + (86400 * 365), '/');
        
        // Redirect to remove switch-lang from URL
        $redirectUrl = $_GET['redirect'] ?? '/';
        if (!empty($_GET['redirect'])) {
            header("Location: " . urldecode($_GET['redirect']));
        } else {
            // Remove switch-lang param and redirect to same page
            $urlParts = parse_url($_SERVER['REQUEST_URI']);
            $path = $urlParts['path'] ?? '/';
            $query = [];
            if (isset($urlParts['query'])) {
                parse_str($urlParts['query'], $query);
                unset($query['switch-lang']);
            }
            $newQuery = !empty($query) ? '?' . http_build_query($query) : '';
            header("Location: " . $path . $newQuery);
        }
        exit();
    }
}
