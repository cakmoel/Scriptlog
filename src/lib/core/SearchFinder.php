<?php

namespace Scriptlog\Core;

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
     * Strips FULLTEXT boolean mode operators to prevent operator injection.
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

        $keyword = preg_replace('/[+\-><()~*:"@]+/', ' ', $keyword);
        $keyword = trim(preg_replace('/\s+/', ' ', $keyword));

        if (mb_strlen($keyword, 'UTF-8') < 2) {
            return '';
        }

        return $keyword;
    }

    /**
     * Build a FULLTEXT boolean mode query string from user keyword.
     * Prepends + to each word for AND semantics.
     *
     * @param string $keyword Already-sanitized keyword
     * @return string
     */
    private function buildBooleanQuery($keyword)
    {
        $terms = explode(' ', $keyword);
        $terms = array_filter($terms, function ($t) {
            return trim($t) !== '';
        });
        if (empty($terms)) {
            return '';
        }
        return '+' . implode(' +', $terms);
    }

    /**
     * Search posts
     *
     * @param string $keyword
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function searchPost($keyword, $page = 1, $perPage = 10)
    {
        $keyword = $this->sanitizeKeyword($keyword);
        $page = max(1, (int)$page);
        $perPage = max(1, min(100, (int)$perPage));
        $offset = ($page - 1) * $perPage;

        if (empty($keyword)) {
            return ['results' => [], 'totalRows' => 0, 'page' => $page, 'perPage' => $perPage, 'totalPages' => 0, 'keyword' => ''];
        }

        try {
            $booleanQuery = $this->buildBooleanQuery($keyword);

            $sql = "SELECT ID, post_author, post_date, post_modified,
                           post_title, post_slug, post_content,
                           post_status, post_type,
                           MATCH (post_title, post_content, post_tags) AGAINST (? IN BOOLEAN MODE) AS relevance
                    FROM tbl_posts
                    WHERE MATCH (post_title, post_content, post_tags) AGAINST (? IN BOOLEAN MODE)
                    AND post_status = 'publish'
                    AND post_type = 'blog'
                    ORDER BY relevance DESC, post_date DESC
                    LIMIT ? OFFSET ?";

            $results = $this->dbc->dbSelect($sql, [$booleanQuery, $booleanQuery, $perPage, $offset]);

            $countSql = "SELECT COUNT(*) as total
                         FROM tbl_posts
                         WHERE MATCH (post_title, post_content, post_tags) AGAINST (? IN BOOLEAN MODE)
                         AND post_status = 'publish'
                         AND post_type = 'blog'";

            $countResult = $this->dbc->dbSelect($countSql, [$booleanQuery]);
            $totalRows = isset($countResult[0]->total) ? (int)$countResult[0]->total : 0;
            $totalPages = $totalRows > 0 ? (int)ceil($totalRows / $perPage) : 0;

            return [
                'results' => $results ?: [],
                'totalRows' => $totalRows,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
                'keyword' => $keyword
            ];
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return ['results' => [], 'totalRows' => 0, 'page' => $page, 'perPage' => $perPage, 'totalPages' => 0, 'keyword' => $keyword, 'error' => $this->error];
        }
    }

    /**
     * Search pages
     *
     * @param string $keyword
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function searchPage($keyword, $page = 1, $perPage = 10)
    {
        $keyword = $this->sanitizeKeyword($keyword);
        $page = max(1, (int)$page);
        $perPage = max(1, min(100, (int)$perPage));
        $offset = ($page - 1) * $perPage;

        if (empty($keyword)) {
            return ['results' => [], 'totalRows' => 0, 'page' => $page, 'perPage' => $perPage, 'totalPages' => 0, 'keyword' => ''];
        }

        try {
            $booleanQuery = $this->buildBooleanQuery($keyword);

            $sql = "SELECT ID, post_author, post_date, post_modified,
                           post_title, post_slug, post_content,
                           post_status, post_type,
                           MATCH (post_title, post_content, post_tags) AGAINST (? IN BOOLEAN MODE) AS relevance
                    FROM tbl_posts
                    WHERE MATCH (post_title, post_content, post_tags) AGAINST (? IN BOOLEAN MODE)
                    AND post_status = 'publish'
                    AND post_type = 'page'
                    ORDER BY relevance DESC, post_date DESC
                    LIMIT ? OFFSET ?";

            $results = $this->dbc->dbSelect($sql, [$booleanQuery, $booleanQuery, $perPage, $offset]);

            $countSql = "SELECT COUNT(*) as total
                         FROM tbl_posts
                         WHERE MATCH (post_title, post_content, post_tags) AGAINST (? IN BOOLEAN MODE)
                         AND post_status = 'publish'
                         AND post_type = 'page'";

            $countResult = $this->dbc->dbSelect($countSql, [$booleanQuery]);
            $totalRows = isset($countResult[0]->total) ? (int)$countResult[0]->total : 0;
            $totalPages = $totalRows > 0 ? (int)ceil($totalRows / $perPage) : 0;

            return [
                'results' => $results ?: [],
                'totalRows' => $totalRows,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
                'keyword' => $keyword
            ];
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return ['results' => [], 'totalRows' => 0, 'page' => $page, 'perPage' => $perPage, 'totalPages' => 0, 'keyword' => $keyword, 'error' => $this->error];
        }
    }

    /**
     * Search both posts and pages
     *
     * @param string $keyword
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function searchAll($keyword, $page = 1, $perPage = 10)
    {
        $keyword = $this->sanitizeKeyword($keyword);
        $page = max(1, (int)$page);
        $perPage = max(1, min(100, (int)$perPage));
        $offset = ($page - 1) * $perPage;

        if (empty($keyword)) {
            return ['results' => [], 'totalRows' => 0, 'page' => $page, 'perPage' => $perPage, 'totalPages' => 0, 'keyword' => ''];
        }

        try {
            $booleanQuery = $this->buildBooleanQuery($keyword);

            $sql = "SELECT ID, post_author, post_date, post_modified,
                           post_title, post_slug, post_content,
                           post_status, post_type,
                           MATCH (post_title, post_content, post_tags) AGAINST (? IN BOOLEAN MODE) AS relevance
                    FROM tbl_posts
                    WHERE MATCH (post_title, post_content, post_tags) AGAINST (? IN BOOLEAN MODE)
                    AND post_status = 'publish'
                    ORDER BY relevance DESC, post_date DESC
                    LIMIT ? OFFSET ?";

            $results = $this->dbc->dbSelect($sql, [$booleanQuery, $booleanQuery, $perPage, $offset]);

            $countSql = "SELECT COUNT(*) as total
                         FROM tbl_posts
                         WHERE MATCH (post_title, post_content, post_tags) AGAINST (? IN BOOLEAN MODE)
                         AND post_status = 'publish'";

            $countResult = $this->dbc->dbSelect($countSql, [$booleanQuery]);
            $totalRows = isset($countResult[0]->total) ? (int)$countResult[0]->total : 0;
            $totalPages = $totalRows > 0 ? (int)ceil($totalRows / $perPage) : 0;

            return [
                'results' => $results ?: [],
                'totalRows' => $totalRows,
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => $totalPages,
                'keyword' => $keyword
            ];
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return ['results' => [], 'totalRows' => 0, 'page' => $page, 'perPage' => $perPage, 'totalPages' => 0, 'keyword' => $keyword, 'error' => $this->error];
        }
    }
}
