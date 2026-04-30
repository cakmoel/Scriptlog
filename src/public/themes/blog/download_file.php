<?php

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * download_file.php
 *
 * Handles actual file download delivery
 *
 * This file is NOT a theme template - it is handled specially by the Dispatcher
 * and bypasses theme header/footer rendering.
 *
 * @category Download Handler
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release1.0
 */

// Support both SEO-friendly and query string URLs
$identifier = '';

if (function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes') {
    // SEO-friendly URL: /download/{identifier}/file
    $identifier = $requestPath->identifier ?? '';
} else {
    // Query string URL: ?download={identifier}&file=1
    if (class_exists('HandleRequest')) {
        $qs = HandleRequest::isQueryStringRequested();
        if (isset($qs['key']) && $qs['key'] === 'download') {
            $identifier = $qs['value'] ?? '';
        }
    }
}

// Also check GET parameter as fallback
if (empty($identifier) && isset($_GET['identifier'])) {
    $identifier = $_GET['identifier'];
}

if (empty($identifier)) {
    http_response_code(400);
    echo 'Invalid download request';
    exit;
}

$downloadController = new DownloadController(new DownloadService(new DownloadModel(), new MediaDao()));
$downloadController->download($identifier);
