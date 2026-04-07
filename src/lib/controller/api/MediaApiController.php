<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * MediaApiController
 *
 * Handles media upload API for Summernote WYSIWYG editor
 * This endpoint requires admin session authentication
 *
 * @category  Controller
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 *
 */
class MediaApiController
{
    
    /**
     * Upload image for Summernote
     * 
     * POST /api/v1/media/upload
     */
    public function upload()
    {
        // Check authentication via session OR auth cookie
        $isAuthenticated = false;
        $userLogin = null;
        $userLevel = null;
        
        // Method 1: Check session variables
        if (isset($_SESSION['scriptlog_session_login']) && !empty($_SESSION['scriptlog_session_login'])) {
            $isAuthenticated = true;
            $userLogin = $_SESSION['scriptlog_session_login'];
            $userLevel = $_SESSION['scriptlog_session_level'] ?? '';
        }
        // Method 2: Check auth cookie (for AJAX requests from admin panel)
        elseif (isset($_COOKIE['scriptlog_auth']) && !empty($_COOKIE['scriptlog_auth'])) {
            try {
                $cipherKey = class_exists('ScriptlogCryptonize') ? ScriptlogCryptonize::scriptlogCipherKey() : '';
                if (!empty($cipherKey)) {
                    $userLogin = ScriptlogCryptonize::scriptlogDecipher($_COOKIE['scriptlog_auth'], $cipherKey);
                    if (!empty($userLogin)) {
                        $isAuthenticated = true;
                        // Get user level from database
                        $userDao = new UserDao();
                        $user = $userDao->getUserByLogin($userLogin);
                        if ($user) {
                            $userLevel = $user['user_level'];
                        }
                    }
                }
            } catch (Throwable $e) {
                // Cookie decryption failed
            }
        }
        
        if (!$isAuthenticated || empty($userLogin)) {
            header('Content-Type: application/json');
            http_response_code(401);
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

        // Verify user level (must be at least contributor)
        $allowedLevels = ['administrator', 'manager', 'editor', 'author', 'contributor'];
        if (!in_array($userLevel, $allowedLevels)) {
            header('Content-Type: application/json');
            http_response_code(403);
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

        // CSRF token validation - skip if session not available (auth cookie used instead)
        // Only validate CSRF if session token exists
        if (isset($_SESSION['csrf_csrfToken']) && (!isset($_POST['csrfToken']) || !csrf_check_token('csrfToken', $_POST, 60 * 10))) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'status' => 403,
                'error' => [
                    'code' => 'CSRF_INVALID',
                    'message' => 'Invalid security token'
                ]
            ]);
            exit;
        }

        // Get post_id if provided (for linking image to post)
        $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : null;

        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            header('Content-Type: application/json');
            http_response_code(400);
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
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'status' => 400,
                'error' => [
                    'code' => 'INVALID_FILE_TYPE',
                    'message' => 'Invalid file type. Only JPEG, PNG, GIF, WebP, and BMP are allowed.'
                ]
            ]);
            exit;
        }

        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'status' => 400,
                'error' => [
                    'code' => 'FILE_TOO_LARGE',
                    'message' => 'File size exceeds maximum allowed (5MB)'
                ]
            ]);
            exit;
        }

        // Generate unique filename
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $newFilename = uniqid() . '_' . time() . '.' . $fileExtension;

        // Use existing upload_photo() function to resize to 3 sizes + WebP
        upload_photo(
            $file['tmp_name'],
            $file['size'],
            $fileType,
            $newFilename
        );

        // Save to database via MediaDao
        $mediaDao = new MediaDao();
        $mediaId = $mediaDao->createMedia([
            'media_filename' => $newFilename,
            'media_caption' => '',
            'media_type' => 'image',
            'media_target' => 'blog',
            'media_user' => $userLogin,
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

        // Return direct filesystem URL for fast loading
        $imageUrl = app_url() . '/public/files/pictures/' . $newFilename;

        header('Content-Type: application/json');
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'status' => 201,
            'data' => [
                'url' => $imageUrl,
                'filename' => $newFilename,
                'media_id' => $mediaId,
                'post_id' => $postId
            ]
        ]);
        exit;
    }
}
