<?php
/**
 * Media Upload for SummerNote
 * 
 * Simple standalone endpoint that handles session authentication
 * without going through the main admin routing
 */

// Turn off layout
define('NO_LAYOUT', true);

// Prevent direct access
define('SCRIPTLOG', hash('sha256', 'BLOGWARE_ADMIN_ACCESS'));

// Load main application
require_once __DIR__ . '/../lib/main.php';

// Check if we're in admin folder context
$session = null;
$isAuthenticated = false;

// Try to get session from both cookie and session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['scriptlog_session_login']) && !empty($_SESSION['scriptlog_session_login'])) {
    $isAuthenticated = true;
    $userLevel = $_SESSION['scriptlog_session_level'] ?? '';
} else {
    // Try cookie-based auth
    if (isset($_COOKIE['scriptlog_auth'])) {
        require_once __DIR__ . '/../lib/core/Authentication.php';
        require_once __DIR__ . '/../lib/core/ScriptlogCryptonize.php';
        
        $key = '';
        if (file_exists(APP_ROOT . '/storage/keys/')) {
            $keyFiles = glob(APP_ROOT . '/storage/keys/*.php');
            if (!empty($keyFiles)) {
                $key = include reset($keyFiles);
            }
        }
        
        if (!empty($key)) {
            $decrypt = ScriptlogCryptonize::scriptlogDecipher($_COOKIE['scriptlog_auth'], $key);
            if (!empty($decrypt)) {
                $isAuthenticated = true;
                $userLevel = $_SESSION['scriptlog_session_level'] ?? 'contributor';
            }
        }
    }
}

// Return JSON error if not authenticated
if (!$isAuthenticated) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'status' => 401,
        'error' => [
            'code' => 'UNAUTHORIZED',
            'message' => 'Admin authentication required'
        ]
    ]);
    exit;
}

// Verify user level
$allowedLevels = ['administrator', 'manager', 'editor', 'author', 'contributor'];
if (!in_array($userLevel, $allowedLevels)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'status' => 403,
        'error' => [
            'code' => 'FORBIDDEN',
            'message' => 'Insufficient permissions'
        ]
    ]);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'status' => 400,
        'error' => [
            'code' => 'UPLOAD_ERROR',
            'message' => 'No image uploaded or upload error'
        ]
    ]);
    exit;
}

$file = $_FILES['image'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
$fileType = mime_content_type($file['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'status' => 400,
        'error' => [
            'code' => 'INVALID_FILE_TYPE',
            'message' => 'Invalid file type'
        ]
    ]);
    exit;
}

// Max 5MB
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'status' => 400,
        'error' => [
            'code' => 'FILE_TOO_LARGE',
            'message' => 'Max 5MB allowed'
        ]
    ]);
    exit;
}

// Generate filename
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$newFilename = uniqid() . '_' . time() . '.' . $fileExtension;

// Upload path
$uploadDir = APP_ROOT . APP_PUBLIC . DS . 'files' . DS . 'pictures' . DS;

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$targetPath = $uploadDir . $newFilename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'status' => 500,
        'error' => [
            'code' => 'UPLOAD_FAILED',
            'message' => 'Failed to save file'
        ]
    ]);
    exit;
}

// Success
$imageUrl = app_url() . '/public/files/pictures/' . $newFilename;

http_response_code(201);
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'status' => 201,
    'data' => [
        'url' => $imageUrl,
        'filename' => $newFilename
    ]
]);
exit;
