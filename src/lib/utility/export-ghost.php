<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Ghost Exporter Utility
 *
 * Exports Blogware content to Ghost JSON format
 *
 * @category  Utility
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class GhostExporter
{
    private $siteUrl;
    private $siteTitle;

    public function __construct()
    {
        // Get site info from services or config
        $this->siteUrl = isset($_SERVER['HTTP_HOST']) ?
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" :
            'http://example.com';
        $this->siteTitle = 'Blogware Site'; // Could be fetched from settings
    }

    /**
     * Export content to Ghost JSON format
     *
     * @param array $exportStats Reference to stats array to update
     * @param int|null $authorId Filter by author ID (null for all)
     * @return string Ghost JSON content
     * @throws ExportException
     */
    public function export(&$exportStats, $authorId = null)
    {
        $posts = $this->getPosts($authorId);
        $pages = $this->getPages($authorId);
        $tags = $this->getTags();
        $users = $this->getUsers($authorId);

        // Prepare Ghost export structure
        $exportData = [
            'meta' => [
                'exported_on' => gmdate('Y-m-d H:i:s'),
                'version' => '0.3.1'
            ],
            'data' => [
                'users' => [],
                'posts' => [],
                'pages' => [],
                'tags' => []
            ]
        ];

        // Export users
        foreach ($users as $user) {
            $exportData['data']['users'][] = [
                'id' => $user['ID'],
                'name' => $user['user_fullname'] ?: $user['user_login'],
                'email' => $user['user_email'],
                'slug' => make_slug($user['user_login']),
                'password' => '', // Ghost will generate its own password
                'status' => 'active',
                'role' => $this->mapUserRole($user['user_level']),
                'profile_image' => '',
                'cover_image' => '',
                'bio' => '',
                'website' => $user['user_url'] ?? '',
                'facebook' => '',
                'twitter' => '',
                'accessibility' => '',
                'visibility' => 'public',
                'created_at' => gmdate('Y-m-d H:i:s', strtotime($user['user_registered'])),
                'created_by' => 1,
                'updated_at' => gmdate('Y-m-d H:i:s', strtotime($user['user_registered'])),
                'updated_by' => 1
            ];
        }

        // Export tags
        foreach ($tags as $tag) {
            $exportData['data']['tags'][] = [
                'id' => $tag['ID'],
                'name' => $tag['topic_title'],
                'slug' => $tag['topic_slug'],
                'description' => '',
                'feature_image' => '',
                'hidden' => false,
                'parent_id' => null,
                'created_at' => gmdate('Y-m-d H:i:s'),
                'created_by' => 1,
                'updated_at' => gmdate('Y-m-d H:i:s'),
                'updated_by' => 1
            ];
        }

        // Export posts
        foreach ($posts as $post) {
            $exportData['data']['posts'][] = [
                'id' => $post['ID'],
                'uuid' => $this->generateUuid(),
                'title' => $post['post_title'],
                'slug' => $post['post_slug'],
                'markdown' => $this->convertHtmlToMarkdown($post['post_content']),
                'html' => $post['post_content'],
                'image' => null,
                'featured' => 0,
                'page' => 0,
                'status' => $this->mapPostStatus($post['post_status']),
                'language' => 'en_US',
                'meta_title' => null,
                'meta_description' => null,
                'author_id' => $post['post_author'],
                'created_at' => gmdate('Y-m-d H:i:s', strtotime($post['post_date'])),
                'created_by' => $post['post_author'],
                'updated_at' => gmdate('Y-m-d H:i:s', strtotime($post['post_modified'] ?? $post['post_date'])),
                'updated_by' => $post['post_author'],
                'published_at' => $post['post_status'] === 'publish' ? gmdate('Y-m-d H:i:s', strtotime($post['post_date'])) : null,
                'custom_excerpt' => $post['post_summary'],
                'codeinjection_head' => '',
                'codeinjection_foot' => '',
                'custom_template' => null
            ];
            $exportStats['posts_exported']++;
        }

        // Export pages
        foreach ($pages as $page) {
            $exportData['data']['pages'][] = [
                'id' => $page['ID'],
                'uuid' => $this->generateUuid(),
                'title' => $page['post_title'],
                'slug' => $page['post_slug'],
                'markdown' => $this->convertHtmlToMarkdown($page['post_content']),
                'html' => $page['post_content'],
                'image' => null,
                'featured' => 0,
                'page' => 1,
                'status' => $this->mapPostStatus($page['post_status']),
                'language' => 'en_US',
                'meta_title' => null,
                'meta_description' => null,
                'author_id' => $page['post_author'],
                'created_at' => gmdate('Y-m-d H:i:s', strtotime($page['post_date'])),
                'created_by' => $page['post_author'],
                'updated_at' => gmdate('Y-m-d H:i:s', strtotime($page['post_modified'] ?? $page['post_date'])),
                'updated_by' => $page['post_author'],
                'published_at' => $page['post_status'] === 'publish' ? gmdate('Y-m-d H:i:s', strtotime($page['post_date'])) : null,
                'custom_excerpt' => '',
                'codeinjection_head' => '',
                'codeinjection_foot' => '',
                'custom_template' => null
            ];
            $exportStats['pages_exported']++;
        }

        // Count categories as tags in Ghost
        $exportStats['tags_exported'] = count($tags);

        return json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get users for export
     *
     * @param int|null $authorId
     * @return array
     */
    private function getUsers($authorId = null)
    {
        $where = [];
        $params = [];

        if ($authorId !== null) {
            $where[] = 'ID = ?';
            $params[] = $authorId;
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT ID, user_login, user_email, user_fullname, user_url, user_registered 
                FROM tbl_users 
                {$whereClause}
                ORDER BY user_login";

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get tags for export
     *
     * @return array
     */
    private function getTags()
    {
        $sql = "SELECT ID, topic_title, topic_slug 
                FROM tbl_topics 
                WHERE topic_status = 'Y'
                ORDER BY topic_title";

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, []);
        return $stmt->fetchAll();
    }

    /**
     * Get posts for export
     *
     * @param int|null $authorId
     * @return array
     */
    private function getPosts($authorId = null)
    {
        $where = [];
        $params = [];

        if ($authorId !== null) {
            $where[] = 'post_author = ?';
            $params[] = $authorId;
        }

        $where[] = 'post_type = ?';
        $params[] = 'post';

        $where[] = 'post_status = ?';
        $params[] = 'publish';

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT p.*, u.user_login, u.user_fullname 
                FROM tbl_posts p 
                LEFT JOIN tbl_users u ON p.post_author = u.ID 
                {$whereClause}
                ORDER BY p.post_date DESC";

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, $params);
        $posts = $stmt->fetchAll();

        // Process each post to get associated data
        foreach ($posts as &$post) {
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
    private function getPages($authorId = null)
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

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get comments for a post
     *
     * @param int $postId
     * @return array
     */
    private function getPostComments($postId)
    {
        $sql = "SELECT * 
                FROM tbl_comments 
                WHERE comment_post_id = ?
                AND comment_status = 'approved'
                ORDER BY comment_date ASC";

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, [$postId]);
        return $stmt->fetchAll();
    }

    /**
     * Map Blogware user level to Ghost role
     *
     * @param string $userLevel
     * @return string
     */
    private function mapUserRole($userLevel)
    {
        $roleMap = [
            'administrator' => 'Administrator',
            'manager' => 'Administrator',
            'editor' => 'Editor',
            'author' => 'Author',
            'contributor' => 'Author',
            'subscriber' => 'Subscriber'
        ];

        return $roleMap[$userLevel] ?? 'Author';
    }

    /**
     * Map Blogware post status to Ghost status
     *
     * @param string $postStatus
     * @return string
     */
    private function mapPostStatus($postStatus)
    {
        $statusMap = [
            'publish' => 'published',
            'draft' => 'draft',
            'pending' => 'draft',
            'private' => 'draft'
        ];

        return $statusMap[$postStatus] ?? 'draft';
    }

    /**
     * Convert HTML to Markdown (simplified)
     *
     * @param string $html
     * @return string
     */
    private function convertHtmlToMarkdown($html)
    {
        // This is a simplified conversion - in reality you'd use a proper library
        // For now, we'll just return the HTML as Ghost accepts HTML
        return $html;
    }

    /**
     * Generate a UUID-like string
     *
     * @return string
     */
    private function generateUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
