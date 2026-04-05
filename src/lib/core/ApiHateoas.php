<?php

/**
 * API HATEOAS Links Generator
 *
 * Generates Hypermedia as the Engine of Application State (HATEOAS)
 * links for RESTful API responses following RFC 5988 (Web Linking).
 *
 * @category  Core Class
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0
 * @since     1.0.0
 */
class ApiHateoas
{
    /**
     * Base URL for the API
     */
    private $baseUrl;

    /**
     * Base URL for the application
     */
    private $appUrl;

    /**
     * Constructor
     *
     * @param string|null $baseUrl API base URL
     */
    public function __construct($baseUrl = null)
    {
        $config = [];
        if (file_exists(__DIR__ . '/../../config.php')) {
            $config = require __DIR__ . '/../../config.php';
        }

        $this->appUrl = rtrim($config['app']['url'] ?? 'http://localhost', '/');
        $this->baseUrl = rtrim($baseUrl ?? ($this->appUrl . '/api/v1'), '/');
    }

    /**
     * Generate HATEOAS links for a paginated collection
     *
     * @param string $resource Resource name (e.g., 'posts', 'categories')
     * @param int $currentPage Current page number
     * @param int $perPage Items per page
     * @param int $totalItems Total number of items
     * @param array $extraParams Additional query parameters
     * @return array
     */
    public function paginationLinks($resource, $currentPage, $perPage, $totalItems, $extraParams = [])
    {
        $totalPages = ceil($totalItems / $perPage);
        $links = [];

        // Self link
        $links['self'] = [
            'href' => $this->buildUrl($resource, $currentPage, $perPage, $extraParams),
            'rel' => 'self',
            'type' => 'GET'
        ];

        // First page
        if ($currentPage > 1) {
            $links['first'] = [
                'href' => $this->buildUrl($resource, 1, $perPage, $extraParams),
                'rel' => 'first',
                'type' => 'GET'
            ];
        }

        // Previous page
        if ($currentPage > 1) {
            $links['prev'] = [
                'href' => $this->buildUrl($resource, $currentPage - 1, $perPage, $extraParams),
                'rel' => 'prev',
                'type' => 'GET'
            ];
        }

        // Next page
        if ($currentPage < $totalPages) {
            $links['next'] = [
                'href' => $this->buildUrl($resource, $currentPage + 1, $perPage, $extraParams),
                'rel' => 'next',
                'type' => 'GET'
            ];
        }

        // Last page
        if ($currentPage < $totalPages) {
            $links['last'] = [
                'href' => $this->buildUrl($resource, $totalPages, $perPage, $extraParams),
                'rel' => 'last',
                'type' => 'GET'
            ];
        }

        return $links;
    }

    /**
     * Generate HATEOAS links for a single post resource
     *
     * @param int $postId Post ID
     * @param string $slug Post slug
     * @return array
     */
    public function postLinks($postId, $slug = '')
    {
        $links = [];

        $links['self'] = [
            'href' => $this->baseUrl . '/posts/' . $postId,
            'rel' => 'self',
            'type' => 'GET'
        ];

        $links['comments'] = [
            'href' => $this->baseUrl . '/posts/' . $postId . '/comments',
            'rel' => 'comments',
            'type' => 'GET'
        ];

        if (!empty($slug)) {
            $links['canonical'] = [
                'href' => $this->appUrl . '/post/' . $postId . '/' . $slug,
                'rel' => 'canonical',
                'type' => 'text/html'
            ];
        }

        $links['collection'] = [
            'href' => $this->baseUrl . '/posts',
            'rel' => 'collection',
            'type' => 'GET'
        ];

        return $links;
    }

    /**
     * Generate HATEOAS links for a single category resource
     *
     * @param int $categoryId Category ID
     * @param string $slug Category slug
     * @return array
     */
    public function categoryLinks($categoryId, $slug = '')
    {
        $links = [];

        $links['self'] = [
            'href' => $this->baseUrl . '/categories/' . $categoryId,
            'rel' => 'self',
            'type' => 'GET'
        ];

        $links['posts'] = [
            'href' => $this->baseUrl . '/categories/' . $categoryId . '/posts',
            'rel' => 'posts',
            'type' => 'GET'
        ];

        if (!empty($slug)) {
            $links['canonical'] = [
                'href' => $this->appUrl . '/category/' . $slug,
                'rel' => 'canonical',
                'type' => 'text/html'
            ];
        }

        $links['collection'] = [
            'href' => $this->baseUrl . '/categories',
            'rel' => 'collection',
            'type' => 'GET'
        ];

        return $links;
    }

    /**
     * Generate HATEOAS links for a single comment resource
     *
     * @param int $commentId Comment ID
     * @param int $postId Post ID
     * @return array
     */
    public function commentLinks($commentId, $postId = 0)
    {
        $links = [];

        $links['self'] = [
            'href' => $this->baseUrl . '/comments/' . $commentId,
            'rel' => 'self',
            'type' => 'GET'
        ];

        if ($postId > 0) {
            $links['post'] = [
                'href' => $this->baseUrl . '/posts/' . $postId,
                'rel' => 'post',
                'type' => 'GET'
            ];
        }

        $links['collection'] = [
            'href' => $this->baseUrl . '/comments',
            'rel' => 'collection',
            'type' => 'GET'
        ];

        return $links;
    }

    /**
     * Generate HATEOAS links for archive resources
     *
     * @param int $year Year
     * @param int|null $month Month (optional)
     * @return array
     */
    public function archiveLinks($year, $month = null)
    {
        $links = [];

        if ($month !== null) {
            $links['self'] = [
                'href' => $this->baseUrl . '/archives/' . $year . '/' . $month,
                'rel' => 'self',
                'type' => 'GET'
            ];

            $links['year'] = [
                'href' => $this->baseUrl . '/archives/' . $year,
                'rel' => 'year',
                'type' => 'GET'
            ];
        } else {
            $links['self'] = [
                'href' => $this->baseUrl . '/archives/' . $year,
                'rel' => 'self',
                'type' => 'GET'
            ];
        }

        $links['collection'] = [
            'href' => $this->baseUrl . '/archives',
            'rel' => 'collection',
            'type' => 'GET'
        ];

        return $links;
    }

    /**
     * Generate HATEOAS root links (API info)
     *
     * @return array
     */
    public function rootLinks()
    {
        return [
            'self' => [
                'href' => $this->baseUrl,
                'rel' => 'self',
                'type' => 'GET'
            ],
            'posts' => [
                'href' => $this->baseUrl . '/posts',
                'rel' => 'posts',
                'type' => 'GET'
            ],
            'categories' => [
                'href' => $this->baseUrl . '/categories',
                'rel' => 'categories',
                'type' => 'GET'
            ],
            'comments' => [
                'href' => $this->baseUrl . '/comments',
                'rel' => 'comments',
                'type' => 'GET'
            ],
            'archives' => [
                'href' => $this->baseUrl . '/archives',
                'rel' => 'archives',
                'type' => 'GET'
            ],
            'search' => [
                'href' => $this->baseUrl . '/search?q={query}',
                'rel' => 'search',
                'type' => 'GET',
                'templated' => true
            ],
            'openapi' => [
                'href' => $this->baseUrl . '/openapi.json',
                'rel' => 'service-desc',
                'type' => 'application/json'
            ]
        ];
    }

    /**
     * Build a URL with pagination query parameters
     *
     * @param string $resource
     * @param int $page
     * @param int $perPage
     * @param array $extraParams
     * @return string
     */
    private function buildUrl($resource, $page, $perPage, $extraParams = [])
    {
        $params = array_merge($extraParams, [
            'page' => $page,
            'per_page' => $perPage
        ]);

        $query = http_build_query($params);
        return $this->baseUrl . '/' . $resource . '?' . $query;
    }
}
