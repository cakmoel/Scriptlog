<?php

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
class CommentsApiController extends ApiController
{
    /**
     * @var CommentDao
     */
    private $commentDao;

    /**
     * @var Sanitize
     */
    private $sanitizer;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Initialize DAO
        $this->commentDao = new CommentDao();
        $this->sanitizer = new Sanitize();
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
            $dbc = Registry::get('dbc');

            // Build query
            $whereClause = "WHERE c.comment_status = 'approved'";
            $countWhereClause = "WHERE comment_status = 'approved'";
            $paramsArr = [];

            if ($postIdFilter) {
                $whereClause .= " AND c.comment_post_id = ?";
                $countWhereClause .= " AND comment_post_id = ?";
                $paramsArr[] = $postIdFilter;
            }

            $sql = "SELECT c.ID, c.comment_post_id, c.comment_parent_id, 
                           c.comment_author_name, c.comment_author_email,
                           c.comment_content, c.comment_status, c.comment_date,
                           p.post_title, p.post_slug
                    FROM tbl_comments c
                    LEFT JOIN tbl_posts p ON c.comment_post_id = p.ID
                    " . $whereClause . "
                    ORDER BY c." . $sorting['sort_by'] . " " . $sorting['sort_order'] . "
                    LIMIT " . $pagination['per_page'] . " OFFSET " . $pagination['offset'];

            $stmt = $dbc->prepare($sql);
            $stmt->execute($paramsArr);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM tbl_comments " . $countWhereClause;
            $countStmt = $dbc->prepare($countSql);
            $countStmt->execute($postIdFilter ? [$postIdFilter] : []);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Transform comments
            $transformedComments = array_map([$this, 'transformComment'], $comments);

            ApiResponse::paginated($transformedComments, $pagination['page'], $pagination['per_page'], $total);
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

        $commentId = isset($params[0]) ? (int)$params[0] : 0;

        if (!$commentId) {
            ApiResponse::badRequest('Comment ID is required');
            return;
        }

        try {
            $dbc = Registry::get('dbc');

            // Get comment
            $sql = "SELECT c.*, p.post_title, p.post_slug
                    FROM tbl_comments c
                    LEFT JOIN tbl_posts p ON c.comment_post_id = p.ID
                    WHERE c.ID = ?";

            $stmt = $dbc->prepare($sql);
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$comment) {
                ApiResponse::notFound('Comment not found');
                return;
            }

            // For non-approved comments, require auth
            if ($comment['comment_status'] !== 'approved' && !$this->isAuthenticated()) {
                ApiResponse::forbidden('This comment is not publicly visible');
                return;
            }

            ApiResponse::success($this->transformComment($comment));
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
    public function store($params = [])
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
            $dbc = Registry::get('dbc');

            // Check if post exists and allows comments
            $checkPostSql = "SELECT ID, comment_status FROM tbl_posts WHERE ID = ?";
            $checkPostStmt = $dbc->prepare($checkPostSql);
            $checkPostStmt->execute([$postId]);
            $post = $checkPostStmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                ApiResponse::notFound('Post not found');
                return;
            }

            if ($post['comment_status'] !== 'open') {
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

            // Get client IP
            $ipAddress = $this->getClientIp();

            // Insert comment (default status is pending for moderation)
            $sql = "INSERT INTO tbl_comments 
                    (comment_post_id, comment_parent_id, comment_author_name, 
                     comment_author_ip, comment_author_email, comment_content, 
                     comment_status, comment_date)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";

            $stmt = $dbc->prepare($sql);
            $stmt->execute([
                $postId,
                isset($this->requestData['comment_parent_id']) ? (int)$this->requestData['comment_parent_id'] : 0,
                $this->sanitize($this->requestData['comment_author_name']),
                $ipAddress,
                isset($this->requestData['comment_author_email']) ? $this->sanitize($this->requestData['comment_author_email']) : null,
                $this->sanitize($this->requestData['comment_content'])
            ]);

            $commentId = $dbc->lastInsertId();

            // Fetch created comment
            $fetchSql = "SELECT * FROM tbl_comments WHERE ID = ?";
            $fetchStmt = $dbc->prepare($fetchSql);
            $fetchStmt->execute([$commentId]);
            $createdComment = $fetchStmt->fetch(PDO::FETCH_ASSOC);

            ApiResponse::created(
                $this->transformComment($createdComment),
                'Comment submitted successfully. It will be visible after moderation.'
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

        $commentId = isset($params[0]) ? (int)$params[0] : 0;

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
            $dbc = Registry::get('dbc');

            // Check if comment exists
            $checkSql = "SELECT ID FROM tbl_comments WHERE ID = ?";
            $checkStmt = $dbc->prepare($checkSql);
            $checkStmt->execute([$commentId]);
            $comment = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$comment) {
                ApiResponse::notFound('Comment not found');
                return;
            }

            // Build update query
            $updates = [];
            $values = [];

            if (isset($this->requestData['comment_author_name'])) {
                $updates[] = 'comment_author_name = ?';
                $values[] = $this->sanitize($this->requestData['comment_author_name']);
            }

            if (isset($this->requestData['comment_content'])) {
                $updates[] = 'comment_content = ?';
                $values[] = $this->sanitize($this->requestData['comment_content']);
            }

            if (isset($this->requestData['comment_status'])) {
                $updates[] = 'comment_status = ?';
                $values[] = in_array($this->requestData['comment_status'], ['approved', 'pending', 'spam'])
                    ? $this->requestData['comment_status']
                    : 'pending';
            }

            if (empty($updates)) {
                ApiResponse::badRequest('No fields to update');
                return;
            }

            // Add comment ID to values
            $values[] = $commentId;

            // Execute update
            $sql = "UPDATE tbl_comments SET " . implode(', ', $updates) . " WHERE ID = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->execute($values);

            // Fetch updated comment
            $fetchSql = "SELECT c.*, p.post_title, p.post_slug
                         FROM tbl_comments c
                         LEFT JOIN tbl_posts p ON c.comment_post_id = p.ID
                         WHERE c.ID = ?";
            $fetchStmt = $dbc->prepare($fetchSql);
            $fetchStmt->execute([$commentId]);
            $updatedComment = $fetchStmt->fetch(PDO::FETCH_ASSOC);

            ApiResponse::success($this->transformComment($updatedComment), 200, 'Comment updated successfully');
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

        $commentId = isset($params[0]) ? (int)$params[0] : 0;

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
            $dbc = Registry::get('dbc');

            // Check if comment exists
            $checkSql = "SELECT ID FROM tbl_comments WHERE ID = ?";
            $checkStmt = $dbc->prepare($checkSql);
            $checkStmt->execute([$commentId]);
            $comment = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$comment) {
                ApiResponse::notFound('Comment not found');
                return;
            }

            // Delete replies first
            $deleteRepliesSql = "DELETE FROM tbl_comments WHERE comment_parent_id = ?";
            $deleteRepliesStmt = $dbc->prepare($deleteRepliesSql);
            $deleteRepliesStmt->execute([$commentId]);

            // Delete the comment
            $deleteSql = "DELETE FROM tbl_comments WHERE ID = ?";
            $deleteStmt = $dbc->prepare($deleteSql);
            $deleteStmt->execute([$commentId]);

            ApiResponse::success(null, 200, 'Comment deleted successfully');
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to delete comment: ' . $e->getMessage(), 500, 'DELETE_ERROR');
        }
    }

    /**
     * Transform comment data for API response
     *
     * @param array $comment
     * @return array
     */
    private function transformComment($comment)
    {
        return [
            'id' => (int)$comment['ID'],
            'post_id' => (int)$comment['comment_post_id'],
            'parent_id' => (int)$comment['comment_parent_id'],
            'author' => [
                'name' => $comment['comment_author_name'],
                'email' => $comment['comment_author_email'] ?? ''
            ],
            'content' => $comment['comment_content'],
            'status' => $comment['comment_status'],
            'date' => $comment['comment_date'],
            'post' => isset($comment['post_title']) ? [
                'title' => $comment['post_title'],
                'slug' => $comment['post_slug'] ?? '',
                'url' => $this->getPostUrl($comment['comment_post_id'], $comment['post_slug'] ?? '')
            ] : null
        ];
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
        return $appUrl . '/post/' . $id . '/' . ($slug ?: 'post');
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
