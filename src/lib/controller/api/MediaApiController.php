<?php

namespace Scriptlog\Controller\Api;

defined('SCRIPTLOG') || die("Direct access not permitted");

use Scriptlog\Controller\ApiController;
use Scriptlog\Core\ApiAuth;
use Scriptlog\Core\ApiResponse;
use Scriptlog\Core\ScriptlogCryptonize;
use Scriptlog\Dao\MediaDao;
use Scriptlog\Dao\UserDao;

class MediaApiController extends ApiController
{
    /**
     * Upload image for Summernote
     *
     * POST /api/v1/media/upload
     */
    public function upload()
    {
        // Authenticate via admin session/cookie
        if (!$this->authenticateAdminSession()) {
            ApiResponse::unauthorized('Admin authentication required');
            return;
        }

        // Verify user level (must be at least contributor)
        $allowedLevels = ['administrator', 'manager', 'editor', 'author', 'contributor'];
        if (!in_array(ApiAuth::getUserLevel(), $allowedLevels)) {
            ApiResponse::forbidden('Insufficient permissions');
            return;
        }

        // CSRF validation via ApiAuth
        ApiAuth::validateCsrfForWrite();

        // Get post_id if provided (for linking image to post)
        $postId = isset($_POST['post_id']) ? (int)$_POST['post_id'] : null;

        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            ApiResponse::badRequest('No image uploaded or upload error');
            return;
        }

        $file = $_FILES['image'];

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
        $fileType = mime_content_type($file['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            ApiResponse::badRequest('Invalid file type. Only JPEG, PNG, GIF, WebP, and BMP are allowed.');
            return;
        }

        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            ApiResponse::badRequest('File size exceeds maximum allowed (5MB)');
            return;
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
            'media_user' => ApiAuth::getUserLogin(),
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

        ApiResponse::created([
            'url' => $imageUrl,
            'filename' => $newFilename,
            'media_id' => $mediaId,
            'post_id' => $postId
        ], 'Image uploaded successfully');
    }

    /**
     * Authenticate via admin session or auth cookie
     *
     * Sets ApiAuth state on success so that ApiAuth::getUser()
     * and related methods work consistently.
     *
     * @return bool
     */
    private function authenticateAdminSession()
    {
        $userLogin = null;
        $userLevel = null;

        // Method 1: Check session variables
        if (isset($_SESSION['scriptlog_session_login']) && !empty($_SESSION['scriptlog_session_login'])) {
            $userLogin = $_SESSION['scriptlog_session_login'];
            $userLevel = $_SESSION['scriptlog_session_level'] ?? '';
        }
        // Method 2: Check auth cookie (for AJAX requests from admin panel)
        elseif (isset($_COOKIE['scriptlog_auth']) && !empty($_COOKIE['scriptlog_auth'])) {
            try {
                $cipherKey = ScriptlogCryptonize::scriptlogCipherKey();
                $userLogin = ScriptlogCryptonize::scriptlogDecipher($_COOKIE['scriptlog_auth'], $cipherKey);
                if (!empty($userLogin)) {
                    $userDao = new UserDao();
                    $user = $userDao->getUserByLogin($userLogin);
                    if ($user) {
                        $userLevel = $user['user_level'];
                    }
                }
            } catch (\Throwable $e) {
                return false;
            }
        }

        if (empty($userLogin)) {
            return false;
        }

        ApiAuth::setSessionUser([
            'user_login' => $userLogin,
            'user_level' => $userLevel ?? ''
        ]);

        return true;
    }
}
