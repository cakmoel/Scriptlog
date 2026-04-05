<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * ExportService
 *
 * Main service for handling content export to various platforms
 *
 * @category  Service Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ExportService
{
    private $dbc;
    private $authorId;
    private $exportStats;

    public const DESTINATION_WORDPRESS = 'wordpress';
    public const DESTINATION_GHOST = 'ghost';
    public const DESTINATION_BLOGSPOT = 'blogspot';
    public const DESTINATION_SCRIPTLOG = 'scriptlog';

    public function __construct()
    {
        $this->dbc = Registry::get('dbc');

        $this->authorId = 1;
        $this->exportStats = [
            'posts_exported' => 0,
            'pages_exported' => 0,
            'categories_exported' => 0,
            'comments_exported' => 0,
        ];
    }

    /**
     * Set author ID for filtering content (optional)
     *
     * @param int $authorId
     */
    public function setAuthorId($authorId)
    {
        $this->authorId = (int) $authorId;
    }

    /**
     * Get export statistics
     *
     * @return array
     */
    public function getStats()
    {
        return $this->exportStats;
    }

    /**
     * Reset export statistics
     *
     * @return void
     */
    public function resetStats()
    {
        $this->exportStats = [
            'posts_exported' => 0,
            'pages_exported' => 0,
            'categories_exported' => 0,
            'comments_exported' => 0,
        ];
    }

    /**
     * Export content to WordPress WXR format
     *
     * @return array
     */
    public function exportToWordPress()
    {
        $this->resetStats();

        try {
            $exporter = new WordPressExporter();
            $content = $exporter->export($this->exportStats, $this->authorId);

            return [
                'success' => true,
                'destination' => self::DESTINATION_WORDPRESS,
                'filename' => 'blogware-export-' . date('Y-m-d') . '.xml',
                'content' => $content,
                'stats' => $this->exportStats
            ];
        } catch (ExportException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'destination' => self::DESTINATION_WORDPRESS
            ];
        }
    }

    /**
     * Export content to Ghost JSON format
     *
     * @return array
     */
    public function exportToGhost()
    {
        $this->resetStats();

        try {
            $exporter = new GhostExporter();
            $content = $exporter->export($this->exportStats, $this->authorId);

            return [
                'success' => true,
                'destination' => self::DESTINATION_GHOST,
                'filename' => 'blogware-export-' . date('Y-m-d') . '.json',
                'content' => $content,
                'stats' => $this->exportStats
            ];
        } catch (ExportException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'destination' => self::DESTINATION_GHOST
            ];
        }
    }

    /**
     * Export content to Blogspot XML format
     *
     * @return array
     */
    public function exportToBlogspot()
    {
        $this->resetStats();

        try {
            $exporter = new BlogspotExporter();
            $content = $exporter->export($this->exportStats, $this->authorId);

            return [
                'success' => true,
                'destination' => self::DESTINATION_BLOGSPOT,
                'filename' => 'blogware-export-' . date('Y-m-d') . '.xml',
                'content' => $content,
                'stats' => $this->exportStats
            ];
        } catch (ExportException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'destination' => self::DESTINATION_BLOGSPOT
            ];
        }
    }

    /**
     * Export content to Scriptlog native JSON format
     * This format can be imported directly into another Scriptlog installation
     *
     * @return array
     */
    public function exportToScriptlog()
    {
        $this->resetStats();

        try {
            $exporter = new ScriptlogExporter();
            $content = $exporter->export($this->exportStats, $this->authorId);

            return [
                'success' => true,
                'destination' => self::DESTINATION_SCRIPTLOG,
                'filename' => 'scriptlog-export-' . date('Y-m-d') . '.json',
                'content' => $content,
                'stats' => $this->exportStats
            ];
        } catch (ExportException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'destination' => self::DESTINATION_SCRIPTLOG
            ];
        }
    }

    /**
     * Get posts for export
     *
     * @param int|null $authorId
     * @return array
     */
    protected function getPosts($authorId = null)
    {
        $where = [];
        $params = [];

        if ($authorId !== null) {
            $where[] = 'post_author = ?';
            $params[] = $authorId;
        }

        $where[] = 'post_status = ?';
        $params[] = 'publish';

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT p.*, u.user_login, u.user_fullname 
                FROM tbl_posts p 
                LEFT JOIN tbl_users u ON p.post_author = u.ID 
                {$whereClause}
                ORDER BY p.post_date DESC";

        $stmt = $this->dbc->dbQuery($sql, $params);
        $posts = $stmt->fetchAll();

        // Process each post to get associated data
        foreach ($posts as &$post) {
            // Get categories/topics
            $post['categories'] = $this->getPostCategories($post['ID']);
            // Get tags from post_tags
            $post['tags'] = $this->getPostTags($post['post_tags']);
            // Get comments
            $post['comments'] = $this->getPostComments($post['ID']);
        }

        return $posts;
    }

    /**
     * Get pages for export
     *
     * @param int|null $authorId
     * @return array
     */
    protected function getPages($authorId = null)
    {
        $where = [];
        $params = [];

        if ($authorId !== null) {
            $where[] = 'post_author = ?';
            $params[] = $authorId;
        }

        $where[] = 'post_type = ?';
        $params[] = 'page';

        $where[] = 'post_status = ?';
        $params[] = 'publish';

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT p.*, u.user_login, u.user_fullname 
                FROM tbl_posts p 
                LEFT JOIN tbl_users u ON p.post_author = u.ID 
                {$whereClause}
                ORDER BY p.post_date DESC";

        $stmt = $this->dbc->dbQuery($sql, $params);
        $pages = $stmt->fetchAll();

        // Process each page to get associated data
        foreach ($pages as &$page) {
            // Pages typically don't have categories/tags in Blogware, but we'll get them anyway
            $page['categories'] = $this->getPostCategories($page['ID']);
            $page['tags'] = $this->getPostTags($page['post_tags']);
            // Pages usually don't have comments, but we'll get them if they exist
            $page['comments'] = $this->getPostComments($page['ID']);
        }

        return $pages;
    }

    /**
     * Get categories/topics for export
     *
     * @return array
     */
    protected function getCategories()
    {
        $sql = "SELECT ID, topic_title, topic_slug 
                FROM tbl_topics 
                WHERE topic_status = 'Y'
                ORDER BY topic_title";

        $stmt = $this->dbc->dbQuery($sql, []);
        return $stmt->fetchAll();
    }

    /**
     * Get categories associated with a post
     *
     * @param int $postId
     * @return array
     */
    protected function getPostCategories($postId)
    {
        $sql = "SELECT t.ID, t.topic_title, t.topic_slug 
                FROM tbl_topics t
                INNER JOIN tbl_post_topic pt ON t.ID = pt.topic_id
                WHERE pt.post_id = ?
                ORDER BY t.topic_title";

        $stmt = $this->dbc->dbQuery($sql, [$postId]);
        return $stmt->fetchAll();
    }

    /**
     * Get tags from post_tags field (comma-separated)
     *
     * @param string $tagsField
     * @return array
     */
    protected function getPostTags($tagsField)
    {
        if (empty($tagsField)) {
            return [];
        }

        $tags = explode(',', $tagsField);
        $tags = array_map('trim', $tags);
        $tags = array_filter($tags);

        return $tags;
    }

    /**
     * Get comments for a post
     *
     * @param int $postId
     * @return array
     */
    protected function getPostComments($postId)
    {
        $sql = "SELECT * 
                FROM tbl_comments 
                WHERE comment_post_id = ?
                AND comment_status = 'approved'
                ORDER BY comment_date ASC";

        $stmt = $this->dbc->dbQuery($sql, [$postId]);
        return $stmt->fetchAll();
    }

    /**
     * Sanitize input for export
     *
     * @param string $input
     * @return string
     */
    protected function sanitizeInput($input)
    {
        if (empty($input)) {
            return '';
        }

        return Sanitize::mildSanitizer($input);
    }
}
