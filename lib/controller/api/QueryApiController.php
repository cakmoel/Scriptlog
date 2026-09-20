<?php

namespace Scriptlog\Controller\Api;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Query API Controller
 *
 * Handles QUERY method requests per RFC 10008 (HTTP QUERY Method).
 * QUERY is a safe, idempotent method for server-side queries where
 * the query input is passed as request content (body) rather than URI
 * query parameters.
 *
 * The QUERY method bridges the gap between GET (limited URI length,
 * logged URIs) and POST (not safe/idempotent). QUERY requests are
 * cacheable and can be automatically retried.
 *
 * @category  Controller Class
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0
 * @since     1.1.2
 *
 * @see       RFC 10008 https://datatracker.ietf.org/doc/rfc10008/
 */

use Scriptlog\Controller\ApiController;
use Scriptlog\Core\ApiHateoas;
use Scriptlog\Core\ApiResponse;
use Scriptlog\Core\FormValidator;
use Scriptlog\Core\Sanitize;
use Scriptlog\Dao\PostDao;
use Scriptlog\Service\PostService;

/**
 * @psalm-suppress UnusedClass
 */
class QueryApiController extends ApiController
{
    /**
     * @var PostService
     */
    private $postService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->requiresAuth = false;
        parent::__construct();
        $this->postService = new PostService(new PostDao(), new FormValidator(), new Sanitize());
    }

    /**
     * Generic query endpoint.
     *
     * QUERY /api/v1/query
     *
     * Accepts a JSON body with query parameters and returns results.
     * This is a safe, idempotent endpoint suitable for complex queries
     * that would be impractical to encode as URI query parameters.
     *
     * @param array $_params
     * @return void
     */
    public function index($_params = [])
    {
        $body = $this->getJsonBody();

        if (empty($body)) {
            ApiResponse::badRequest('Request body is required for QUERY method. Send JSON with query parameters.');
            return;
        }

        $type = isset($body['type']) ? $body['type'] : 'all';
        $keyword = isset($body['q']) ? trim($body['q']) : (isset($body['keyword']) ? trim($body['keyword']) : '');

        if (empty($keyword) && $type === 'all') {
            ApiResponse::badRequest('Query must include a "q" or "keyword" field');
            return;
        }

        $results = $this->executeQuery($keyword, $type);

        $hateoas = new ApiHateoas();
        $links = $hateoas->rootLinks();

        ApiResponse::withHeader('Accept-Query: application/json');
        ApiResponse::success([
            'query' => [
                'keyword' => $keyword,
                'type' => $type
            ],
            'total' => count($results),
            'results' => $results
        ], 200, 'Query executed successfully', $links);
    }

    /**
     * Query posts only.
     *
     * QUERY /api/v1/query/posts
     *
     * @param array $_params
     * @return void
     */
    public function posts($_params = [])
    {
        $body = $this->getJsonBody();
        $keyword = isset($body['q']) ? trim($body['q']) : (isset($body['keyword']) ? trim($body['keyword']) : '');

        if (empty($keyword)) {
            ApiResponse::badRequest('Query must include a "q" or "keyword" field');
            return;
        }

        $results = $this->executeQuery($keyword, 'posts');

        ApiResponse::withHeader('Accept-Query: application/json');
        ApiResponse::success([
            'query' => [
                'keyword' => $keyword,
                'type' => 'posts'
            ],
            'total' => count($results),
            'results' => $results
        ], 200, 'Post query executed successfully');
    }

    /**
     * Query pages only.
     *
     * QUERY /api/v1/query/pages
     *
     * @param array $_params
     * @return void
     */
    public function pages($_params = [])
    {
        $body = $this->getJsonBody();
        $keyword = isset($body['q']) ? trim($body['q']) : (isset($body['keyword']) ? trim($body['keyword']) : '');

        if (empty($keyword)) {
            ApiResponse::badRequest('Query must include a "q" or "keyword" field');
            return;
        }

        $results = $this->executeQuery($keyword, 'pages');

        ApiResponse::withHeader('Accept-Query: application/json');
        ApiResponse::success([
            'query' => [
                'keyword' => $keyword,
                'type' => 'pages'
            ],
            'total' => count($results),
            'results' => $results
        ], 200, 'Page query executed successfully');
    }

    /**
     * Execute a query based on keyword and type.
     *
     * @param string $keyword
     * @param string $type
     * @return array
     */
    private function executeQuery($keyword, $type)
    {
        if (empty($keyword)) {
            return [];
        }

        try {
            $rows = $this->postService->searchPostsApi($keyword, $type);

            if (empty($rows)) {
                return [];
            }

            return array_map(function ($row) {
                $excerpt = strip_tags($row['post_content']);
                if (mb_strlen($excerpt, 'UTF-8') > 200) {
                    $excerpt = mb_substr($excerpt, 0, 200, 'UTF-8') . '...';
                }

                return [
                    'id' => (int)$row['ID'],
                    'title' => html_entity_decode($row['post_title'], ENT_QUOTES, 'UTF-8'),
                    'slug' => $row['post_slug'],
                    'excerpt' => $excerpt,
                    'type' => $row['post_type'],
                    'date' => $row['post_date'],
                    'status' => $row['post_status']
                ];
            }, $rows);
        } catch (\Throwable $e) {
            return [];
        }
    }
}
