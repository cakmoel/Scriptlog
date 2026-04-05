<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Scriptlog Exporter Utility
 *
 * Exports Blogware/Scriptlog content to native Scriptlog JSON format
 * This format can be imported directly into another Scriptlog installation
 *
 * @category  Utility
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ScriptlogExporter
{
    private $siteUrl;
    private $siteTitle;
    private $siteDescription;
    private $version = '1.0';

    public function __construct()
    {
        $this->siteUrl = isset($_SERVER['HTTP_HOST']) ?
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" :
            'http://example.com';
        $this->siteTitle = 'Blogware Site';
        $this->siteDescription = 'Just another Scriptlog site';
    }

    /**
     * Export content to Scriptlog JSON format
     *
     * @param array $exportStats Reference to stats array to update
     * @param int|null $authorId Filter by author ID (null for all)
     * @return string Scriptlog JSON content
     * @throws ExportException
     */
    public function export(&$exportStats, $authorId = null)
    {
        $users = $this->getUsers($authorId);
        $topics = $this->getTopics();
        $posts = $this->getPosts($authorId);
        $pages = $this->getPages($authorId);
        $comments = $this->getComments();
        $menus = $this->getMenus();
        $settings = $this->getSettings();

        $exportData = [
            '_meta' => [
                'version' => $this->version,
                'exported_at' => gmdate('Y-m-d H:i:s'),
                'exported_from' => $this->siteUrl,
                'format' => 'scriptlog-native'
            ],
            'site' => [
                'title' => $this->siteTitle,
                'description' => $this->siteDescription,
                'url' => $this->siteUrl
            ],
            'users' => $users,
            'topics' => $topics,
            'posts' => $posts,
            'pages' => $pages,
            'comments' => $comments,
            'menus' => $menus,
            'settings' => $settings,
            '_post_topic' => $this->getPostTopics()
        ];

        foreach ($posts as $post) {
            $exportStats['posts_exported']++;
        }

        foreach ($pages as $page) {
            $exportStats['pages_exported']++;
        }

        foreach ($topics as $topic) {
            $exportStats['categories_exported']++;
        }

        foreach ($comments as $comment) {
            $exportStats['comments_exported']++;
        }

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

        $sql = "SELECT ID, user_login, user_email, user_level, user_fullname, user_url, 
                       user_registered, user_signin_count
                FROM tbl_users 
                {$whereClause}
                ORDER BY ID";

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, $params);
        $users = $stmt->fetchAll();

        foreach ($users as &$user) {
            $user['user_pass'] = ''; // Don't export passwords
            unset($user['user_activation_key']);
            unset($user['user_session']);
            unset($user['user_banned']);
            unset($user['user_locked_until']);
        }

        return $users;
    }

    /**
     * Get topics/categories for export
     *
     * @return array
     */
    private function getTopics()
    {
        $sql = "SELECT ID, topic_title, topic_slug, topic_status
                FROM tbl_topics 
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
        $params[] = 'blog';

        $where[] = 'post_status = ?';
        $params[] = 'publish';

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT p.*, u.user_login, u.user_fullname, u.user_email
                FROM tbl_posts p 
                LEFT JOIN tbl_users u ON p.post_author = u.ID 
                {$whereClause}
                ORDER BY p.post_date DESC";

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, $params);
        return $stmt->fetchAll();
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

        $sql = "SELECT p.*, u.user_login, u.user_fullname, u.user_email
                FROM tbl_posts p 
                LEFT JOIN tbl_users u ON p.post_author = u.ID 
                {$whereClause}
                ORDER BY p.post_date DESC";

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get comments for export
     *
     * @return array
     */
    private function getComments()
    {
        $sql = "SELECT c.*, p.post_title
                FROM tbl_comments c
                LEFT JOIN tbl_posts p ON c.comment_post_id = p.ID
                WHERE c.comment_status = 'approved'
                ORDER BY c.comment_date ASC";

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, []);
        return $stmt->fetchAll();
    }

    /**
     * Get menus for export
     *
     * @return array
     */
    private function getMenus()
    {
        $sql = "SELECT * FROM tbl_menu ORDER BY menu_sort";

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, []);
        return $stmt->fetchAll();
    }

    /**
     * Get post-topic relationships
     *
     * @return array
     */
    private function getPostTopics()
    {
        $sql = "SELECT * FROM tbl_post_topic";

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, []);
        return $stmt->fetchAll();
    }

    /**
     * Get settings for export
     *
     * @return array
     */
    private function getSettings()
    {
        $sql = "SELECT setting_name, setting_value FROM tbl_settings";

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, []);
        $settings = $stmt->fetchAll();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_name']] = $setting['setting_value'];
        }

        return $result;
    }
}
