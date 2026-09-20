<?php

namespace Scriptlog\Dto\Api;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * PostApiDto
 *
 * Data Transfer Object for API post responses.
 * Transforms raw post data from the Service/DAO layer into
 * the standardized API response format.
 *
 * @category DTO
 * @author   Blogware Team
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 */
class PostApiDto
{
    public static function transform(array $post, string $appUrl): array
    {
        $tags = !empty($post['post_tags']) ? explode(',', $post['post_tags']) : [];

        $isPage = isset($post['post_type']) && $post['post_type'] === 'page';

        $permalinkEnabled = function_exists('rewrite_status') && rewrite_status() === 'yes';

        if ($permalinkEnabled) {
            $url = $isPage
                ? $appUrl . '/page/' . rawurlencode($post['post_slug'])
                : $appUrl . '/post/' . (int)$post['ID'] . '/' . rawurlencode($post['post_slug']);
        } else {
            $url = $isPage
                ? $appUrl . '/?pg=' . (int)$post['ID']
                : $appUrl . '/?p=' . (int)$post['ID'];
        }

        $excerpt = '';
        if (!empty($post['post_summary'])) {
            $excerpt = $post['post_summary'];
        } elseif (!empty($post['post_content'])) {
            $content = strip_tags($post['post_content']);
            $excerpt = mb_strlen($content, 'UTF-8') > 150
                ? mb_substr($content, 0, 150, 'UTF-8') . '...'
                : $content;
        }

        $result = [
            'id' => (int)$post['ID'],
            'title' => $post['post_title'],
            'slug' => $post['post_slug'],
            'content' => $post['post_content'] ?? '',
            'summary' => $post['post_summary'] ?? '',
            'excerpt' => $excerpt,
            'status' => $post['post_status'],
            'visibility' => $post['post_visibility'] ?? 'public',
            'tags' => $tags,
            'comment_status' => $post['comment_status'] ?? 'open',
            'type' => $post['post_type'] ?? 'blog',
            'author' => [
                'id' => (int)$post['post_author'],
                'login' => $post['author_login'] ?? '',
                'name' => $post['author_name'] ?? ''
            ],
            'date' => $post['post_date'] ?? '',
            'modified' => $post['post_modified'] ?? '',
            'url' => $url
        ];

        return $result;
    }

    public static function transformCollection(array $posts, string $appUrl): array
    {
        return array_map(function ($post) use ($appUrl) {
            return self::transform($post, $appUrl);
        }, $posts);
    }
}
