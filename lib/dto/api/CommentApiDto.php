<?php

namespace Scriptlog\Dto\Api;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * CommentApiDto
 *
 * Data Transfer Object for API comment responses.
 * Transforms raw comment data from the Service/DAO layer into
 * the standardized API response format.
 *
 * @category DTO
 * @author   Blogware Team
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 */
class CommentApiDto
{
    public static function transform(array $comment, string $appUrl): array
    {
        $result = [
            'id' => (int)$comment['ID'],
            'post_id' => (int)$comment['comment_post_id'],
            'parent_id' => isset($comment['comment_parent_id']) ? (int)$comment['comment_parent_id'] : 0,
            'author' => [
                'name' => $comment['comment_author_name'] ?? '',
                'email' => $comment['comment_author_email'] ?? ''
            ],
            'content' => $comment['comment_content'] ?? '',
            'status' => $comment['comment_status'] ?? 'pending',
            'date' => $comment['comment_date'] ?? ''
        ];

        if (isset($comment['post_title'])) {
            $permalinkEnabled = function_exists('rewrite_status') && rewrite_status() === 'yes';
            $postSlug = $comment['post_slug'] ?? '';
            $postId = (int)$comment['comment_post_id'];

            $postUrl = $permalinkEnabled
                ? $appUrl . '/post/' . $postId . '/' . rawurlencode($postSlug)
                : $appUrl . '/?p=' . $postId;

            $result['post'] = [
                'title' => $comment['post_title'],
                'slug' => $postSlug,
                'url' => $postUrl
            ];
        }

        return $result;
    }

    public static function transformCollection(array $comments, string $appUrl): array
    {
        return array_map(function ($comment) use ($appUrl) {
            return self::transform($comment, $appUrl);
        }, $comments);
    }
}
