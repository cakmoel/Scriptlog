<?php

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
class ArchivesApiController extends ApiController
{
    /**
     * @var ApiHateoas
     */
    private $hateoas;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->hateoas = new ApiHateoas();
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
    public function index($params = [])
    {
        // This is a public endpoint - no auth required
        $this->requiresAuth = false;

        try {
            $dbc = Registry::get('dbc');

            // Get distinct year-month combinations
            $sql = "SELECT 
                        YEAR(post_date) as year,
                        MONTH(post_date) as month,
                        COUNT(*) as post_count
                    FROM tbl_posts
                    WHERE post_status = 'publish' 
                    AND post_type = 'blog'
                    AND post_visibility = 'public'
                    GROUP BY YEAR(post_date), MONTH(post_date)
                    ORDER BY year DESC, month DESC";

            $stmt = $dbc->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                'total_years' => count($archives),
                '_links' => $hateoasLinks
            ]);
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

        $year = isset($params[0]) ? (int)$params[0] : 0;

        if (!$year || $year < 1900 || $year > date('Y')) {
            ApiResponse::badRequest('Invalid year');
            return;
        }

        // Get pagination
        $pagination = $this->getPagination($params);

        // Get sorting
        $sorting = $this->getSorting($params, ['ID', 'post_date', 'post_modified', 'post_title']);

        try {
            $dbc = Registry::get('dbc');

            // Get posts from this year
            $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
                           p.post_title, p.post_slug, p.post_summary, p.post_status,
                           p.post_visibility, p.post_tags, p.post_type, p.comment_status,
                           u.user_login as author_login, u.user_fullname as author_name
                    FROM tbl_posts p
                    LEFT JOIN tbl_users u ON p.post_author = u.ID
                    WHERE YEAR(p.post_date) = ?
                    AND p.post_status = 'publish'
                    AND p.post_type = 'blog'
                    AND p.post_visibility = 'public'
                    ORDER BY p." . $sorting['sort_by'] . " " . $sorting['sort_order'] . "
                    LIMIT " . $pagination['per_page'] . " OFFSET " . $pagination['offset'];

            $stmt = $dbc->prepare($sql);
            $stmt->execute([$year]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $countSql = "SELECT COUNT(*) as total
                         FROM tbl_posts
                         WHERE YEAR(post_date) = ?
                         AND post_status = 'publish'
                         AND post_type = 'blog'
                         AND post_visibility = 'public'";
            $countStmt = $dbc->prepare($countSql);
            $countStmt->execute([$year]);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            if ($total == 0) {
                ApiResponse::notFound('No posts found for year ' . $year);
                return;
            }

            // Transform posts
            $transformedPosts = array_map([$this, 'transformPost'], $posts);

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

        $year = isset($params[0]) ? (int)$params[0] : 0;
        $month = isset($params[1]) ? (int)$params[1] : 0;

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
            $dbc = Registry::get('dbc');

            // Get posts from this month
            $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
                           p.post_title, p.post_slug, p.post_summary, p.post_status,
                           p.post_visibility, p.post_tags, p.post_type, p.comment_status,
                           u.user_login as author_login, u.user_fullname as author_name
                    FROM tbl_posts p
                    LEFT JOIN tbl_users u ON p.post_author = u.ID
                    WHERE YEAR(p.post_date) = ?
                    AND MONTH(p.post_date) = ?
                    AND p.post_status = 'publish'
                    AND p.post_type = 'blog'
                    AND p.post_visibility = 'public'
                    ORDER BY p." . $sorting['sort_by'] . " " . $sorting['sort_order'] . "
                    LIMIT " . $pagination['per_page'] . " OFFSET " . $pagination['offset'];

            $stmt = $dbc->prepare($sql);
            $stmt->execute([$year, $month]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $countSql = "SELECT COUNT(*) as total
                         FROM tbl_posts
                         WHERE YEAR(post_date) = ?
                         AND MONTH(post_date) = ?
                         AND post_status = 'publish'
                         AND post_type = 'blog'
                         AND post_visibility = 'public'";
            $countStmt = $dbc->prepare($countSql);
            $countStmt->execute([$year, $month]);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            if ($total == 0) {
                ApiResponse::notFound('No posts found for ' . $this->getMonthName($month) . ' ' . $year);
                return;
            }

            // Transform posts
            $transformedPosts = array_map([$this, 'transformPost'], $posts);

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
     * Transform post data for API response
     *
     * @param array $post
     * @return array
     */
    private function transformPost($post)
    {
        return [
            'id' => (int)$post['ID'],
            'title' => $post['post_title'],
            'slug' => $post['post_slug'],
            'summary' => $post['post_summary'],
            'status' => $post['post_status'],
            'visibility' => $post['post_visibility'],
            'tags' => $post['post_tags'] ? explode(',', $post['post_tags']) : [],
            'comment_status' => $post['comment_status'],
            'author' => [
                'id' => (int)$post['post_author'],
                'login' => $post['author_login'] ?? '',
                'name' => $post['author_name'] ?? ''
            ],
            'date' => $post['post_date'],
            'modified' => $post['post_modified'],
            'url' => $this->getPostUrl($post['ID'], $post['post_slug'])
        ];
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

    /**
     * Get post URL
     *
     * @param int $id
     * @param string $slug
     * @return string
     */
    private function getPostUrl($id, $slug)
    {
        $appUrl = $this->getAppUrl();
        return $appUrl . '/post/' . $id . '/' . $slug;
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
