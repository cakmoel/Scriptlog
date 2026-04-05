<?php

/**
 * download_file.php
 *
 * Handles actual file download delivery
 *
 * @category Theme Template
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 *
 */

$identifier = $requestPath->identifier ?? '';

if (empty($identifier)) {
    http_response_code(400);
    echo 'Invalid download request';
    exit;
}

$downloadController = new DownloadController(new DownloadService(new DownloadModel(), new MediaDao()));
$downloadController->download($identifier);
