<?php

namespace Scriptlog\Dto\Api;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * TopicApiDto
 *
 * Data Transfer Object for API topic/category responses.
 * Transforms raw topic data from the Service/DAO layer into
 * the standardized API response format.
 *
 * @category DTO
 * @author   Blogware Team
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 */
class TopicApiDto
{
    public static function transform(array $topic, string $appUrl): array
    {
        $permalinkEnabled = function_exists('rewrite_status') && rewrite_status() === 'yes';

        $url = $permalinkEnabled
            ? $appUrl . '/category/' . rawurlencode($topic['topic_slug'])
            : $appUrl . '/?cat=' . (int)$topic['ID'];

        return [
            'id' => (int)$topic['ID'],
            'title' => $topic['topic_title'],
            'slug' => $topic['topic_slug'],
            'status' => $topic['topic_status'] ?? 'published',
            'post_count' => isset($topic['post_count']) ? (int)$topic['post_count'] : 0,
            'url' => $url
        ];
    }

    public static function transformCollection(array $topics, string $appUrl): array
    {
        return array_map(function ($topic) use ($appUrl) {
            return self::transform($topic, $appUrl);
        }, $topics);
    }
}
