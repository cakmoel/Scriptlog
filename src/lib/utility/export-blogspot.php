<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Blogspot Exporter Utility
 *
 * Exports Blogware content to Blogspot/Blogger XML format
 *
 * @category  Utility
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class BlogspotExporter
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
     * Export content to Blogspot XML format
     *
     * @param array $exportStats Reference to stats array to update
     * @param int|null $authorId Filter by author ID (null for all)
     * @return string Blogspot XML content
     * @throws ExportException
     */
    public function export(&$exportStats, $authorId = null)
    {
        $posts = $this->getPosts($authorId);
        $categories = $this->getCategories();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<feed xmlns="http://www.w3.org/2005/Atom"' . "\n";
        $xml .= '      xmlns:openSearch="http://a9.com/-/spec/opensearch/1.1/"' . "\n";
        $xml .= '      xmlns:blogger="http://schemas.google.com/blogger/2008"' . "\n";
        $xml .= '      xmlns:georss="http://www.georss.org/georss"' . "\n";
        $xml .= '      xmlns:gd="http://schemas.google.com/g/2005"' . "\n";
        $xml .= '      gd:etag="feed/etag">' . "\n";
        $xml .= '  <id>tag:blogger.com,1999:blog-' . md5($this->siteUrl) . '</id>' . "\n";
        $xml .= '  <title>' . htmlspecialchars($this->siteTitle, ENT_QUOTES) . '</title>' . "\n";
        $xml .= '  <subtitle type="text">Just another Blogware site</subtitle>' . "\n";
        $xml .= '  <updated>' . gmdate('Y-m-d\TH:i:s\Z') . '</updated>' . "\n";
        $xml .= '  <category scheme="http://schemas.google.com/g/2005#kind"' . "\n";
        $xml .= '            term="http://schemas.google.com/blogger/2008/kind#post"/>' . "\n";
        $xml .= '  <link rel="http://schemas.google.com/g/2005#feed"' . "\n";
        $xml .= '        type="application/atom+xml"' . "\n";
        $xml .= '        href="' . htmlspecialchars($this->siteUrl . '/feeds/posts/default', ENT_QUOTES) . '"/>' . "\n";
        $xml .= '  <link rel="http://schemas.google.com/g/2005#post"' . "\n";
        $xml .= '        type="application/atom+xml"' . "\n";
        $xml .= '        href="' . htmlspecialchars($this->siteUrl . '/feeds/posts/default', ENT_QUOTES) . '"/>' . "\n";
        $xml .= '  <link rel="self" type="application/atom+xml"' . "\n";
        $xml .= '        href="' . htmlspecialchars($this->siteUrl . '/feeds/posts/default', ENT_QUOTES) . '"/>' . "\n";
        $xml .= '  <link rel="alternate" type="text/html"' . "\n";
        $xml .= '        href="' . htmlspecialchars($this->siteUrl, ENT_QUOTES) . '"/>' . "\n";

        foreach ($posts as $post) {
            $xml .= '  <entry>' . "\n";
            $xml .= '    <id>tag:blogger.com,1999:blog-' . md5($this->siteUrl) . '.post-' . $post['ID'] . '</id>' . "\n";
            $xml .= '    <published>' . gmdate('Y-m-d\TH:i:s\Z', strtotime($post['post_date'])) . '</published>' . "\n";
            $xml .= '    <updated>' . gmdate('Y-m-d\TH:i:s\Z', strtotime($post['post_modified'] ?? $post['post_date'])) . '</updated>' . "\n";
            $xml .= '    <category scheme="http://www.blogger.com/atom/ns#"' . "\n";
            $xml .= '              term="' . htmlspecialchars(implode(' ', $this->getPostTagsAsString($post['post_tags'])), ENT_QUOTES) . '"/>' . "\n";
            $xml .= '    <title type="text">' . htmlspecialchars($post['post_title'], ENT_QUOTES) . '</title>' . "\n";
            $xml .= '    <content type="html"><![CDATA[' . $post['post_content'] . ']]></content>' . "\n";
            $xml .= '    <link rel="alternate" type="text/html"' . "\n";
            $xml .= '          href="' . htmlspecialchars($this->siteUrl . '/post/' . $post['ID'] . '/' . $post['post_slug'], ENT_QUOTES) . '"/>' . "\n";
            $xml .= '    <author>' . "\n";
            $xml .= '      <name>' . htmlspecialchars($post['user_fullname'] ?: $post['user_login'], ENT_QUOTES) . '</name>' . "\n";
            $xml .= '      <email>' . htmlspecialchars($post['user_email'], ENT_QUOTES) . '</email>' . "\n";
            $xml .= '    </author>' . "\n";

            foreach ($post['comments'] as $comment) {
                $xml .= '    <thr:in-reply-to xmlns:thr="http://purl.org/syndication/thread/1.0"' . "\n";
                $xml .= '                     ref="tag:blogger.com,1999:blog-' . md5($this->siteUrl) . '.post-' . $post['ID'] . '"' . "\n";
                $xml .= '                     href="' . htmlspecialchars($this->siteUrl . '/post/' . $post['ID'] . '/' . $post['post_slug'], ENT_QUOTES) . '"' . "\n";
                $xml .= '                     type="application/atom+xml"' . "\n";
                $xml .= '                     source="">' . "\n";
                $xml .= '      <thr:content type="html"><![CDATA[' . $comment['comment_content'] . ']]></thr:content>' . "\n";
                $xml .= '      <thr:author>' . "\n";
                $xml .= '        <thr:name>' . htmlspecialchars($comment['comment_author_name'], ENT_QUOTES) . '</thr:name>' . "\n";
                $xml .= '      </thr:author>' . "\n";
                $xml .= '      <thr:published>' . gmdate('Y-m-d\TH:i:s\Z', strtotime($comment['comment_date'])) . '</thr:published>' . "\n";
                $xml .= '      <thr:updated>' . gmdate('Y-m-d\TH:i:s\Z', strtotime($comment['comment_date'])) . '</thr:updated>' . "\n";
                $xml .= '    </thr:in-reply-to>' . "\n";
                $exportStats['comments_exported']++;
            }

            $xml .= '  </entry>' . "\n";

            if ($post['post_type'] === 'page') {
                $exportStats['pages_exported']++;
            } else {
                $exportStats['posts_exported']++;
            }
        }

        foreach ($categories as $category) {
            $xml .= '  <entry>' . "\n";
            $xml .= '    <id>tag:blogger.com,1999:blog-' . md5($this->siteUrl) . '.label-' . $category['ID'] . '</id>' . "\n";
            $xml .= '    <published>' . gmdate('Y-m-d\TH:i:s\Z') . '</published>' . "\n";
            $xml .= '    <updated>' . gmdate('Y-m-d\TH:i:s\Z') . '</updated>' . "\n";
            $xml .= '    <category scheme="http://schemas.google.com/g/2005#kind"' . "\n";
            $xml .= '              term="http://schemas.google.com/blogger/2008/kind#label"/>' . "\n";
            $xml .= '    <title type="text">' . htmlspecialchars($category['topic_title'], ENT_QUOTES) . '</title>' . "\n";
            $xml .= '    <content type="text"></content>' . "\n";
            $xml .= '    <author>' . "\n";
            $xml .= '      <name>' . htmlspecialchars($this->siteTitle, ENT_QUOTES) . '</name>' . "\n";
            $xml .= '    </author>' . "\n";
            $xml .= '  </entry>' . "\n";
            $exportStats['categories_exported']++;
        }

        $xml .= '</feed>';

        return $xml;
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

        $sql = "SELECT p.*, u.user_login, u.user_fullname, u.user_email 
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
     * Convert post_tags field to array of tags
     *
     * @param string $tagsField
     * @return array
     */
    private function getPostTagsAsString($tagsField)
    {
        if (empty($tagsField)) {
            return [];
        }

        $tags = explode(',', $tagsField);
        $tags = array_map('trim', $tags);
        $tags = array_filter($tags);

        return $tags;
    }
}
