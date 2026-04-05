<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * WordPress WXR Exporter Utility
 *
 * Exports Blogware content to WordPress eXtended RSS (WXR) format
 *
 * @category  Utility
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class WordPressExporter
{
    private $wxrVersion = '1.2';
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
     * Export content to WXR format
     *
     * @param array $exportStats Reference to stats array to update
     * @param int|null $authorId Filter by author ID (null for all)
     * @return string WXR XML content
     * @throws ExportException
     */
    public function export(&$exportStats, $authorId = null)
    {
        $authors = $this->getAuthors($authorId);
        $categories = $this->getCategories();
        $posts = $this->getPosts($authorId);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0"' . "\n";
        $xml .= "\t" . 'xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"' . "\n";
        $xml .= "\t" . 'xmlns:content="http://purl.org/rss/1.0/modules/content/"' . "\n";
        $xml .= "\t" . 'xmlns:wfw="http://wellformedweb.org/CommentAPI/"' . "\n";
        $xml .= "\t" . 'xmlns:dc="http://purl.org/dc/elements/1.1/"' . "\n";
        $xml .= "\t" . 'xmlns:wp="http://wordpress.org/export/1.2/"' . "\n";
        $xml .= '>' . "\n";
        $xml .= '<channel>' . "\n";
        $xml .= "\t" . '<title>' . htmlspecialchars($this->siteTitle, ENT_QUOTES) . '</title>' . "\n";
        $xml .= "\t" . '<link>' . htmlspecialchars($this->siteUrl, ENT_QUOTES) . '</link>' . "\n";
        $xml .= "\t" . '<description>Just another Blogware site</description>' . "\n";
        $xml .= "\t" . '<pubDate>' . gmdate('D, d M Y H:i:s +0000') . '</pubDate>' . "\n";
        $xml .= "\t" . '<language>en-US</language>' . "\n";
        $xml .= "\t" . '<wp:wxr_version>' . $this->wxrVersion . '</wp:wxr_version>' . "\n";
        $xml .= "\t" . '<wp:base_site_url>' . htmlspecialchars($this->siteUrl, ENT_QUOTES) . '</wp:base_site_url>' . "\n";
        $xml .= "\t" . '<wp:base_blog_url>' . htmlspecialchars($this->siteUrl, ENT_QUOTES) . '</wp:base_blog_url>' . "\n";

        foreach ($authors as $author) {
            $xml .= "\t" . '<wp:author>' . "\n";
            $xml .= "\t\t" . '<wp:author_id>' . $author['ID'] . '</wp:author_id>' . "\n";
            $xml .= "\t\t" . '<wp:author_login><![CDATA[' . htmlspecialchars($author['user_login'], ENT_QUOTES) . ']]></wp:author_login>' . "\n";
            $xml .= "\t\t" . '<wp:author_email><![CDATA[' . htmlspecialchars($author['user_email'], ENT_QUOTES) . ']]></wp:author_email>' . "\n";
            $xml .= "\t\t" . '<wp:author_display_name><![CDATA[' . htmlspecialchars($author['user_fullname'] ?: $author['user_login'], ENT_QUOTES) . ']]></wp:author_display_name>' . "\n";
            $xml .= "\t\t" . '<wp:author_first_name><![CDATA[' . htmlspecialchars($author['user_fullname'] ?: '', ENT_QUOTES) . ']]></wp:author_first_name>' . "\n";
            $xml .= "\t\t" . '<wp:author_last_name><![CDATA[]]></wp:author_last_name>' . "\n";
            $xml .= "\t" . '</wp:author>' . "\n";
        }

        foreach ($categories as $category) {
            $xml .= "\t" . '<wp:category>' . "\n";
            $xml .= "\t\t" . '<wp:term_id>' . $category['ID'] . '</wp:term_id>' . "\n";
            $xml .= "\t\t" . '<wp:category_nicename><![CDATA[' . htmlspecialchars($category['topic_slug'], ENT_QUOTES) . ']]></wp:category_nicename>' . "\n";
            $xml .= "\t\t" . '<wp:category_parent><![CDATA[]]></wp:category_parent>' . "\n";
            $xml .= "\t\t" . '<wp:cat_name><![CDATA[' . htmlspecialchars($category['topic_title'], ENT_QUOTES) . ']]></wp:cat_name>' . "\n";
            $xml .= "\t" . '</wp:category>' . "\n";
            $exportStats['categories_exported']++;
        }

        foreach ($posts as $post) {
            $xml .= "\t" . '<item>' . "\n";
            $xml .= "\t\t" . '<title><![CDATA[' . htmlspecialchars($post['post_title'], ENT_QUOTES) . ']]></title>' . "\n";
            $xml .= "\t\t" . '<link>' . htmlspecialchars($this->siteUrl . '/post/' . $post['ID'] . '/' . $post['post_slug'], ENT_QUOTES) . '</link>' . "\n";
            $xml .= "\t\t" . '<pubDate>' . gmdate('D, d M Y H:i:s +0000', strtotime($post['post_date'])) . '</pubDate>' . "\n";
            $xml .= "\t\t" . '<dc:creator><![CDATA[' . htmlspecialchars($post['user_login'], ENT_QUOTES) . ']]></dc:creator>' . "\n";
            $xml .= "\t\t" . '<guid isPermaLink="false">' . $this->siteUrl . '/?p=' . $post['ID'] . '</guid>' . "\n";
            $xml .= "\t\t" . '<description></description>' . "\n";
            $xml .= "\t\t" . '<content:encoded><![CDATA[' . $post['post_content'] . ']]></content:encoded>' . "\n";
            $xml .= "\t\t" . '<excerpt:encoded><![CDATA[' . htmlspecialchars($post['post_summary'], ENT_QUOTES) . ']]></excerpt:encoded>' . "\n";
            $xml .= "\t\t" . '<wp:post_id>' . $post['ID'] . '</wp:post_id>' . "\n";
            $xml .= "\t\t" . '<wp:post_date>' . gmdate('Y-m-d H:i:s', strtotime($post['post_date'])) . '</wp:post_date>' . "\n";
            $xml .= "\t\t" . '<wp:post_date_gmt>' . gmdate('Y-m-d H:i:s', strtotime($post['post_date'])) . '</wp:post_date_gmt>' . "\n";
            $xml .= "\t\t" . '<wp:post_type>' . htmlspecialchars($post['post_type'], ENT_QUOTES) . '</wp:post_type>' . "\n";
            $xml .= "\t\t" . '<wp:post_status>' . htmlspecialchars($post['post_status'], ENT_QUOTES) . '</wp:post_status>' . "\n";
            $xml .= "\t\t" . '<wp:post_name><![CDATA[' . htmlspecialchars($post['post_slug'], ENT_QUOTES) . ']]></wp:post_name>' . "\n";
            $xml .= "\t\t" . '<wp:post_parent>' . ($post['media_id'] ?? 0) . '</wp:post_parent>' . "\n";
            $xml .= "\t\t" . '<wp:menu_order>0</wp:menu_order>' . "\n";
            $xml .= "\t\t" . '<wp:post_password><![CDATA[' . htmlspecialchars($post['post_password'] ?? '', ENT_QUOTES) . ']]></wp:post_password>' . "\n";
            $xml .= "\t\t" . '<wp:is_sticky>0</wp:is_sticky>' . "\n";

            foreach ($post['categories'] as $category) {
                $xml .= "\t\t\t" . '<wp:category><![CDATA[' . htmlspecialchars($category['topic_slug'], ENT_QUOTES) . ']]></wp:category>' . "\n";
            }

            foreach ($post['tags'] as $tag) {
                $xml .= "\t\t\t" . '<wp:tag><![CDATA[' . htmlspecialchars($tag, ENT_QUOTES) . ']]></wp:tag>' . "\n";
            }

            $xml .= "\t\t" . '<wp:comment_status>' . htmlspecialchars($post['comment_status'], ENT_QUOTES) . '</wp:comment_status>' . "\n";
            $xml .= "\t\t" . '<wp:ping_status>open</wp:ping_status>' . "\n";

            foreach ($post['comments'] as $comment) {
                $xml .= "\t\t\t" . '<wp:comment>' . "\n";
                $xml .= "\t\t\t\t" . '<wp:comment_id>' . $comment['ID'] . '</wp:comment_id>' . "\n";
                $xml .= "\t\t\t\t" . '<wp:comment_author><![CDATA[' . htmlspecialchars($comment['comment_author_name'], ENT_QUOTES) . ']]></wp:comment_author>' . "\n";
                $xml .= "\t\t\t\t" . '<wp:comment_author_email>' . htmlspecialchars($comment['comment_author_email'] ?? '', ENT_QUOTES) . '</wp:comment_author_email>' . "\n";
                $xml .= "\t\t\t\t" . '<wp:comment_author_url>' . htmlspecialchars($comment['comment_author_url'] ?? '', ENT_QUOTES) . '</wp:comment_author_url>' . "\n";
                $xml .= "\t\t\t\t" . '<wp:comment_author_IP>' . htmlspecialchars($comment['comment_author_ip'], ENT_QUOTES) . '</wp:comment_author_IP>' . "\n";
                $xml .= "\t\t\t\t" . '<wp:comment_date>' . gmdate('Y-m-d H:i:s', strtotime($comment['comment_date'])) . '</wp:comment_date>' . "\n";
                $xml .= "\t\t\t\t" . '<wp:comment_date_gmt>' . gmdate('Y-m-d H:i:s', strtotime($comment['comment_date'])) . '</wp:comment_date_gmt>' . "\n";
                $xml .= "\t\t\t\t" . '<wp:comment_content><![CDATA[' . $comment['comment_content'] . ']]></wp:comment_content>' . "\n";
                $xml .= "\t\t\t\t" . '<wp:comment_approved>' . ($comment['comment_status'] === 'approved' ? 1 : 0) . '</wp:comment_approved>' . "\n";
                $xml .= "\t\t\t\t" . '<wp:comment_type></wp:comment_type>' . "\n";
                $xml .= "\t\t\t\t" . '<wp:comment_parent>' . ($comment['comment_parent_id'] ?? 0) . '</wp:comment_parent>' . "\n";
                $xml .= "\t\t\t\t" . '<wp:comment_user_id>' . ($comment['user_id'] ?? 0) . '</wp:comment_user_id>' . "\n";
                $xml .= "\t\t\t" . '</wp:comment>' . "\n";
                $exportStats['comments_exported']++;
            }

            $xml .= "\t" . '</item>' . "\n";

            if ($post['post_type'] === 'page') {
                $exportStats['pages_exported']++;
            } else {
                $exportStats['posts_exported']++;
            }
        }

        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Get authors for export
     *
     * @param int|null $authorId
     * @return array
     */
    private function getAuthors($authorId = null)
    {
        $where = [];
        $params = [];

        if ($authorId !== null) {
            $where[] = 'ID = ?';
            $params[] = $authorId;
        }

        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        $sql = "SELECT ID, user_login, user_email, user_fullname 
                FROM tbl_users 
                {$whereClause}
                ORDER BY user_login";

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Get categories for export
     *
     * @return array
     */
    private function getCategories()
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
     * Get categories associated with a post
     *
     * @param int $postId
     * @return array
     */
    private function getPostCategories($postId)
    {
        $sql = "SELECT t.ID, t.topic_title, t.topic_slug 
                FROM tbl_topics t
                INNER JOIN tbl_post_topic pt ON t.ID = pt.topic_id
                WHERE pt.post_id = ?
                ORDER BY t.topic_title";

        $dbc = Registry::get('dbc');
        $stmt = $dbc->dbQuery($sql, [$postId]);
        return $stmt->fetchAll();
    }

    /**
     * Get tags from post_tags field (comma-separated)
     *
     * @param string $tagsField
     * @return array
     */
    private function getPostTags($tagsField)
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
}
