<?php

namespace Scriptlog\Controller\Api;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Archives API Controller
 *
 * Handles API requests for post archives (by date)
 *
 * @category  Controller Class
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

use Scriptlog\Controller\ApiController;
use Scriptlog\Core\ApiHateoas;
use Scriptlog\Core\ApiResponse;
use Scriptlog\Core\FormValidator;
use Scriptlog\Core\Sanitize;
use Scriptlog\Dao\PostDao;
use Scriptlog\Dto\Api\PostApiDto;
use Scriptlog\Service\PostService;

class ArchivesApiController extends ApiController
{
    /**
     * @var ApiHateoas
     */
    private $hateoas;

    /**
     * @var PostService
     */
    private $postService;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->hateoas = new ApiHateoas();
        $this->postService = new PostService(new PostDao(), new FormValidator(), new Sanitize());
    }

    /**
     * Get list of available archive dates (public endpoint)
     *
     * GET /api/v1/archives
     *
     * Returns a list of years and months that have published posts
     *
     * @param array $params Query parameters
     * @return void
     */
    public function index($_params = [])
    {
        // This is a public endpoint - no auth required
        $this->requiresAuth = false;

        try {
            $results = $this->postService->getArchiveIndexApi();

            // Group by year
            $archives = [];
            foreach ($results as $row) {
                $year = (int)$row['year'];
                $month = (int)$row['month'];

                if (!isset($archives[$year])) {
                    $archives[$year] = [
                        'year' => $year,
                        'months' => [],
                        'total_posts' => 0
                    ];
                }

                $archives[$year]['months'][] = [
                    'month' => $month,
                    'month_name' => $this->getMonthName($month),
                    'post_count' => (int)$row['post_count']
                ];

                $archives[$year]['total_posts'] += (int)$row['post_count'];
            }

            // Re-index array
            $archives = array_values($archives);

            // Generate HATEOAS links
            $hateoasLinks = $this->hateoas->rootLinks();

            ApiResponse::success([
                'archives' => $archives,
                'total_years' => count($archives)
            ], 200, null, $hateoasLinks);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to fetch archives: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * Get posts from a specific year (public endpoint)
     *
     * GET /api/v1/archives/{year}
     *
     * @param array $params Route parameters including 'year'
     * @return void
     */
    public function year($params = [])
    {
        // This is a public endpoint - no auth required
        $this->requiresAuth = false;

        $year = isset($params['year']) ? (int)$params['year'] : 0;

        if (!$year || $year < 1900 || $year > date('Y')) {
            ApiResponse::badRequest('Invalid year');
            return;
        }

        // Get pagination
        $pagination = $this->getPagination($params);

        // Get sorting
        $sorting = $this->getSorting($params, ['ID', 'post_date', 'post_modified', 'post_title']);

        try {
            $sortBy = str_replace('`', '', $sorting['sort_by']);
            $sortOrder = $sorting['sort_order'];

            $posts = $this->postService->getPostsByYearApi(
                $year,
                $pagination['page'],
                $pagination['per_page'],
                $sortBy,
                $sortOrder
            );

            $total = $this->postService->countPostsByYearApi($year);

            if ($total == 0) {
                ApiResponse::notFound('No posts found for year ' . $year);
                return;
            }

            // Transform posts
            $transformedPosts = PostApiDto::transformCollection($posts, $this->getAppUrl());

            // Generate HATEOAS links
            $hateoasLinks = $this->hateoas->archiveLinks($year);
            $hateoasLinks = array_merge($hateoasLinks, $this->hateoas->paginationLinks('archives/' . $year, $pagination['page'], $pagination['per_page'], $total));

            // Build response
            $response = [
                'year' => $year,
                'posts' => $transformedPosts
            ];

            ApiResponse::paginated($response, $pagination['page'], $pagination['per_page'], $total, $hateoasLinks);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to fetch year archives: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * Get posts from a specific month (public endpoint)
     *
     * GET /api/v1/archives/{year}/{month}
     *
     * @param array $params Route parameters including 'year' and 'month'
     * @return void
     */
    public function month($params = [])
    {
        // This is a public endpoint - no auth required
        $this->requiresAuth = false;

        $year = isset($params['year']) ? (int)$params['year'] : 0;
        $month = isset($params['month']) ? (int)$params['month'] : 0;

        if (!$year || $year < 1900 || $year > date('Y')) {
            ApiResponse::badRequest('Invalid year');
            return;
        }

        if (!$month || $month < 1 || $month > 12) {
            ApiResponse::badRequest('Invalid month');
            return;
        }

        // Get pagination
        $pagination = $this->getPagination($params);

        // Get sorting
        $sorting = $this->getSorting($params, ['ID', 'post_date', 'post_modified', 'post_title']);

        try {
            $sortBy = str_replace('`', '', $sorting['sort_by']);
            $sortOrder = $sorting['sort_order'];

            $posts = $this->postService->getPostsByYearMonthApi(
                $year,
                $month,
                $pagination['page'],
                $pagination['per_page'],
                $sortBy,
                $sortOrder
            );

            $total = $this->postService->countPostsByYearMonthApi($year, $month);

            if ($total == 0) {
                ApiResponse::notFound('No posts found for ' . $this->getMonthName($month) . ' ' . $year);
                return;
            }

            // Transform posts
            $transformedPosts = PostApiDto::transformCollection($posts, $this->getAppUrl());

            // Generate HATEOAS links
            $hateoasLinks = $this->hateoas->archiveLinks($year, $month);
            $hateoasLinks = array_merge($hateoasLinks, $this->hateoas->paginationLinks('archives/' . $year . '/' . $month, $pagination['page'], $pagination['per_page'], $total));

            // Build response
            $response = [
                'year' => $year,
                'month' => $month,
                'month_name' => $this->getMonthName($month),
                'posts' => $transformedPosts
            ];

            ApiResponse::paginated($response, $pagination['page'], $pagination['per_page'], $total, $hateoasLinks);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to fetch month archives: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * Get month name
     *
     * @param int $month
     * @return string
     */
    private function getMonthName($month)
    {
        $months = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];

        return $months[$month] ?? '';
    }
}
