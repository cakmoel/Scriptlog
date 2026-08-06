<?php

namespace Scriptlog\Controller\Api;

defined('SCRIPTLOG') || die("Direct access not permitted");

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
 * @property Db $dbc
 */

use Scriptlog\Controller\ApiController;
use Scriptlog\Core\ApiHateoas;
use Scriptlog\Core\ApiResponse;
use Scriptlog\Core\SearchFinder;

class SearchApiController extends ApiController
{
    /**
     * @var SearchFinder
     */
    private $searchFinder;

    /**
     * @var ApiHateoas
     */
    private $hateoas;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->requiresAuth = false;

        parent::__construct();

        $this->searchFinder = new SearchFinder();
        $this->hateoas = new ApiHateoas();
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
    public function index($_params = [])
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

        $pagination = $this->getPagination($_GET);

        try {
            switch ($type) {
                case 'posts':
                    $results = $this->searchFinder->searchPost($keyword, $pagination['page'], $pagination['per_page']);
                    break;
                case 'pages':
                    $results = $this->searchFinder->searchPage($keyword, $pagination['page'], $pagination['per_page']);
                    break;
                case 'all':
                default:
                    $results = $this->searchFinder->searchAll($keyword, $pagination['page'], $pagination['per_page']);
                    break;
            }

            if (isset($results['error'])) {
                ApiResponse::error('Search failed: ' . $results['error'], 500, 'SEARCH_ERROR');
                return;
            }

            $transformedResults = $this->transformResults($results['results'], $type);

            $totalPages = $results['totalRows'] > 0
                ? (int)ceil($results['totalRows'] / $results['perPage'])
                : 0;

            $hateoasLinks = $this->hateoas->paginationLinks(
                'search',
                $results['page'],
                $results['perPage'],
                $results['totalRows'],
                ['q' => $keyword]
            );

            $responseData = $this->buildResponseData(
                $results,
                $transformedResults,
                $type,
                $keyword,
                $totalPages,
                $hateoasLinks
            );

            ApiResponse::success($responseData);
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
     * Build the search API response payload.
     *
     * The top-level "total" field is what the sidebar widget reads to render
     * the result count without reaching into pagination.total_items.
     *
     * @param array $results Raw SearchFinder result (page, perPage, totalRows)
     * @param array $transformedResults Normalized results for the API consumer
     * @param string $type Requested search scope (all|posts|pages)
     * @param string $keyword Sanitized search keyword
     * @param int $totalPages Total number of result pages
     * @param array $hateoasLinks Optional HATEOAS pagination links
     * @return array
     */
    private function buildResponseData($results, $transformedResults, $type, $keyword, $totalPages, $hateoasLinks)
    {
        $responseData = [
            'keyword' => $keyword,
            'type' => $type,
            'total' => (int)$results['totalRows'],
            'results' => $transformedResults,
            'pagination' => [
                'current_page' => (int)$results['page'],
                'per_page' => (int)$results['perPage'],
                'total_items' => (int)$results['totalRows'],
                'total_pages' => $totalPages,
                'has_next_page' => $results['page'] < $totalPages,
                'has_previous_page' => $results['page'] > 1
            ]
        ];

        if (!empty($hateoasLinks)) {
            $responseData['_links'] = $hateoasLinks;
        }

        return $responseData;
    }

    /**
     * Transform search results for API response
     *
     * @param array $results
     * @param string $type
     * @return array
     */
    private function transformResults($results, $_type)
    {
        if (empty($results)) {
            return [];
        }

        return array_map(function ($item) {
            $item = (array) $item;
            return [
                'id' => (int)$item['ID'],
                'title' => html_entity_decode($item['post_title'], ENT_QUOTES, 'UTF-8'),
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
     * Post content is stored double-encoded in the database (e.g. "&lt;p&gt;"),
     * so HTML entities MUST be decoded before tags are stripped; doing it the
     * other way round leaks raw HTML into the excerpt (the "Found undefined
     * result(s)" / raw-<p> widget bug). Whitespace is then collapsed so the
     * truncated excerpt reads cleanly.
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

        $content = html_entity_decode($content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8');
        $content = strip_tags($content);
        $content = trim(preg_replace('/\s+/', ' ', $content));

        if (mb_strlen($content, 'UTF-8') <= $length) {
            return $content;
        }

        $truncated = mb_substr($content, 0, $length, 'UTF-8');
        $lastSpace = mb_strrpos($truncated, ' ');

        if ($lastSpace !== false) {
            $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
        }

        return $truncated . '...';
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
        $permalinkEnabled = function_exists('rewrite_status') ? rewrite_status() : 'no';

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
}
