<?php

namespace Scriptlog\Controller\Api;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Posts API Controller
 *
 * Handles API requests for blog posts
 *
 * @category  Controller Class
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

use Scriptlog\Controller\ApiController;
use Scriptlog\Core\ApiAuth;
use Scriptlog\Core\ApiHateoas;
use Scriptlog\Core\ApiResponse;
use Scriptlog\Core\FormValidator;
use Scriptlog\Core\Sanitize;
use Scriptlog\Dao\CommentDao;
use Scriptlog\Dao\MediaDao;
use Scriptlog\Dao\PostDao;
use Scriptlog\Dao\TopicDao;
use Scriptlog\Dto\Api\CommentApiDto;
use Scriptlog\Dto\Api\PostApiDto;
use Scriptlog\Service\PostService;

class PostsApiController extends ApiController
{
    /**
     * @var PostDao
     */
    private $postDao;

    /**
     * @var \Scriptlog\Service\PostService
     */
    private $postService;

    /**
     * @var TopicDao
     */
    private $topicDao;

    /**
     * @var CommentDao
     */
    private $commentDao;

    /**
     * @var MediaDao
     */
    private $mediaDao;

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
        parent::__construct();

        // Initialize DAOs and services
        $this->postDao = new PostDao();
        $this->topicDao = new TopicDao();
        $this->commentDao = new CommentDao();
        $this->mediaDao = new MediaDao();
        $this->sanitizer = new Sanitize();
        $this->hateoas = new ApiHateoas();
        $this->postService = new PostService($this->postDao, new FormValidator(), $this->sanitizer);
    }

    /**
     * Get all published posts (public endpoint)
     *
     * GET /api/v1/posts
     *
     * @param array $params Query parameters (page, per_page, sort_by, sort_order)
     * @return void
     */
    public function index($params = [])
    {
        // This is a public endpoint - no auth required
        $this->requiresAuth = false;

        // Get pagination
        $pagination = $this->getPagination($params);

        // Get sorting
        $sorting = $this->getSorting($params, ['ID', 'post_date', 'post_modified', 'post_title']);

        try {
            $sortBy = str_replace('`', '', $sorting['sort_by']);
            $sortOrder = $sorting['sort_order'];

            $posts = $this->postService->getPublishedPostsApi(
                $pagination['page'],
                $pagination['per_page'],
                $sortBy,
                $sortOrder
            );

            $total = $this->postService->countPublishedPostsApi();

            // Transform posts for API response
            $transformedPosts = PostApiDto::transformCollection($posts, $this->getAppUrl());

            // Generate ETag from post count and page for cache validation
            $etag = md5($total . '_' . $pagination['page'] . '_' . $pagination['per_page']);
            ApiResponse::withEtag($etag);

            // Check conditional request
            if (ApiResponse::checkEtagMatch($etag)) {
                ApiResponse::notModified();
                return;
            }

            // Generate HATEOAS pagination links
            $hateoasLinks = $this->hateoas->paginationLinks('posts', $pagination['page'], $pagination['per_page'], $total);

            // Return paginated response with HATEOAS links
            ApiResponse::paginated($transformedPosts, $pagination['page'], $pagination['per_page'], $total, $hateoasLinks);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to fetch posts: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * Get a single post by ID (public endpoint)
     *
     * GET /api/v1/posts/{id}
     *
     * @param array $params Route parameters including 'id'
     * @return void
     */
    public function show($params = [])
    {
        // This is a public endpoint - no auth required
        $this->requiresAuth = false;

        $postId = isset($params['id']) ? (int)$params['id'] : 0;

        if (!$postId) {
            ApiResponse::badRequest('Post ID is required');
            return;
        }

        try {
            $post = $this->postService->getPublishedPostApi($postId);

            if (!$post) {
                ApiResponse::notFound('Post not found');
                return;
            }

            // Get categories/topics for this post
            $topics = $this->postService->getPostTopicsApi($postId);

            // Generate ETag from post_modified for cache validation
            $etag = md5($post['post_modified'] . $postId);
            ApiResponse::withEtag($etag);
            ApiResponse::withLastModified($post['post_modified']);

            // Check conditional request
            if (ApiResponse::checkEtagMatch($etag) || ApiResponse::checkModifiedSince($post['post_modified'])) {
                ApiResponse::notModified();
                return;
            }

            // Transform post for API response
            $transformedPost = PostApiDto::transform($post, $this->getAppUrl());
            $transformedPost['topics'] = $topics;

            // Get featured image if available
            if ($post['media_id']) {
                $media = $this->mediaDao->findMediaById($post['media_id'], $this->sanitizer);
                if ($media && is_array($media)) {
                    $transformedPost['featured_image'] = $this->getAppUrl() . '/public/files/pictures/' . $media['media_filename'];
                }
            }

            // Generate HATEOAS links
            $hateoasLinks = $this->hateoas->postLinks($postId, $post['post_slug']);

            ApiResponse::success($transformedPost, 200, null, $hateoasLinks);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to fetch post: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * Get comments for a post (public endpoint)
     *
     * GET /api/v1/posts/{id}/comments
     *
     * @param array $params Route parameters including 'id'
     * @return void
     */
    public function comments($params = [])
    {
        // This is a public endpoint - no auth required
        $this->requiresAuth = false;

        $postId = isset($params['id']) ? (int)$params['id'] : 0;

        if (!$postId) {
            ApiResponse::badRequest('Post ID is required');
            return;
        }

        // Get pagination
        $pagination = $this->getPagination($params);

        try {
            $comments = $this->commentDao->findApprovedCommentsPaginated(
                $pagination['per_page'],
                $pagination['offset'],
                'ID',
                'DESC',
                $postId
            );

            $total = $this->commentDao->countApprovedComments($postId);

            $transformedComments = [];

            foreach ($comments as $comment) {
                $c = (array) $comment;
                $transformedComments[] = CommentApiDto::transform($c, $this->getAppUrl());
            }

            $hateoasLinks = $this->hateoas->paginationLinks('posts/' . $postId . '/comments', $pagination['page'], $pagination['per_page'], $total);
            $hateoasLinks['post'] = [
                'href' => $this->hateoas->postLinks($postId)['self']['href'],
                'rel' => 'post',
                'type' => 'GET'
            ];

            ApiResponse::paginated($transformedComments, $pagination['page'], $pagination['per_page'], $total, $hateoasLinks);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to fetch comments: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * Create a new post (requires authentication)
     *
     * POST /api/v1/posts
     *
     * @param array $params Request data
     * @return void
     */
    public function store($_params = [])
    {
        // Require authentication
        $this->requiresAuth = true;

        // Check permission - only administrators and editors can create posts
        if (!$this->hasPermission(['administrator', 'editor', 'author'])) {
            ApiResponse::forbidden('You do not have permission to create posts');
            return;
        }

        // Validate required fields
        $required = ['post_title', 'post_content'];
        $validationErrors = $this->validateRequired($this->requestData, $required);

        if ($validationErrors) {
            ApiResponse::unprocessableEntity('Validation failed', $validationErrors);
            return;
        }

        try {
            $slug = $this->generateSlug($this->requestData['post_title']);
            $userId = ApiAuth::getUserId();

            $postData = [
                'post_author' => $userId,
                'post_date' => date('Y-m-d H:i:s'),
                'post_title' => $this->sanitize($this->requestData['post_title']),
                'post_slug' => $slug,
                'post_content' => $this->requestData['post_content'],
                'post_summary' => isset($this->requestData['post_summary']) ? $this->sanitize($this->requestData['post_summary']) : null,
                'post_status' => isset($this->requestData['post_status']) ? $this->requestData['post_status'] : 'draft',
                'post_visibility' => isset($this->requestData['post_visibility']) ? $this->requestData['post_visibility'] : 'public',
                'post_tags' => isset($this->requestData['post_tags']) ? $this->sanitize($this->requestData['post_tags']) : null,
                'comment_status' => isset($this->requestData['comment_status']) ? $this->requestData['comment_status'] : 'open',
                'post_type' => 'blog'
            ];

            $postId = $this->postService->createPostApi($postData);

            if (isset($this->requestData['topics']) && is_array($this->requestData['topics'])) {
                $this->postService->setPostTopicsApi($postId, $this->requestData['topics']);
            }

            $createdPost = $this->postService->getPostByIdApi($postId);

            ApiResponse::created(PostApiDto::transform($createdPost, $this->getAppUrl()), 'Post created successfully', $this->hateoas->postLinks($postId, $slug), $this->getAppUrl() . '/api/v1/posts/' . $postId);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to create post: ' . $e->getMessage(), 500, 'CREATE_ERROR');
        }
    }

    /**
     * Update an existing post (requires authentication)
     *
     * PUT /api/v1/posts/{id}
     *
     * @param array $params Route parameters including 'id'
     * @return void
     */
    public function update($params = [])
    {
        // Require authentication
        $this->requiresAuth = true;

        $postId = isset($params['id']) ? (int)$params['id'] : 0;

        if (!$postId) {
            ApiResponse::badRequest('Post ID is required');
            return;
        }

        // Check permission
        if (!$this->hasPermission(['administrator', 'editor'])) {
            ApiResponse::forbidden('You do not have permission to update posts');
            return;
        }

        try {
            $post = $this->postService->getPostByIdApi($postId);

            if (!$post) {
                ApiResponse::notFound('Post not found');
                return;
            }

            $updateData = [];

            if (isset($this->requestData['post_title'])) {
                $updateData['post_title'] = $this->sanitize($this->requestData['post_title']);
                $updateData['post_slug'] = $this->generateSlug($this->requestData['post_title']);
            }

            if (isset($this->requestData['post_content'])) {
                $updateData['post_content'] = $this->requestData['post_content'];
            }

            if (isset($this->requestData['post_summary'])) {
                $updateData['post_summary'] = $this->sanitize($this->requestData['post_summary']);
            }

            if (isset($this->requestData['post_status'])) {
                $updateData['post_status'] = $this->requestData['post_status'];
            }

            if (isset($this->requestData['post_visibility'])) {
                $updateData['post_visibility'] = $this->requestData['post_visibility'];
            }

            if (isset($this->requestData['post_tags'])) {
                $updateData['post_tags'] = $this->sanitize($this->requestData['post_tags']);
            }

            if (isset($this->requestData['comment_status'])) {
                $updateData['comment_status'] = $this->requestData['comment_status'];
            }

            $updateData['post_modified'] = date('Y-m-d H:i:s');

            $this->postService->updatePostApi($postId, $updateData);

            if (isset($this->requestData['topics']) && is_array($this->requestData['topics'])) {
                $this->postService->setPostTopicsApi($postId, $this->requestData['topics']);
            }

            $updatedPost = $this->postService->getPostByIdApi($postId);

            ApiResponse::success(PostApiDto::transform($updatedPost, $this->getAppUrl()), 200, 'Post updated successfully');
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to update post: ' . $e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }

    /**
     * Delete a post (requires authentication)
     *
     * DELETE /api/v1/posts/{id}
     *
     * @param array $params Route parameters including 'id'
     * @return void
     */
    public function destroy($params = [])
    {
        // Require authentication
        $this->requiresAuth = true;

        $postId = isset($params['id']) ? (int)$params['id'] : 0;

        if (!$postId) {
            ApiResponse::badRequest('Post ID is required');
            return;
        }

        // Check permission
        if (!$this->hasPermission(['administrator'])) {
            ApiResponse::forbidden('Only administrators can delete posts');
            return;
        }

        try {
            $post = $this->postService->getPostByIdApi($postId);

            if (!$post) {
                ApiResponse::notFound('Post not found');
                return;
            }

            $this->postService->removePostApi($postId);

            ApiResponse::noContent();
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to delete post: ' . $e->getMessage(), 500, 'DELETE_ERROR');
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
