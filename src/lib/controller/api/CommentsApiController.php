<?php

namespace Scriptlog\Controller\Api;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Comments API Controller
 *
 * Handles API requests for comments
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
use Scriptlog\Dao\CommentDao;
use Scriptlog\Dto\Api\CommentApiDto;
use Scriptlog\Service\CommentService;

class CommentsApiController extends ApiController
{
    /**
     * @var CommentDao
     */
    private $commentDao;

    /**
     * @var CommentService
     */
    private $commentService;

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

        // Initialize DAO and services
        $this->commentDao = new CommentDao();
        $this->sanitizer = new Sanitize();
        $this->hateoas = new ApiHateoas();
        $this->commentService = new CommentService($this->commentDao, new FormValidator(), $this->sanitizer);
    }

    /**
     * Get all approved comments (public endpoint)
     *
     * GET /api/v1/comments
     *
     * @param array $params Query parameters
     * @return void
     */
    public function index($params = [])
    {
        // This is a public endpoint - no auth required
        $this->requiresAuth = false;

        // Get pagination
        $pagination = $this->getPagination($params);

        // Get sorting
        $sorting = $this->getSorting($params, ['ID', 'comment_date']);

        // Filter by post_id if provided
        $postIdFilter = isset($params['post_id']) ? (int)$params['post_id'] : null;

        try {
            $sortBy = str_replace('`', '', $sorting['sort_by']);
            $sortOrder = $sorting['sort_order'];

            $comments = $this->commentService->getApprovedCommentsApi(
                $pagination['page'],
                $pagination['per_page'],
                $sortBy,
                $sortOrder,
                $postIdFilter
            );

            $total = $this->commentService->countApprovedCommentsApi($postIdFilter);

            // Transform comments
            $transformedComments = CommentApiDto::transformCollection($comments, $this->getAppUrl());

            // Generate HATEOAS pagination links
            $hateoasLinks = $this->hateoas->paginationLinks('comments', $pagination['page'], $pagination['per_page'], $total);

            // Add post filter link if post_id was provided
            if ($postIdFilter) {
                $hateoasLinks['post'] = [
                    'href' => $this->hateoas->postLinks($postIdFilter)['self']['href'],
                    'rel' => 'post',
                    'type' => 'GET'
                ];
            }

            ApiResponse::paginated($transformedComments, $pagination['page'], $pagination['per_page'], $total, $hateoasLinks);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to fetch comments: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * Get a single comment by ID (public endpoint)
     *
     * GET /api/v1/comments/{id}
     *
     * @param array $params Route parameters including 'id'
     * @return void
     */
    public function show($params = [])
    {
        // This is a public endpoint - no auth required
        $this->requiresAuth = false;

        $commentId = isset($params['id']) ? (int)$params['id'] : 0;

        if (!$commentId) {
            ApiResponse::badRequest('Comment ID is required');
            return;
        }

        try {
            $comment = $this->commentService->getCommentApi($commentId);

            if (!$comment) {
                ApiResponse::notFound('Comment not found');
                return;
            }

            // For non-approved comments, require auth
            if ($comment['comment_status'] !== 'approved' && !$this->isAuthenticated()) {
                ApiResponse::forbidden('This comment is not publicly visible');
                return;
            }

            $links = $this->hateoas->commentLinks($commentId, $comment['comment_post_id']);

            $responseData = CommentApiDto::transform($comment, $this->getAppUrl());

            ApiResponse::success($responseData, 200, null, $links);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to fetch comment: ' . $e->getMessage(), 500, 'FETCH_ERROR');
        }
    }

    /**
     * Create a new comment (public endpoint - for visitors)
     *
     * POST /api/v1/comments
     *
     * @param array $params Request data
     * @return void
     */
    public function store($_params = [])
    {
        // This is a public endpoint - visitors can post comments
        $this->requiresAuth = false;

        // Validate required fields
        $required = ['comment_author_name', 'comment_content', 'comment_post_id'];
        $validationErrors = $this->validateRequired($this->requestData, $required);

        if ($validationErrors) {
            ApiResponse::unprocessableEntity('Validation failed', $validationErrors);
            return;
        }

        // Validate post_id exists and is valid
        $postId = (int)$this->requestData['comment_post_id'];

        if (!$postId || $postId <= 0) {
            ApiResponse::badRequest('Invalid post ID');
            return;
        }

        try {
            $commentStatus = $this->commentService->checkPostAcceptsComments($postId);

            if ($commentStatus === null) {
                ApiResponse::notFound('Post not found');
                return;
            }

            if ($commentStatus !== 'open') {
                ApiResponse::forbidden('Comments are closed for this post');
                return;
            }

            // Validate email if provided
            if (isset($this->requestData['comment_author_email']) && !empty($this->requestData['comment_author_email'])) {
                if (!$this->validateEmail($this->requestData['comment_author_email'])) {
                    ApiResponse::badRequest('Invalid email address');
                    return;
                }
            }

            $ipAddress = $this->getClientIp();

            $commentId = $this->commentService->createCommentApi([
                'comment_post_id' => $postId,
                'comment_parent_id' => isset($this->requestData['comment_parent_id']) ? (int)$this->requestData['comment_parent_id'] : 0,
                'comment_author_name' => $this->sanitize($this->requestData['comment_author_name']),
                'comment_author_ip' => $ipAddress,
                'comment_author_email' => isset($this->requestData['comment_author_email']) ? $this->sanitize($this->requestData['comment_author_email']) : null,
                'comment_content' => $this->sanitize($this->requestData['comment_content']),
                'comment_status' => 'pending',
                'comment_date' => date('Y-m-d H:i:s')
            ]);

            $createdComment = $this->commentService->getCommentApi($commentId);

            ApiResponse::created(
                CommentApiDto::transform($createdComment, $this->getAppUrl()),
                'Comment submitted successfully. It will be visible after moderation.',
                $this->hateoas->commentLinks($commentId, $postId),
                $this->getAppUrl() . '/api/v1/comments/' . $commentId
            );
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to create comment: ' . $e->getMessage(), 500, 'CREATE_ERROR');
        }
    }

    /**
     * Update a comment (requires authentication)
     *
     * PUT /api/v1/comments/{id}
     *
     * @param array $params Route parameters including 'id'
     * @return void
     */
    public function update($params = [])
    {
        // Require authentication
        $this->requiresAuth = true;

        $commentId = isset($params['id']) ? (int)$params['id'] : 0;

        if (!$commentId) {
            ApiResponse::badRequest('Comment ID is required');
            return;
        }

        // Check permission
        if (!$this->hasPermission(['administrator', 'editor'])) {
            ApiResponse::forbidden('You do not have permission to update comments');
            return;
        }

        try {
            $existing = $this->commentService->getCommentApi($commentId);

            if (!$existing) {
                ApiResponse::notFound('Comment not found');
                return;
            }

            $updateData = [];

            if (isset($this->requestData['comment_author_name'])) {
                $updateData['comment_author_name'] = $this->sanitize($this->requestData['comment_author_name']);
            }

            if (isset($this->requestData['comment_content'])) {
                $updateData['comment_content'] = $this->sanitize($this->requestData['comment_content']);
            }

            if (isset($this->requestData['comment_status'])) {
                $updateData['comment_status'] = in_array($this->requestData['comment_status'], ['approved', 'pending', 'spam'])
                    ? $this->requestData['comment_status']
                    : 'pending';
            }

            if (empty($updateData)) {
                ApiResponse::badRequest('No fields to update');
                return;
            }

            $this->commentService->updateCommentApi($commentId, $updateData);

            $updatedComment = $this->commentService->getCommentApi($commentId);

            ApiResponse::success(CommentApiDto::transform($updatedComment, $this->getAppUrl()), 200, 'Comment updated successfully');
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to update comment: ' . $e->getMessage(), 500, 'UPDATE_ERROR');
        }
    }

    /**
     * Delete a comment (requires authentication)
     *
     * DELETE /api/v1/comments/{id}
     *
     * @param array $params Route parameters including 'id'
     * @return void
     */
    public function destroy($params = [])
    {
        // Require authentication
        $this->requiresAuth = true;

        $commentId = isset($params['id']) ? (int)$params['id'] : 0;

        if (!$commentId) {
            ApiResponse::badRequest('Comment ID is required');
            return;
        }

        // Check permission
        if (!$this->hasPermission(['administrator', 'editor'])) {
            ApiResponse::forbidden('You do not have permission to delete comments');
            return;
        }

        try {
            $existing = $this->commentService->getCommentApi($commentId);

            if (!$existing) {
                ApiResponse::notFound('Comment not found');
                return;
            }

            $this->commentService->removeCommentApi($commentId);

            ApiResponse::noContent();
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to delete comment: ' . $e->getMessage(), 500, 'DELETE_ERROR');
        }
    }



    /**
     * Get client IP address
     *
     * @return string
     */
    private function getClientIp()
    {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED',
                   'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
