<?php

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
class PostsApiController extends ApiController
{
    /**
     * @var PostDao
     */
    private $postDao;

    /**
     * @var PostService
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
        $this->sanitizer = new Sanitize();
        $this->hateoas = new ApiHateoas();

        // Service requires validator - create minimal version
        $this->postService = null; // Will be initialized when needed
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
            $dbc = Registry::get('dbc');

            // Build query
            $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
                           p.post_title, p.post_slug, p.post_summary, p.post_status,
                           p.post_visibility, p.post_tags, p.post_type, p.comment_status,
                           u.user_login as author_login, u.user_fullname as author_name
                    FROM tbl_posts p
                    LEFT JOIN tbl_users u ON p.post_author = u.ID
                    WHERE p.post_status = 'publish' 
                    AND p.post_type = 'blog'
                    AND p.post_visibility = 'public'
                    ORDER BY p." . $sorting['sort_by'] . " " . $sorting['sort_order'] . "
                    LIMIT " . $pagination['per_page'] . " OFFSET " . $pagination['offset'];

            $stmt = $dbc->query($sql);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM tbl_posts 
                         WHERE post_status = 'publish' AND post_type = 'blog' AND post_visibility = 'public'";
            $countStmt = $dbc->query($countSql);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Transform posts for API response
            $transformedPosts = array_map([$this, 'transformPost'], $posts);

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

        $postId = isset($params[0]) ? (int)$params[0] : 0;

        if (!$postId) {
            ApiResponse::badRequest('Post ID is required');
            return;
        }

        try {
            $dbc = Registry::get('dbc');

            // Get post
            $sql = "SELECT p.*, u.user_login as author_login, u.user_fullname as author_name
                    FROM tbl_posts p
                    LEFT JOIN tbl_users u ON p.post_author = u.ID
                    WHERE p.ID = ? 
                    AND p.post_type = 'blog'";

            $stmt = $dbc->prepare($sql);
            $stmt->execute([$postId]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                ApiResponse::notFound('Post not found');
                return;
            }

            // Check visibility - only show public posts without password
            if ($post['post_visibility'] !== 'public') {
                ApiResponse::forbidden('This post is not publicly accessible');
                return;
            }

            // Get categories/topics for this post
            $topics = $this->getPostTopics($postId);

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
            $transformedPost = $this->transformPost($post);
            $transformedPost['topics'] = $topics;

            // Get featured image if available
            if ($post['media_id']) {
                $transformedPost['featured_image'] = $this->getMediaUrl($post['media_id']);
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

        $postId = isset($params[0]) ? (int)$params[0] : 0;

        if (!$postId) {
            ApiResponse::badRequest('Post ID is required');
            return;
        }

        // Get pagination
        $pagination = $this->getPagination($params);

        try {
            $dbc = Registry::get('dbc');

            // Get comments for this post
            $sql = "SELECT c.ID, c.comment_post_id, c.comment_parent_id, 
                           c.comment_author_name, c.comment_author_email,
                           c.comment_content, c.comment_status, c.comment_date
                    FROM tbl_comments c
                    WHERE c.comment_post_id = ?
                    AND c.comment_status = 'approved'
                    ORDER BY c.comment_date DESC
                    LIMIT ? OFFSET ?";

            $stmt = $dbc->prepare($sql);
            $stmt->execute([$postId, $pagination['per_page'], $pagination['offset']]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM tbl_comments 
                         WHERE comment_post_id = ? AND comment_status = 'approved'";
            $countStmt = $dbc->prepare($countSql);
            $countStmt->execute([$postId]);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Transform comments
            $transformedComments = array_map([$this, 'transformComment'], $comments);

            // Generate HATEOAS links
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
    public function store($params = [])
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
            $dbc = Registry::get('dbc');

            // Generate slug from title
            $slug = $this->generateSlug($this->requestData['post_title']);

            // Get user ID from auth
            $userId = ApiAuth::getUserId();

            // Prepare post data
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
                'comment_status' => isset($this->requestData['comment_status']) ? $this->requestData['comment_status'] : 'open'
            ];

            // Insert post
            $sql = "INSERT INTO tbl_posts (post_author, post_date, post_title, post_slug, 
                           post_content, post_summary, post_status, post_visibility, 
                           post_tags, comment_status, post_type)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'blog')";

            $stmt = $dbc->prepare($sql);
            $stmt->execute([
                $postData['post_author'],
                $postData['post_date'],
                $postData['post_title'],
                $postData['post_slug'],
                $postData['post_content'],
                $postData['post_summary'],
                $postData['post_status'],
                $postData['post_visibility'],
                $postData['post_tags'],
                $postData['comment_status']
            ]);

            $postId = $dbc->lastInsertId();

            // Handle categories/topics if provided
            if (isset($this->requestData['topics']) && is_array($this->requestData['topics'])) {
                $this->setPostTopics($postId, $this->requestData['topics']);
            }

            // Fetch created post
            $fetchSql = "SELECT * FROM tbl_posts WHERE ID = ?";
            $fetchStmt = $dbc->prepare($fetchSql);
            $fetchStmt->execute([$postId]);
            $createdPost = $fetchStmt->fetch(PDO::FETCH_ASSOC);

            ApiResponse::created($this->transformPost($createdPost), 'Post created successfully', $this->hateoas->postLinks($postId, $slug), $this->getAppUrl() . '/api/v1/posts/' . $postId);
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

        $postId = isset($params[0]) ? (int)$params[0] : 0;

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
            $dbc = Registry::get('dbc');

            // Check if post exists
            $checkSql = "SELECT ID, post_author FROM tbl_posts WHERE ID = ?";
            $checkStmt = $dbc->prepare($checkSql);
            $checkStmt->execute([$postId]);
            $post = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                ApiResponse::notFound('Post not found');
                return;
            }

            // Build update query
            $updates = [];
            $values = [];

            if (isset($this->requestData['post_title'])) {
                $updates[] = 'post_title = ?';
                $values[] = $this->sanitize($this->requestData['post_title']);
                $updates[] = 'post_slug = ?';
                $values[] = $this->generateSlug($this->requestData['post_title']);
            }

            if (isset($this->requestData['post_content'])) {
                $updates[] = 'post_content = ?';
                $values[] = $this->requestData['post_content'];
            }

            if (isset($this->requestData['post_summary'])) {
                $updates[] = 'post_summary = ?';
                $values[] = $this->sanitize($this->requestData['post_summary']);
            }

            if (isset($this->requestData['post_status'])) {
                $updates[] = 'post_status = ?';
                $values[] = $this->requestData['post_status'];
            }

            if (isset($this->requestData['post_visibility'])) {
                $updates[] = 'post_visibility = ?';
                $values[] = $this->requestData['post_visibility'];
            }

            if (isset($this->requestData['post_tags'])) {
                $updates[] = 'post_tags = ?';
                $values[] = $this->sanitize($this->requestData['post_tags']);
            }

            if (isset($this->requestData['comment_status'])) {
                $updates[] = 'comment_status = ?';
                $values[] = $this->requestData['comment_status'];
            }

            // Always update modified date
            $updates[] = 'post_modified = ?';
            $values[] = date('Y-m-d H:i:s');

            // Add post ID to values
            $values[] = $postId;

            // Execute update
            $sql = "UPDATE tbl_posts SET " . implode(', ', $updates) . " WHERE ID = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->execute($values);

            // Handle topics if provided
            if (isset($this->requestData['topics']) && is_array($this->requestData['topics'])) {
                // Delete existing topics
                $deleteSql = "DELETE FROM tbl_post_topic WHERE post_id = ?";
                $deleteStmt = $dbc->prepare($deleteSql);
                $deleteStmt->execute([$postId]);

                // Add new topics
                $this->setPostTopics($postId, $this->requestData['topics']);
            }

            // Fetch updated post
            $fetchSql = "SELECT * FROM tbl_posts WHERE ID = ?";
            $fetchStmt = $dbc->prepare($fetchSql);
            $fetchStmt->execute([$postId]);
            $updatedPost = $fetchStmt->fetch(PDO::FETCH_ASSOC);

            ApiResponse::success($this->transformPost($updatedPost), 200, 'Post updated successfully');
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

        $postId = isset($params[0]) ? (int)$params[0] : 0;

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
            $dbc = Registry::get('dbc');

            // Check if post exists
            $checkSql = "SELECT ID FROM tbl_posts WHERE ID = ?";
            $checkStmt = $dbc->prepare($checkSql);
            $checkStmt->execute([$postId]);
            $post = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$post) {
                ApiResponse::notFound('Post not found');
                return;
            }

            // Delete post topics first
            $deleteTopicSql = "DELETE FROM tbl_post_topic WHERE post_id = ?";
            $deleteTopicStmt = $dbc->prepare($deleteTopicSql);
            $deleteTopicStmt->execute([$postId]);

            // Delete comments for this post
            $deleteCommentSql = "DELETE FROM tbl_comments WHERE comment_post_id = ?";
            $deleteCommentStmt = $dbc->prepare($deleteCommentSql);
            $deleteCommentStmt->execute([$postId]);

            // Delete the post
            $deleteSql = "DELETE FROM tbl_posts WHERE ID = ?";
            $deleteStmt = $dbc->prepare($deleteSql);
            $deleteStmt->execute([$postId]);

            ApiResponse::noContent();
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to delete post: ' . $e->getMessage(), 500, 'DELETE_ERROR');
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
            'content' => $post['post_content'],
            'summary' => $post['post_summary'],
            'excerpt' => $post['post_summary'] ?? $this->generateExcerpt($post['post_content']),
            'status' => $post['post_status'],
            'visibility' => $post['post_visibility'],
            'tags' => $post['post_tags'] ? explode(',', $post['post_tags']) : [],
            'comment_status' => $post['comment_status'],
            'type' => $post['post_type'],
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
            'date' => $comment['comment_date']
        ];
    }

    /**
     * Get topics/categories for a post
     *
     * @param int $postId
     * @return array
     */
    private function getPostTopics($postId)
    {
        try {
            $dbc = Registry::get('dbc');

            $sql = "SELECT t.ID, t.topic_title, t.topic_slug
                    FROM tbl_topics t
                    INNER JOIN tbl_post_topic pt ON t.ID = pt.topic_id
                    WHERE pt.post_id = ?";

            $stmt = $dbc->prepare($sql);
            $stmt->execute([$postId]);
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(function ($topic) {
                return [
                    'id' => (int)$topic['ID'],
                    'title' => $topic['topic_title'],
                    'slug' => $topic['topic_slug']
                ];
            }, $topics);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Set topics for a post
     *
     * @param int $postId
     * @param array $topicIds
     */
    private function setPostTopics($postId, $topicIds)
    {
        try {
            $dbc = Registry::get('dbc');

            $sql = "INSERT INTO tbl_post_topic (post_id, topic_id) VALUES (?, ?)";
            $stmt = $dbc->prepare($sql);

            foreach ($topicIds as $topicId) {
                $stmt->execute([$postId, (int)$topicId]);
            }
        } catch (\Throwable $e) {
            // Silently fail - topics are optional
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
     * Get media URL
     *
     * @param int $mediaId
     * @return string|null
     */
    private function getMediaUrl($mediaId)
    {
        try {
            $dbc = Registry::get('dbc');

            $sql = "SELECT media_filename FROM tbl_media WHERE ID = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->execute([$mediaId]);
            $media = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($media) {
                $appUrl = $this->getAppUrl();
                return $appUrl . '/public/files/pictures/' . $media['media_filename'];
            }
        } catch (\Throwable $e) {
            // Silently fail
        }

        return null;
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

    /**
     * Generate excerpt from content
     *
     * @param string $content
     * @param int $length
     * @return string
     */
    private function generateExcerpt($content, $length = 150)
    {
        $content = strip_tags($content);
        if (strlen($content) <= $length) {
            return $content;
        }
        return substr($content, 0, $length) . '...';
    }
}
