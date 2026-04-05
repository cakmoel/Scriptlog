<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class SearchFinder
 * Searching keyword from search functionality form
 *
 * @category  Core Class
 * @author    Maoelana Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class SearchFinder
{
    /**
     * Database connection
     *
     * @var Db
     */
    private $dbc;

    /**
     * Error message
     *
     * @var string
     */
    protected $error;

    /**
     * Initialize object properties and method
     * and an instance of database connection
     */
    public function __construct()
    {
        if (Registry::isKeySet('dbc')) {
            $this->dbc = Registry::get('dbc');
        }
    }

    public function __destruct()
    {
        session_write_close();
    }

    /**
     * Get error message
     *
     * @return string|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Sanitize search keyword
     * Prevents XSS and SQL injection
     *
     * @param string $keyword
     * @return string
     */
    public function sanitizeKeyword($keyword)
    {
        if (!is_string($keyword)) {
            return '';
        }

        $keyword = trim($keyword);

        if (mb_strlen($keyword, 'UTF-8') < 2) {
            return '';
        }

        if (mb_strlen($keyword, 'UTF-8') > 100) {
            $keyword = mb_substr($keyword, 0, 100, 'UTF-8');
        }

        return $keyword;
    }

    /**
     * Search posts
     *
     * @param string $keyword
     * @return array
     */
    public function searchPost($keyword)
    {
        $keyword = $this->sanitizeKeyword($keyword);

        if (empty($keyword)) {
            return ['results' => [], 'totalRows' => 0];
        }

        try {
            $searchTerm = "%{$keyword}%";

            $sql = "SELECT ID, post_author, post_date, post_modified, 
                           post_title, post_slug, post_content, 
                           post_status, post_type
                    FROM tbl_posts 
                    WHERE (post_title LIKE ? OR post_content LIKE ? OR post_tags LIKE ?) 
                    AND post_status = 'publish' 
                    AND post_type = 'blog'
                    ORDER BY post_date DESC 
                    LIMIT 20";

            $results = $this->dbc->dbSelect($sql, [$searchTerm, $searchTerm, $searchTerm]);

            $countSql = "SELECT COUNT(*) as total 
                         FROM tbl_posts 
                         WHERE (post_title LIKE ? OR post_content LIKE ? OR post_tags LIKE ?) 
                         AND post_status = 'publish' 
                         AND post_type = 'blog'";

            $countResult = $this->dbc->dbSelect($countSql, [$searchTerm, $searchTerm, $searchTerm]);
            $totalRows = isset($countResult[0]->total) ? (int)$countResult[0]->total : 0;

            return [
                'results' => $results ?: [],
                'totalRows' => $totalRows
            ];
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return ['results' => [], 'totalRows' => 0, 'error' => $this->error];
        }
    }

    /**
     * Search pages
     *
     * @param string $keyword
     * @return array
     */
    public function searchPage($keyword)
    {
        $keyword = $this->sanitizeKeyword($keyword);

        if (empty($keyword)) {
            return ['results' => [], 'totalRows' => 0];
        }

        try {
            $searchTerm = "%{$keyword}%";

            $sql = "SELECT ID, post_author, post_date, post_modified, 
                           post_title, post_slug, post_content, 
                           post_status, post_type
                    FROM tbl_posts 
                    WHERE (post_title LIKE ? OR post_content LIKE ?) 
                    AND post_status = 'publish' 
                    AND post_type = 'page'
                    ORDER BY post_date DESC 
                    LIMIT 20";

            $results = $this->dbc->dbSelect($sql, [$searchTerm, $searchTerm]);

            $countSql = "SELECT COUNT(*) as total 
                         FROM tbl_posts 
                         WHERE (post_title LIKE ? OR post_content LIKE ?) 
                         AND post_status = 'publish' 
                         AND post_type = 'page'";

            $countResult = $this->dbc->dbSelect($countSql, [$searchTerm, $searchTerm]);
            $totalRows = isset($countResult[0]->total) ? (int)$countResult[0]->total : 0;

            return [
                'results' => $results ?: [],
                'totalRows' => $totalRows
            ];
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return ['results' => [], 'totalRows' => 0, 'error' => $this->error];
        }
    }

    /**
     * Search both posts and pages
     *
     * @param string $keyword
     * @return array
     */
    public function searchAll($keyword)
    {
        $keyword = $this->sanitizeKeyword($keyword);

        if (empty($keyword)) {
            return ['results' => [], 'totalRows' => 0];
        }

        try {
            $searchTerm = "%{$keyword}%";

            $sql = "SELECT ID, post_author, post_date, post_modified, 
                           post_title, post_slug, post_content, 
                           post_status, post_type
                    FROM tbl_posts 
                    WHERE (post_title LIKE ? OR post_content LIKE ? OR post_tags LIKE ?) 
                    AND post_status = 'publish'
                    ORDER BY post_date DESC 
                    LIMIT 50";

            $results = $this->dbc->dbSelect($sql, [$searchTerm, $searchTerm, $searchTerm]);

            $countSql = "SELECT COUNT(*) as total 
                         FROM tbl_posts 
                         WHERE (post_title LIKE ? OR post_content LIKE ? OR post_tags LIKE ?) 
                         AND post_status = 'publish'";

            $countResult = $this->dbc->dbSelect($countSql, [$searchTerm, $searchTerm, $searchTerm]);
            $totalRows = isset($countResult[0]->total) ? (int)$countResult[0]->total : 0;

            return [
                'results' => $results ?: [],
                'totalRows' => $totalRows
            ];
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return ['results' => [], 'totalRows' => 0, 'error' => $this->error];
        }
    }
}
