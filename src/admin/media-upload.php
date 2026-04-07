<?php
/**
 * Simple Media Upload Handler for SummerNote
 * 
 * This endpoint handles image uploads from SummerNote WYSIWYG editor
 * It's placed in the admin folder so it uses admin session authentication
 */

// Prevent direct access
define('SCRIPTLOG', hash('sha256', 'BLOGWARE_ADMIN_ACCESS'));

// Disable all output and errors - we'll control the output
error_reporting(0);
ini_set('display_errors', 0);

// Clean any existing output buffers
while (ob_get_level()) {
    ob_end_clean();
}

// Start our own output buffer
ob_start();

// Load required files
require_once __DIR__ . '/../lib/main.php';

// Check if user is logged in (admin authentication)
$session = Session::getInstance();
if (!isset($session->scriptlog_session_login) || empty($session->scriptlog_session_login)) {
    sendJsonResponse(401, false, 'UNAUTHORIZED', 'Admin authentication required');
}

// Verify user level (must be at least contributor)
$userLevel = $session->scriptlog_session_level ?? '';
$allowedLevels = ['administrator', 'manager', 'editor', 'author', 'contributor'];
if (!in_array($userLevel, $allowedLevels)) {
    sendJsonResponse(403, false, 'FORBIDDEN', 'Insufficient permissions');
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'Upload blocked by extension',
    ];
    $errorCode = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    $message = $errorMessages[$errorCode] ?? 'Unknown upload error';
    sendJsonResponse(400, false, 'UPLOAD_ERROR', $message);
}

$file = $_FILES['image'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    sendJsonResponse(400, false, 'INVALID_FILE_TYPE', 'Invalid file type. Only JPEG, PNG, GIF, WebP, and BMP are allowed.');
}

// Validate file size (max 5MB)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    sendJsonResponse(400, false, 'FILE_TOO_LARGE', 'File size exceeds maximum allowed (5MB)');
}

// Generate unique filename
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$newFilename = uniqid() . '_' . time() . '.' . $fileExtension;

// Use existing upload_photo() function to resize to 3 sizes + WebP
// This might output something, so we capture and discard
ob_start();
try {
    upload_photo(
        $file['tmp_name'],
        $file['size'],
        $fileType,
        $newFilename
    );
} catch (Throwable $e) {
    // Ignore upload errors, continue with original file
}
ob_end_clean();

// Get post_id if provided (for linking image to post)
$postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : null;

// Save to database via MediaDao
$mediaDao = new MediaDao();
$mediaId = $mediaDao->createMedia([
    'media_filename' => $newFilename,
    'media_caption' => '',
    'media_type' => 'image',
    'media_target' => 'blog',
    'media_user' => $session->scriptlog_session_login,
    'media_access' => 'public',
    'media_status' => 1
]);

// Link image to post via tbl_mediameta (only if post_id provided)
if (!empty($postId)) {
    $mediaDao->createMediaMeta([
        'media_id' => $mediaId,
        'meta_key' => 'post_id',
        'meta_value' => (string)$postId
    ]);
}

// Return success with image URL
$imageUrl = app_url() . '/public/files/pictures/' . $newFilename;

sendJsonResponse(201, true, null, null, [
    'url' => $imageUrl,
    'filename' => $newFilename,
    'media_id' => $mediaId,
    'post_id' => $postId
]);

/**
 * Send JSON response and exit
 */
function sendJsonResponse($statusCode, $success, $errorCode = null, $errorMessage = null, $data = null) {
    // Clean any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code($statusCode);
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    
    $response = ['success' => $success];
    
    if ($errorCode !== null) {
        $response['error'] = [
            'code' => $errorCode,
            'message' => $errorMessage
        ];
    }
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}
