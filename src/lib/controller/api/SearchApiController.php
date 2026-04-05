<?php

/**
 * Search API Controller
 *
 * Handles API requests for search functionality
 *
 * @category  Controller Class
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class SearchApiController extends ApiController
{
    /**
     * @var SearchFinder
     */
    private $searchFinder;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->requiresAuth = false;

        parent::__construct();

        $this->searchFinder = new SearchFinder();
    }

    /**
     * Search posts and pages (public endpoint)
     *
     * GET /api/v1/search?q=keyword
     * GET /api/v1/search/posts?q=keyword
     * GET /api/v1/search/pages?q=keyword
     *
     * @param array $params Query parameters (q, type)
     * @return void
     */
    public function index($params = [])
    {
        $this->requiresAuth = false;

        $keyword = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['keyword']) ? trim($_GET['keyword']) : '');
        $type = isset($_GET['type']) ? $_GET['type'] : 'all';

        if (empty($keyword)) {
            ApiResponse::badRequest('Search keyword is required');
            return;
        }

        if (mb_strlen($keyword, 'UTF-8') < 2) {
            ApiResponse::badRequest('Search keyword must be at least 2 characters');
            return;
        }

        try {
            switch ($type) {
                case 'posts':
                    $results = $this->searchFinder->searchPost($keyword);
                    break;
                case 'pages':
                    $results = $this->searchFinder->searchPage($keyword);
                    break;
                case 'all':
                default:
                    $results = $this->searchFinder->searchAll($keyword);
                    break;
            }

            if (isset($results['error'])) {
                ApiResponse::error('Search failed: ' . $results['error'], 500, 'SEARCH_ERROR');
                return;
            }

            $transformedResults = $this->transformResults($results['results'], $type);

            ApiResponse::success([
                'keyword' => $keyword,
                'type' => $type,
                'total' => $results['totalRows'],
                'results' => $transformedResults
            ]);
        } catch (\Throwable $e) {
            ApiResponse::error('Search failed: ' . $e->getMessage(), 500, 'SEARCH_ERROR');
        }
    }

    /**
     * Search posts only
     *
     * GET /api/v1/search/posts?q=keyword
     *
     * @param array $params
     * @return void
     */
    public function posts($params = [])
    {
        $_GET['type'] = 'posts';
        $this->index($params);
    }

    /**
     * Search pages only
     *
     * GET /api/v1/search/pages?q=keyword
     *
     * @param array $params
     * @return void
     */
    public function pages($params = [])
    {
        $_GET['type'] = 'pages';
        $this->index($params);
    }

    /**
     * Transform search results for API response
     *
     * @param array $results
     * @param string $type
     * @return array
     */
    private function transformResults($results, $type)
    {
        if (empty($results)) {
            return [];
        }

        return array_map(function ($item) use ($type) {
            $item = (array) $item;
            return [
                'id' => (int)$item['ID'],
                'title' => html_entity_decode($item['post_title']),
                'slug' => $item['post_slug'],
                'excerpt' => $this->generateExcerpt($item['post_content']),
                'type' => $item['post_type'],
                'date' => $item['post_date'],
                'url' => $this->getContentUrl($item['ID'], $item['post_slug'], $item['post_type'])
            ];
        }, $results);
    }

    /**
     * Generate excerpt from content
     *
     * @param string $content
     * @param int $length
     * @return string
     */
    private function generateExcerpt($content, $length = 150)
    {
        if (empty($content)) {
            return '';
        }

        $content = strip_tags($content);
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');

        if (mb_strlen($content, 'UTF-8') <= $length) {
            return $content;
        }

        return mb_substr($content, 0, $length, 'UTF-8') . '...';
    }

    /**
     * Get content URL based on type
     *
     * @param int $id
     * @param string $slug
     * @param string $type
     * @return string
     */
    private function getContentUrl($id, $slug, $type)
    {
        $appUrl = $this->getAppUrl();
        $permalinkEnabled = $this->isPermalinkEnabled();

        if ($type === 'page') {
            if ($permalinkEnabled === 'yes') {
                return $appUrl . '/page/' . rawurlencode($slug);
            }
            return $appUrl . '/?pg=' . (int)$id;
        }

        if ($permalinkEnabled === 'yes') {
            return $appUrl . '/post/' . (int)$id . '/' . rawurlencode($slug);
        }

        return $appUrl . '/?p=' . (int)$id;
    }

    /**
     * Check if permalinks are enabled
     *
     * @return string
     */
    private function isPermalinkEnabled()
    {
        try {
            $result = $this->dbc->dbSelect(
                "SELECT setting_value FROM tbl_settings WHERE setting_name = 'permalink_setting'",
                []
            );

            if (!empty($result) && isset($result[0]->setting_value)) {
                $rewriteStatus = json_decode($result[0]->setting_value, true);
                return (is_array($rewriteStatus) && isset($rewriteStatus['rewrite'])) ? $rewriteStatus['rewrite'] : 'no';
            }
        } catch (\Throwable $e) {
            // Fallback to 'no' if query fails
        }

        return 'no';
    }

    /**
     * Get application URL
     *
     * @return string
     */
    private function getAppUrl()
    {
        $config = [];
        if (file_exists(__DIR__ . '/../../../config.php')) {
            $config = require __DIR__ . '/../../../config.php';
        }
        return $config['app']['url'] ?? 'http://localhost';
    }
}
