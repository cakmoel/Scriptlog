<?php

/**
 * Protected Post API Controller
 *
 * Handles API requests for password-protected post unlock functionality
 *
 * @category  Controller Class
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
defined('SCRIPTLOG') || die('Direct access not permitted');

class ProtectedPostApiController extends ApiController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->requiresAuth = false;
        parent::__construct();
    }

    /**
     * Unlock password-protected post
     *
     * POST /api/v1/posts/{id}/unlock
     *
     * @param array $params URL parameters (post ID)
     * @return void
     */
    public function unlock($params = [])
    {
        $postId = isset($params['id']) ? (int)$params['id'] : 0;

        if (empty($postId)) {
            ApiResponse::error('Post ID is required', 400);
        }

        $input = $this->getJsonBody();
        $password = isset($input['password']) ? trim($input['password']) : '';

        if (empty($password)) {
            ApiResponse::error('Password is required', 400);
        }

        if (function_exists('is_unlock_rate_limited') && is_unlock_rate_limited($postId)) {
            ApiResponse::error('Too many failed attempts. Please try again later.', 429);
        }

        if (!function_exists('checking_post_password')) {
            ApiResponse::error('Password verification function not available', 500);
        }

        if (!function_exists('decrypt_post')) {
            ApiResponse::error('Decryption function not available', 500);
        }

        if (!checking_post_password($postId, $password)) {
            if (function_exists('track_failed_unlock_attempt')) {
                track_failed_unlock_attempt($postId);
            }
            ApiResponse::error('Incorrect password', 401);
        }

        if (function_exists('clear_failed_unlock_attempts')) {
            clear_failed_unlock_attempts($postId);
        }

        $decrypted = decrypt_post($postId, $password);

        if (!isset($decrypted['post_content']) || empty($decrypted['post_content'])) {
            ApiResponse::error('Unable to decrypt post content', 500);
        }

        $decoded_content = html_entity_decode($decrypted['post_content'], ENT_QUOTES, 'UTF-8');
        $decoded_content = html_entity_decode($decoded_content, ENT_QUOTES, 'UTF-8');
        $clean_content = preg_replace('/\s*style="[^"]*"/', '', $decoded_content);
        $clean_content = preg_replace('/\s*style=[^>\s]*/', '', $clean_content);
        $content = htmLawed($clean_content, array(
            'deny_attribute' => 'style,onclick,onerror,onload,onmouseover,onfocus,onblur,onchange,onsubmit,onkeydown,onkeyup,onkeypress',
            'keep_bad' => 0
        ));

        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION['unlocked_posts'])) {
            $_SESSION['unlocked_posts'] = [];
        }
        $_SESSION['unlocked_posts'][$postId] = $password;

        ApiResponse::success([
            'content' => $content
        ]);
    }

    /**
     * Verify post password (lightweight check)
     *
     * POST /api/v1/posts/{id}/verify
     *
     * @param array $params URL parameters (post ID)
     * @return void
     */
    public function verify($params = [])
    {
        $postId = isset($params['id']) ? (int)$params['id'] : 0;

        if (empty($postId)) {
            ApiResponse::error('Post ID is required', 400);
        }

        $input = $this->getJsonBody();
        $password = isset($input['password']) ? trim($input['password']) : '';

        if (empty($password)) {
            ApiResponse::error('Password is required', 400);
        }

        if (function_exists('is_unlock_rate_limited') && is_unlock_rate_limited($postId)) {
            ApiResponse::error('Too many failed attempts. Please try again later.', 429);
        }

        if (!checking_post_password($postId, $password)) {
            if (function_exists('track_failed_unlock_attempt')) {
                track_failed_unlock_attempt($postId);
            }
            ApiResponse::error('Incorrect password', 401);
        }

        if (function_exists('clear_failed_unlock_attempts')) {
            clear_failed_unlock_attempts($postId);
        }

        $isValid = checking_post_password($postId, $password);

        ApiResponse::success([
            'valid' => $isValid
        ]);
    }
}
