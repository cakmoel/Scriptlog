<?php

/**
 * Blog Theme Comment Helpers
 *
 * Comment counting, CSRF token and comment-section rendering helpers for
 * the Bootstrap Blog theme. Extracted from the monolithic functions.php
 * (Phase 5 remediation). The raw comment-count SQL is routed through
 * CommentDao. All functions use function_exists() guards to avoid
 * redeclaration errors.
 *
 * @category Theme Function
 * @package Scriptlog
 */

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * total_comment() - Get total approved comments for post
 */
if (!function_exists('total_comment')) {
    function total_comment($id)
    {
        $commentDao = class_exists('CommentDao') ? new CommentDao() : null;
        if (!$commentDao) {
            return ['total' => 0];
        }

        try {
            $total = $commentDao->countApprovedComments((int)$id);
            return ['total' => (int)$total];
        } catch (Throwable $e) {
            return ['total' => 0];
        }
    }
}

/**
 * block_csrf() - Generate CSRF token for comment form
 */
if (!function_exists('block_csrf')) {
    function block_csrf()
    {
        return (function_exists('generate_form_token')) ? generate_form_token('comment_form', 32) : "";
    }
}

/**
 * render_comments_section() - Render comments section HTML
 *
 * Captures the shared partials/comments.php markup so single.php keeps a
 * string-returning API while the partial remains the single source of truth.
 */
if (!function_exists('render_comments_section')) {
    function render_comments_section(int $postId, int $offset = 0): string
    {
        $totalRecords = isset(total_comment($postId)['total']) ? (int) total_comment($postId)['total'] : 0;
        $commentLimit = isset(app_reading_setting()['comment_per_post']) ? (int) app_reading_setting()['comment_per_post'] : 3;

        $post_id = $postId;
        $comment_limit = $commentLimit;
        $total_records = $totalRecords;
        $offset = $offset;

        $partial = dirname(__FILE__) . '/partials/comments.php';

        ob_start();
        include $partial;

        return ob_get_clean();
    }
}
