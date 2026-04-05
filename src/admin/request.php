<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * request.php - Refactored for AppContext
 */

$load = null;
$current_request = current_request_method();
$method_allowed = ['GET', 'POST'];

try {
    if (isset($_GET['load']) && !empty($_GET['load'])) {
        // Sanitize and validate input
        $load = filter_input(INPUT_GET, 'load', FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        $load = preg_replace('/[^a-z0-9,_-]/i', '', $load);
        $load = escape_null_byte($load);

        if (empty($load)) {
            throw new AppException("Invalid request parameter");
        }

        if (strpos($load, '..') !== false) {
            throw new AppException("Directory traversal attempt detected");
        }

        $disallowed_schemes = ['http://', 'file://', 'data:', 'zip://', 'php://'];
        foreach ($disallowed_schemes as $scheme) {
            if (stripos($load, $scheme) !== false) {
                throw new AppException("Invalid scheme detected in request");
            }
        }

        $file_path = dirname(dirname(__FILE__)) . DS . APP_ADMIN . DS . "{$load}.php";

        if (!is_readable($file_path) || !in_array($load, array_keys(admin_query()))) {
            direct_page('index.php?load=404&notfound=' . notfound_id(), 404);
        } else {
            if (!$app->authenticator->userAccessControl()) {
                http_response_code(403);
                throw new AppException("403 - Forbidden");
            }

            if (block_request_type($current_request, $method_allowed)) {
                http_response_code(405);
                throw new AppException("405 - Method Not Allowed");
            }

            if (function_exists('realpath')) {
                require realpath($file_path);
            } else {
                require basename(absolute_path($file_path));
            }
        }
    } else {
        if (!$app->authenticator->userAccessControl()) {
            http_response_code(403);
            throw new AppException("403 - Forbidden");
        }

        if (block_request_type($current_request, $method_allowed)) {
            http_response_code(405);
            throw new AppException("405 - Method Not Allowed");
        }

        direct_page('index.php?load=dashboard', 302);
    }
} catch (AppException $e) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);
} catch (\Throwable $th) {
    if (class_exists('LogError')) {
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);
    }
}

// FIXED: Use $app->ubench
if (isset($app->ubench) && APP_DEVELOPMENT === true) {
    $app->ubench->end();
}
