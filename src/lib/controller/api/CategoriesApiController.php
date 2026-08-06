<?php

namespace Scriptlog\Controller\Api;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Categories API Controller
 *
 * Handles API requests for categories (topics)
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
use Scriptlog\Dao\TopicDao;
use Scriptlog\Dto\Api\PostApiDto;
use Scriptlog\Dto\Api\TopicApiDto;
use Scriptlog\Service\TopicService;

class CategoriesApiController extends ApiController
{
    /**
     * @var TopicDao
     */
    private $topicDao;

    /**
     * @var TopicService
     */
    private $topicService;

    /**
     * @var Sanitize
     */
    private $sanitizer;

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

        // Initialize DAO and services
        $this->topicDao = new TopicDao();
        $this->sanitizer = new Sanitize();
        $this->hateoas = new ApiHateoas();
        $this->topicService = new TopicService($this->topicDao, new FormValidator(), $this->sanitizer);
    }

    /**
     * Get all categories (public endpoint)
     *
     * GET /api/v1/categories
     *
     * @param array $params Query parameters
     * @return void
     */
    public function index($params = [])
    {
        // This is a public endpoint - no auth required
        // Get pagination
        $pagination = $this->getPagination($params);

        // Get sorting
        $sorting = $this->getSorting($params, ['ID', 'topic_title', 'topic_slug']);

        try {
            $sortBy = str_replace('`', '', $sorting['sort_by']);
            $sortOrder = $sorting['sort_order'];

            $topics = $this->topicService->getActiveTopicsApi(
                $pagination['page'],
                $pagination['per_page'],
                $sortBy,
                $sortOrder
            );

            $total = $this->topicService->countActiveTopicsApi();

            // Transform topics
            $transformedTopics = TopicApiDto::transformCollection($topics, $this->getAppUrl());

            // Generate HATEOAS pagination links
            $hateoasLinks = $this->hateoas->paginationLinks('categories', $pagination['page'], $pagination['per_page'], $total);

            ApiResponse::paginated($transformedTopics, $pagination['page'], $pagination['per_page'], $total, $hateoasLinks);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to fetch categories: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * Get a single category by ID (public endpoint)
     *
     * GET /api/v1/categories/{id}
     *
     * @param array $params Route parameters including 'id'
     * @return void
     */
    public function show($params = [])
    {
        // This is a public endpoint - no auth required
        $topicId = isset($params['id']) ? (int)$params['id'] : 0;

        if (!$topicId) {
            ApiResponse::badRequest('Category ID is required');
            return;
        }

        try {
            $topic = $this->topicService->getTopicApi($topicId);

            if (!$topic) {
                ApiResponse::notFound('Category not found');
                return;
            }

            ApiResponse::success(TopicApiDto::transform($topic, $this->getAppUrl()), 200, null, $this->hateoas->categoryLinks($topicId, $topic['topic_slug']));
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to fetch category: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * Get posts in a category (public endpoint)
     *
     * GET /api/v1/categories/{id}/posts
     *
     * @param array $params Route parameters including 'id'
     * @return void
     */
    public function posts($params = [])
    {
        // This is a public endpoint - no auth required
        $topicId = isset($params['id']) ? (int)$params['id'] : 0;

        if (!$topicId) {
            ApiResponse::badRequest('Category ID is required');
            return;
        }

        // Get pagination
        $pagination = $this->getPagination($params);

        // Get sorting
        $sorting = $this->getSorting($params, ['ID', 'post_date', 'post_modified', 'post_title']);

        try {
            $category = $this->topicService->getTopicApi($topicId);

            if (!$category) {
                ApiResponse::notFound('Category not found');
                return;
            }

            $sortBy = str_replace('`', '', $sorting['sort_by']);
            $sortOrder = $sorting['sort_order'];

            $posts = $this->topicService->getPostsByTopicApi(
                $topicId,
                $pagination['page'],
                $pagination['per_page'],
                $sortBy,
                $sortOrder
            );

            $total = $this->topicService->countPostsByTopicApi($topicId);

            // Transform posts
            $transformedPosts = PostApiDto::transformCollection($posts, $this->getAppUrl());

            // Include category info
            $response = [
                'category' => [
                    'id' => (int)$topicId,
                    'title' => $category['topic_title'],
                    'slug' => $category['topic_slug'],
                    '_links' => $this->hateoas->categoryLinks($topicId, $category['topic_slug'])
                ],
                'posts' => $transformedPosts
            ];

            // Generate HATEOAS pagination links
            $hateoasLinks = $this->hateoas->paginationLinks('categories/' . $topicId . '/posts', $pagination['page'], $pagination['per_page'], $total);

            ApiResponse::paginated($response, $pagination['page'], $pagination['per_page'], $total, $hateoasLinks);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to fetch category posts: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * Create a new category (requires authentication)
     *
     * POST /api/v1/categories
     *
     * @param array $params Request data
     * @return void
     */
    public function store($_params = [])
    {
        // Require authentication
        $this->requiresAuth = true;

        // Check permission
        if (!$this->hasPermission(['administrator', 'editor'])) {
            ApiResponse::forbidden('You do not have permission to create categories');
            return;
        }

        // Validate required fields
        $required = ['topic_title'];
        $validationErrors = $this->validateRequired($this->requestData, $required);

        if ($validationErrors) {
            ApiResponse::unprocessableEntity('Validation failed', $validationErrors);
            return;
        }

        try {
            $slug = $this->generateSlug($this->requestData['topic_title']);

            if ($this->topicService->checkTopicSlugExists($slug)) {
                ApiResponse::conflict('A category with this title already exists');
                return;
            }

            $topicId = $this->topicService->createTopicApi([
                'topic_title' => $this->sanitize($this->requestData['topic_title']),
                'topic_slug' => $slug,
                'topic_status' => isset($this->requestData['topic_status']) ? $this->requestData['topic_status'] : 'Y'
            ]);

            $createdTopic = $this->topicService->getTopicApi($topicId);

            ApiResponse::created(TopicApiDto::transform($createdTopic, $this->getAppUrl()), 'Category created successfully', $this->hateoas->categoryLinks($topicId, $slug), $this->getAppUrl() . '/api/v1/categories/' . $topicId);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to create category: ' . $e->getMessage(), 500, 'CREATE_ERROR');
        }
    }

    /**
     * Update an existing category (requires authentication)
     *
     * PUT /api/v1/categories/{id}
     *
     * @param array $params Route parameters including 'id'
     * @return void
     */
    public function update($params = [])
    {
        // Require authentication
        $this->requiresAuth = true;

        $topicId = isset($params['id']) ? (int)$params['id'] : 0;

        if (!$topicId) {
            ApiResponse::badRequest('Category ID is required');
            return;
        }

        // Check permission
        if (!$this->hasPermission(['administrator', 'editor'])) {
            ApiResponse::forbidden('You do not have permission to update categories');
            return;
        }

        try {
            $topic = $this->topicService->getTopicApi($topicId);

            if (!$topic) {
                ApiResponse::notFound('Category not found');
                return;
            }

            $updateData = [];

            if (isset($this->requestData['topic_title'])) {
                $updateData['topic_title'] = $this->sanitize($this->requestData['topic_title']);
                $updateData['topic_slug'] = $this->generateSlug($this->requestData['topic_title']);
            }

            if (isset($this->requestData['topic_status'])) {
                $updateData['topic_status'] = in_array($this->requestData['topic_status'], ['Y', 'N']) ? $this->requestData['topic_status'] : 'Y';
            }

            if (empty($updateData)) {
                ApiResponse::badRequest('No fields to update');
                return;
            }

            $this->topicService->updateTopicApi($topicId, $updateData);

            $updatedTopic = $this->topicService->getTopicApi($topicId);

            ApiResponse::success(TopicApiDto::transform($updatedTopic, $this->getAppUrl()), 200, 'Category updated successfully');
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to update category: ' . $e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }

    /**
     * Delete a category (requires authentication)
     *
     * DELETE /api/v1/categories/{id}
     *
     * @param array $params Route parameters including 'id'
     * @return void
     */
    public function destroy($params = [])
    {
        // Require authentication
        $this->requiresAuth = true;

        $topicId = isset($params['id']) ? (int)$params['id'] : 0;

        if (!$topicId) {
            ApiResponse::badRequest('Category ID is required');
            return;
        }

        // Check permission
        if (!$this->hasPermission(['administrator'])) {
            ApiResponse::forbidden('Only administrators can delete categories');
            return;
        }

        try {
            $topic = $this->topicService->getTopicApi($topicId);

            if (!$topic) {
                ApiResponse::notFound('Category not found');
                return;
            }

            $this->topicService->removeTopicApi($topicId);

            ApiResponse::noContent();
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to delete category: ' . $e->getMessage(), 500, 'DELETE_ERROR');
        }
    }



    /**
     * Generate URL-friendly slug
     *
     * @param string $title
     * @return string
     */
    private function generateSlug($title)
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $title)));
        return preg_replace('/-+/', '-', $slug);
    }
}
