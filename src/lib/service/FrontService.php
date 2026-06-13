<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class FrontService
{
    private ?PostDao $postDao;
    private ?PageDao $pageDao;
    private ?TopicDao $topicDao;
    private ?MediaDao $mediaDao;

    public function __construct(
        ?PostDao $postDao = null,
        ?PageDao $pageDao = null,
        ?TopicDao $topicDao = null,
        ?MediaDao $mediaDao = null
    ) {
        $this->postDao = $postDao ?? (class_exists('Registry') ? Registry::get('postDao') : null);
        $this->pageDao = $pageDao ?? (class_exists('Registry') ? Registry::get('pageDao') : null);
        $this->topicDao = $topicDao ?? (class_exists('Registry') ? Registry::get('topicDao') : null);
        $this->mediaDao = $mediaDao ?? (class_exists('Registry') ? Registry::get('mediaDao') : null);
    }

    /**
     * getPublishedPost
     * Mirrors FrontHelper::grabPreparedFrontPostById()
     */
    public function getPublishedPost(int $id): ?array
    {
        if ($this->postDao) {
            $sanitizer = class_exists('Sanitize') ? new Sanitize() : null;
            if ($sanitizer) {
                $result = $this->postDao->findPost($id, $sanitizer, null, true);
                if (is_array($result) && !empty($result)) {
                    return $result;
                }
            }
        }

        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;
        if (!$dbc) return null;

        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date,
               YEAR(p.post_date) AS year_archive, MONTH(p.post_date) AS month_archive,
               p.post_modified, p.post_title, p.post_slug,
               p.post_content, p.post_summary, p.post_tags,
               p.post_status, p.post_visibility, p.post_sticky,
               p.post_type, p.comment_status, m.media_filename, m.media_caption, m.media_target,
               m.media_access, u.user_fullname
        FROM tbl_posts AS p
        LEFT JOIN tbl_media AS m ON p.media_id = m.ID
        LEFT JOIN tbl_users AS u ON p.post_author = u.ID
        WHERE p.ID = ? AND p.post_status = 'publish'
        AND p.post_visibility IN ('public', 'protected')
        AND p.post_type = 'blog' AND m.media_target = 'blog'
        AND m.media_access = 'public' AND m.media_status = '1' LIMIT 1";

        $result = $dbc->dbQuery($sql, [$id]);
        $row = $result ? $result->fetch() : null;
        return empty($row) ? null : $row;
    }

    /**
     * getPublishedPage
     * Mirrors FrontHelper::grabPreparedFrontPageBySlug()
     */
    public function getPublishedPage(string $slug): ?array
    {
        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;
        if (!$dbc) return null;

        $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
               p.post_title, p.post_slug,
               p.post_content, p.post_summary,
               p.post_tags, p.post_status, p.post_visibility, p.post_sticky,
               p.post_type, p.comment_status,
               m.ID, m.media_filename, m.media_caption, m.media_access, u.ID, u.user_fullname
        FROM tbl_posts AS p
        LEFT JOIN tbl_media AS m ON p.media_id = m.ID
        LEFT JOIN tbl_users AS u ON p.post_author = u.ID
        WHERE p.post_slug = ?
        AND p.post_status = 'publish'
        AND p.post_visibility = 'public'
        AND p.post_type = 'page'
        AND m.media_access = 'public' AND m.media_status = '1' LIMIT 1";

        $result = $dbc->dbQuery($sql, [$slug]);
        $row = $result ? $result->fetch() : null;
        return empty($row) ? null : $row;
    }

    /**
     * getPublishedTopic
     * Mirrors FrontHelper::grabPreparedFrontTopicBySlug()
     */
    public function getPublishedTopic(string $slug): ?array
    {
        if ($this->topicDao) {
            $sanitizer = class_exists('Sanitize') ? new Sanitize() : null;
            if ($sanitizer) {
                $topics = $this->topicDao->findTopics();
                if (is_array($topics)) {
                    foreach ($topics as $topic) {
                        if (isset($topic['topic_slug']) && $topic['topic_slug'] === $slug) {
                            return $topic;
                        }
                    }
                }
            }
        }

        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;
        if (!$dbc) return null;

        $sql = "SELECT ID, topic_title, topic_slug FROM tbl_topics WHERE topic_slug = ? AND topic_status = 'Y'";
        $result = $dbc->dbQuery($sql, [$slug]);
        $row = $result ? $result->fetch() : null;
        return empty($row) ? null : $row;
    }

    public function getPublishedTopicById(int $id): ?array
    {
        if ($this->topicDao) {
            $sanitizer = class_exists('Sanitize') ? new Sanitize() : null;
            if ($sanitizer) {
                $result = $this->topicDao->findTopicById($id, $sanitizer);
                return is_array($result) && !empty($result) ? $result : null;
            }
        }

        $dbc = class_exists('Registry') ? Registry::get('dbc') : null;
        if (!$dbc) return null;

        $sql = "SELECT ID, topic_title, topic_slug FROM tbl_topics WHERE ID = ? AND topic_status = 'Y'";
        $result = $dbc->dbQuery($sql, [$id]);
        $row = $result ? $result->fetch() : null;
        return empty($row) ? null : $row;
    }

    public function getSimplePost($id): ?array
    {
        return FrontHelper::grabSimpleFrontPost($id);
    }

    public function getSimpleTopic($id): ?array
    {
        return FrontHelper::grabSimpleFrontTopic($id);
    }

    public function getSimplePage($id): ?array
    {
        return FrontHelper::grabSimpleFrontPage($id);
    }

    public function getSimpleArchive(): array
    {
        return FrontHelper::grabSimpleFrontArchive();
    }

    public function searchTag(string $tag): ?array
    {
        return FrontHelper::simpleSearchingTag($tag);
    }

    public function getTagLists(): array
    {
        return FrontHelper::grabTagLists();
    }

    public function getArchivePosts(array $values): ?array
    {
        return FrontHelper::grabPreparedFrontArchive($values);
    }

    public function getGalleries(int $start, int $limit): ?array
    {
        return FrontHelper::grabPreparedFrontGalleries($start, $limit);
    }
}
