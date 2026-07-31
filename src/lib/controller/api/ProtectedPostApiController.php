<?php

namespace Scriptlog\Controller\Api;

defined('SCRIPTLOG') || die("Direct access not permitted");

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

use Scriptlog\Controller\ApiController;
use Scriptlog\Core\ApiResponse;

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
     * Respond with HTMX fragment or API JSON, depending on request type.
     *
     * @param string $htmxFragment
     * @param array $htmxData
     * @param array|null $apiData
     * @param int $statusCode
     * @param string|null $apiErrorCode
     * @return bool True if HTMX response was sent
     */
    private function respondWithHtmxFallback($htmxFragment, $htmxData, $apiData = null, $statusCode = 200, $apiErrorCode = null)
    {
        if (function_exists('is_htmx_request') && is_htmx_request()) {
            render_htmx_fragment($htmxFragment, $htmxData, $statusCode);
            return true;
        }
        if ($apiData !== null) {
            if ($statusCode >= 400) {
                ApiResponse::error($apiData['message'] ?? 'Error', $statusCode, $apiErrorCode ?? 'ERROR');
            } else {
                ApiResponse::success($apiData, $statusCode);
            }
        }
        return false;
    }

    /**
     * Send an error response via HTMX or API JSON.
     *
     * @param string $message
     * @param int $statusCode
     * @param string|null $errorCode
     * @return void
     */
    private function sendError($message, $statusCode = 400, $errorCode = null)
    {
        if (function_exists('is_htmx_request') && is_htmx_request()) {
            render_htmx_fragment('unlock-error', ['error' => $message], $statusCode);
            return;
        }
        ApiResponse::error($message, $statusCode, $errorCode ?? 'ERROR');
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
            $this->sendError('Post ID is required', 400);
            return;
        }

        $input = $this->getJsonBody();
        $password = isset($input['password']) ? trim($input['password']) : '';

        if (empty($password)) {
            $this->sendError('Password is required', 400);
            return;
        }

        if (function_exists('is_unlock_rate_limited') && is_unlock_rate_limited($postId)) {
            $this->sendError('Too many failed attempts. Please try again later.', 429);
            return;
        }

        if (!function_exists('checking_post_password')) {
            $this->sendError('Password verification function not available', 500);
            return;
        }

        if (!function_exists('decrypt_post')) {
            $this->sendError('Decryption function not available', 500);
            return;
        }

        if (!checking_post_password($postId, $password)) {
            if (function_exists('track_failed_unlock_attempt')) {
                track_failed_unlock_attempt($postId);
            }
            $this->sendError('Incorrect password', 401);
            return;
        }

        if (function_exists('clear_failed_unlock_attempts')) {
            clear_failed_unlock_attempts($postId);
        }

        $decrypted = decrypt_post($postId, $password);

        if (!isset($decrypted['post_content']) || empty($decrypted['post_content'])) {
            $this->sendError('Unable to decrypt post content', 500);
            return;
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

        $this->respondWithHtmxFallback('unlock-success', [
            'content' => $content,
            'post_id' => $postId
        ], [
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
            $this->sendError('Post ID is required', 400);
            return;
        }

        $input = $this->getJsonBody();
        $password = isset($input['password']) ? trim($input['password']) : '';

        if (empty($password)) {
            $this->sendError('Password is required', 400);
            return;
        }

        if (function_exists('is_unlock_rate_limited') && is_unlock_rate_limited($postId)) {
            $this->sendError('Too many failed attempts. Please try again later.', 429);
            return;
        }

        if (!checking_post_password($postId, $password)) {
            if (function_exists('track_failed_unlock_attempt')) {
                track_failed_unlock_attempt($postId);
            }
            $this->sendError('Incorrect password', 401);
            return;
        }

        if (function_exists('clear_failed_unlock_attempts')) {
            clear_failed_unlock_attempts($postId);
        }

        $this->respondWithHtmxFallback('unlock-success', [
            'content' => '',
            'post_id' => $postId,
            'verified' => true
        ], [
            'valid' => true
        ]);
    }
}
