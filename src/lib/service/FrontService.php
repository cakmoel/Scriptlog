<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * FrontService — front-end content retrieval service.
 *
 * Provides a unified, injectable service for retrieving published front-end
 * content (posts, pages, topics/categories, archives, tags, galleries).
 * Used by FrontHelper (as a delegation target) and by request handlers for
 * content validation and rendering.
 *
 * All database queries use prepared statements via Registry::get('dbc')
 * or delegate to injected DAO instances when available. Methods mirror the
 * legacy FrontHelper static API so that FrontHelper can delegate to this
 * service, providing backward compatibility while centralising data access.
 *
 * <code>
 * // Via Registry (typical usage)
 * $frontService = Registry::get('frontService');
 * $post = $frontService->getPublishedPost(42);
 *
 * // Constructor injection (testing or DI)
 * $service = new FrontService($postDao, $pageDao, $topicDao, $mediaDao);
 * </code>
 *
 * @category Service
 * @author   System
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 */
class FrontService
{
    /**
     * Post DAO instance.
     *
     * @var PostDao|null
     */
    private ?PostDao $postDao;

    /**
     * Page DAO instance.
     *
     * @var PageDao|null
     */
    private ?PageDao $pageDao;

    /**
     * Topic/category DAO instance.
     *
     * @var TopicDao|null
     */
    private ?TopicDao $topicDao;

    /**
     * Media DAO instance.
     *
     * @var MediaDao|null
     */
    private ?MediaDao $mediaDao;

    /**
     * Whether a Sanitize instance is available.
     *
     * @var bool
     */
    private bool $hasSanitizer;

    /**
     * Sanitize instance for input filtering.
     *
     * @var Sanitize|null
     */
    private ?Sanitize $sanitizer;

    /**
     * Construct a new FrontService.
     *
     * Accepts optional DAO instances. Any DAO not provided will be
     * resolved from the global Registry at construction time.
     *
     * @param PostDao|null  $postDao  Optional post DAO.
     * @param PageDao|null  $pageDao  Optional page DAO.
     * @param TopicDao|null $topicDao Optional topic DAO.
     * @param MediaDao|null $mediaDao Optional media DAO.
     */
    public function __construct(
        $postDao = null,
        $pageDao = null,
        $topicDao = null,
        $mediaDao = null
    ) {
        $this->postDao = $postDao ?? (class_exists('Registry') ? Registry::get('postDao') : null);
        $this->pageDao = $pageDao ?? (class_exists('Registry') ? Registry::get('pageDao') : null);
        $this->topicDao = $topicDao ?? (class_exists('Registry') ? Registry::get('topicDao') : null);
        $this->mediaDao = $mediaDao ?? (class_exists('Registry') ? Registry::get('mediaDao') : null);
        $this->sanitizer = class_exists('Sanitize') ? new Sanitize() : null;
        $this->hasSanitizer = ($this->sanitizer !== null);
    }

    /**
     * Retrieve a published post by ID, including author and media metadata.
     *
     * Uses a prepared JOIN query via the shared database connection
     * (Registry::get('dbc')). Falls back to PostDao when the shared
     * connection is unavailable.
     *
     * @param int $id The post identifier.
     * @return array|null Post data with user and media information, or null.
     */
    public function getPublishedPost(int $id): ?array
    {
        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;

        if ($dbc) {
            $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date,
                           YEAR(p.post_date) AS year_archive,
                           MONTH(p.post_date) AS month_archive,
                           p.post_modified, p.post_title, p.post_slug,
                           p.post_content, p.post_summary, p.post_tags,
                           p.post_status, p.post_visibility, p.post_sticky,
                           p.post_type, p.comment_status,
                           m.media_filename, m.media_caption,
                           m.media_target, m.media_access,
                           u.user_fullname
                    FROM tbl_posts AS p
                    LEFT JOIN tbl_media AS m
                        ON p.media_id = m.ID
                       AND m.media_target = 'blog'
                       AND m.media_access = 'public'
                       AND m.media_status = '1'
                    LEFT JOIN tbl_users AS u
                        ON p.post_author = u.ID
                    WHERE p.ID = ?
                      AND p.post_status = 'publish'
                      AND p.post_visibility IN ('public', 'protected')
                      AND p.post_type = 'blog'
                    LIMIT 1";

            $result = $dbc->dbQuery($sql, [$id]);
            $row = $result ? $result->fetch() : null;

            if (!empty($row)) {
                return $row;
            }
        }

        if ($this->postDao && $this->hasSanitizer) {
            $result = $this->postDao->findPost($id, $this->sanitizer, null, true);
            if (is_array($result) && !empty($result)) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Retrieve a published page by its slug.
     *
     * Tries the PageDao first (when available), then falls back to a
     * prepared JOIN query via the shared database connection.
     *
     * @param string $slug The page slug.
     * @return array|null Page data with author and media information, or null.
     */
    public function getPublishedPage(string $slug): ?array
    {
        if ($this->pageDao) {
            $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date,
                           p.post_modified, p.post_title, p.post_slug,
                           p.post_content, p.post_summary,
                           p.post_tags, p.post_status, p.post_visibility,
                           p.post_sticky, p.post_type, p.comment_status,
                           m.ID AS media_id_ref, m.media_filename,
                           m.media_caption, m.media_access,
                           u.ID AS user_id, u.user_fullname
                    FROM tbl_posts AS p
                    LEFT JOIN tbl_media AS m
                        ON p.media_id = m.ID
                       AND m.media_access = 'public'
                       AND m.media_status = '1'
                    LEFT JOIN tbl_users AS u
                        ON p.post_author = u.ID
                    WHERE p.post_slug = ?
                      AND p.post_status = 'publish'
                      AND p.post_visibility = 'public'
                      AND p.post_type = 'page'
                    LIMIT 1";

            $dbc = class_exists('Registry') ? Registry::get('dbc') : null;
            if ($dbc) {
                $result = $dbc->dbQuery($sql, [$slug]);
                $row = $result ? $result->fetch() : null;
                if (!empty($row)) {
                    return $row;
                }
            }
        }

        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;
        if (!$dbc) {
            return null;
        }

        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date,
                       p.post_modified, p.post_title, p.post_slug,
                       p.post_content, p.post_summary,
                       p.post_tags, p.post_status, p.post_visibility,
                       p.post_sticky, p.post_type, p.comment_status,
                       m.ID AS media_id_ref, m.media_filename,
                       m.media_caption, m.media_access,
                       u.ID AS user_id, u.user_fullname
                FROM tbl_posts AS p
                LEFT JOIN tbl_media AS m
                    ON p.media_id = m.ID
                   AND m.media_access = 'public'
                   AND m.media_status = '1'
                LEFT JOIN tbl_users AS u
                    ON p.post_author = u.ID
                WHERE p.post_slug = ?
                  AND p.post_status = 'publish'
                  AND p.post_visibility = 'public'
                  AND p.post_type = 'page'
                LIMIT 1";

        $result = $dbc->dbQuery($sql, [$slug]);
        $row = $result ? $result->fetch() : null;

        return empty($row) ? null : $row;
    }

    /**
     * Retrieve a published topic/category by its slug.
     *
     * Uses a direct prepared statement instead of fetching all topics
     * and iterating (avoids O(n) scan).
     *
     * @param string $slug The topic slug.
     * @return array|null Topic data (ID, topic_title, topic_slug) or null.
     */
    public function getPublishedTopic(string $slug): ?array
    {
        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;

        if ($dbc) {
            $sql = "SELECT ID, topic_title, topic_slug
                    FROM tbl_topics
                    WHERE topic_slug = ? AND topic_status = 'Y'
                    LIMIT 1";

            $result = $dbc->dbQuery($sql, [$slug]);
            $row = $result ? $result->fetch() : null;

            if (!empty($row)) {
                return $row;
            }
        }

        if ($this->topicDao) {
            $topics = $this->topicDao->findTopics();
            if (is_array($topics)) {
                foreach ($topics as $topic) {
                    if (isset($topic['topic_slug']) && $topic['topic_slug'] === $slug) {
                        return $topic;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Retrieve a published topic/category by its ID.
     *
     * @param int $id The topic identifier.
     * @return array|null Topic data (ID, topic_title, topic_slug) or null.
     */
    public function getPublishedTopicById(int $id): ?array
    {
        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;

        if ($dbc) {
            $sql = "SELECT ID, topic_title, topic_slug
                    FROM tbl_topics
                    WHERE ID = ? AND topic_status = 'Y'
                    LIMIT 1";

            $result = $dbc->dbQuery($sql, [$id]);
            $row = $result ? $result->fetch() : null;

            if (!empty($row)) {
                return $row;
            }
        }

        if ($this->topicDao && $this->hasSanitizer) {
            $result = $this->topicDao->findTopicById($id, $this->sanitizer);
            return is_array($result) && !empty($result) ? $result : null;
        }

        return null;
    }

    /**
     * Retrieve a simple post by ID (basic fields, no archive columns).
     *
     * Legacy method matching FrontHelper::grabSimpleFrontPost().
     *
     * @param int $id The post identifier.
     * @return array|null Post data or null.
     */
    public function getSimplePost($id): ?array
    {
        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;

        if (!$dbc) {
            return null;
        }

        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date,
                       p.post_modified, p.post_title, p.post_slug,
                       p.post_content, p.post_summary, p.post_tags,
                       p.post_status, p.post_sticky,
                       p.post_type, p.comment_status,
                       m.media_filename, m.media_caption,
                       m.media_target, m.media_access,
                       u.user_login, u.user_fullname
                FROM tbl_posts p
                LEFT JOIN tbl_media m
                    ON p.media_id = m.ID
                   AND m.media_target = 'blog'
                   AND m.media_access = 'public'
                   AND m.media_status = '1'
                LEFT JOIN tbl_users u
                    ON p.post_author = u.ID
                WHERE p.ID = ?
                  AND p.post_status = 'publish'
                  AND p.post_type = 'blog'
                LIMIT 1";

        $result = $dbc->dbQuery($sql, [(int)$id]);
        $row = $result ? $result->fetch() : null;

        return empty($row) ? null : $row;
    }

    /**
     * Retrieve a simple topic/category by ID.
     *
     * Legacy method matching FrontHelper::grabSimpleFrontTopic().
     *
     * @param int $id The topic identifier.
     * @return array|null Topic data (ID, topic_title, topic_slug) or null.
     */
    public function getSimpleTopic($id): ?array
    {
        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;

        if (!$dbc) {
            return null;
        }

        $sql = "SELECT ID, topic_title, topic_slug
                FROM tbl_topics
                WHERE topic_status = 'Y' AND ID = ?
                LIMIT 1";

        $result = $dbc->dbQuery($sql, [(int)$id]);
        $row = $result ? $result->fetch() : null;

        return empty($row) ? null : $row;
    }

    /**
     * Retrieve a simple page by ID.
     *
     * Legacy method matching FrontHelper::grabSimpleFrontPage().
     *
     * @param int $id The page identifier.
     * @return array|null Page data or null.
     */
    public function getSimplePage($id): ?array
    {
        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;

        if (!$dbc) {
            return null;
        }

        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date,
                       p.post_modified, p.post_title, p.post_slug,
                       p.post_content, p.post_summary,
                       p.post_status, p.post_visibility, p.post_tags,
                       p.post_sticky, p.post_type, p.comment_status,
                       m.ID AS media_id_ref, m.media_filename,
                       m.media_caption, m.media_access,
                       u.ID AS user_id, u.user_login, u.user_fullname
                FROM tbl_posts AS p
                LEFT JOIN tbl_media AS m
                    ON p.media_id = m.ID
                   AND m.media_access = 'public'
                   AND m.media_status = '1'
                LEFT JOIN tbl_users AS u
                    ON p.post_author = u.ID
                WHERE p.ID = ?
                  AND p.post_status = 'publish'
                  AND p.post_visibility = 'public'
                  AND p.post_type = 'page'
                LIMIT 1";

        $result = $dbc->dbQuery($sql, [(int)$id]);
        $row = $result ? $result->fetch() : null;

        return empty($row) ? null : $row;
    }

    /**
     * Retrieve archive index (all years/months with published blog posts).
     *
     * Legacy method matching FrontHelper::grabSimpleFrontArchive().
     *
     * @return array List of archive rows with year_archive and month_archive.
     */
    public function getSimpleArchive(): array
    {
        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;

        if (!$dbc) {
            return [];
        }

        $sql = "SELECT YEAR(post_date) AS year_archive,
                       MONTH(post_date) AS month_archive
                FROM tbl_posts
                WHERE post_type = 'blog' AND post_status = 'publish'
                GROUP BY YEAR(post_date), MONTH(post_date)
                ORDER BY YEAR(post_date) DESC, MONTH(post_date) DESC";

        $result = $dbc->dbQuery($sql);
        $rows = $result ? $result->fetchAll() : [];

        return empty($rows) ? [] : $rows;
    }

    /**
     * Search for a tag in published blog posts.
     *
     * Uses a LIKE query against the post_tags column.
     *
     * @param string $tag The tag keyword to search for.
     * @return array|null Matching post data or null if not found.
     */
    public function searchTag(string $tag): ?array
    {
        if (empty($tag)) {
            return [];
        }

        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;

        if (!$dbc) {
            return [];
        }

        try {
            $tagSearch = '%' . $tag . '%';
            $sql = "SELECT ID, post_title, post_content, post_summary, post_tags
                    FROM tbl_posts
                    WHERE post_tags LIKE ?
                      AND post_status = 'publish'
                      AND post_type = 'blog'
                    LIMIT 1";

            $result = $dbc->dbQuery($sql, [$tagSearch]);
            $row = $result ? $result->fetch() : null;

            return empty($row) ? null : $row;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Retrieve a list of all unique, trimmed tags across published posts.
     *
     * @return array List of unique tag strings.
     */
    public function getTagLists(): array
    {
        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;

        if (!$dbc) {
            return [];
        }

        $sql = "SELECT DISTINCT LOWER(post_tags) AS postTags
                FROM tbl_posts
                WHERE post_tags != ''
                GROUP BY postTags";

        $result = $dbc->dbQuery($sql);
        $rows = $result ? $result->fetchAll() : [];

        $tagArrays = [];

        foreach ($rows as $row) {
            $parts = explode(',', $row['postTags']);
            foreach ($parts as $part) {
                $tagArrays[] = trim($part);
            }
        }

        return array_values(array_unique($tagArrays));
    }

    /**
     * Retrieve published posts for a specific archive month/year.
     *
     * Legacy method matching FrontHelper::grabPreparedFrontArchive().
     *
     * @param array $values Associative array with 'month' and 'year' keys.
     * @return array|null Post data for the archive month, or null.
     */
    public function getArchivePosts(array $values): ?array
    {
        $month = isset($values['month']) ? $values['month'] : null;
        $year = isset($values['year']) ? $values['year'] : null;

        if (empty($month) || empty($year)) {
            return null;
        }

        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;

        if (!$dbc) {
            return null;
        }

        $from = date('Y-m-01 00:00:00', strtotime($year . '-' . $month));
        $to   = date('Y-m-t 23:59:59', strtotime($year . '-' . $month));

        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date,
                       p.post_modified, p.post_title, p.post_slug,
                       p.post_content, p.post_summary, p.post_tags,
                       p.post_type, p.post_status, p.post_sticky,
                       u.user_login, u.user_fullname,
                       m.media_filename, m.media_caption
                FROM tbl_posts AS p
                LEFT JOIN tbl_users AS u
                    ON p.post_author = u.ID
                LEFT JOIN tbl_media AS m
                    ON p.media_id = m.ID
                   AND m.media_access = 'public'
                   AND m.media_status = '1'
                WHERE DATE(p.post_date) BETWEEN ? AND ?
                  AND p.post_type = 'blog'
                  AND p.post_status = 'publish'
                ORDER BY DATE(p.post_date) DESC";

        $result = $dbc->dbQuery($sql, [$from, $to]);
        $row = $result ? $result->fetch() : null;

        return empty($row) ? null : $row;
    }

    /**
     * Retrieve gallery images (media targeted as 'gallery').
     *
     * Returns the last result from the result set, preserving the
     * original FrontHelper::grabPreparedFrontGalleries() behaviour.
     *
     * @param int $start Offset for pagination.
     * @param int $limit Maximum number of rows to query.
     * @return array|null Single gallery row with media_filename, media_caption, media_id; or null.
     */
    public function getGalleries(int $start, int $limit): ?array
    {
        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;

        if (!$dbc) {
            return null;
        }

        $sql = "SELECT ID, media_filename, media_caption
                FROM tbl_media
                WHERE media_target = 'gallery'
                ORDER BY ID
                LIMIT ?, ?";

        $result = $dbc->dbQuery($sql, [$start, $limit]);

        if (!$result) {
            return null;
        }

        $rows = $result->fetchAll();

        if (empty($rows)) {
            return null;
        }

        $media_filename = '';
        $media_caption = '';
        $media_id = null;

        foreach ($rows as $row) {
            $media_filename = $row['media_filename'];
            $media_caption = $row['media_caption'];
            $media_id = $row['ID'];
        }

        return [
            'media_filename' => $media_filename,
            'media_caption' => $media_caption,
            'media_id' => $media_id
        ];
    }
}
