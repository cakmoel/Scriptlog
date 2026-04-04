<?php

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
class CategoriesApiController extends ApiController
{
    /**
     * @var TopicDao
     */
    private $topicDao;

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
        $this->topicDao = new TopicDao();
        $this->sanitizer = new Sanitize();
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
        $this->requiresAuth = false;

        // Get pagination
        $pagination = $this->getPagination($params);

        // Get sorting
        $sorting = $this->getSorting($params, ['ID', 'topic_title', 'topic_slug']);

        try {
            $dbc = Registry::get('dbc');

            // Get categories with post count
            $sql = "SELECT t.ID, t.topic_title, t.topic_slug, t.topic_status,
                           (SELECT COUNT(*) FROM tbl_post_topic pt 
                            INNER JOIN tbl_posts p ON pt.post_id = p.ID 
                            WHERE pt.topic_id = t.ID 
                            AND p.post_status = 'publish' 
                            AND p.post_type = 'blog') as post_count
                    FROM tbl_topics t
                    WHERE t.topic_status = 'Y'
                    ORDER BY t." . $sorting['sort_by'] . " " . $sorting['sort_order'] . "
                    LIMIT " . $pagination['per_page'] . " OFFSET " . $pagination['offset'];

            $stmt = $dbc->query($sql);
            $topics = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM tbl_topics WHERE topic_status = 'Y'";
            $countStmt = $dbc->query($countSql);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Transform topics
            $transformedTopics = array_map([$this, 'transformTopic'], $topics);

            ApiResponse::paginated($transformedTopics, $pagination['page'], $pagination['per_page'], $total);
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
        $this->requiresAuth = false;

        $topicId = isset($params[0]) ? (int)$params[0] : 0;

        if (!$topicId) {
            ApiResponse::badRequest('Category ID is required');
            return;
        }

        try {
            $dbc = Registry::get('dbc');

            // Get topic
            $sql = "SELECT t.*,
                           (SELECT COUNT(*) FROM tbl_post_topic pt 
                            INNER JOIN tbl_posts p ON pt.post_id = p.ID 
                            WHERE pt.topic_id = t.ID 
                            AND p.post_status = 'publish' 
                            AND p.post_type = 'blog') as post_count
                    FROM tbl_topics t
                    WHERE t.ID = ?";

            $stmt = $dbc->prepare($sql);
            $stmt->execute([$topicId]);
            $topic = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$topic) {
                ApiResponse::notFound('Category not found');
                return;
            }

            ApiResponse::success($this->transformTopic($topic));
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
        $this->requiresAuth = false;

        $topicId = isset($params[0]) ? (int)$params[0] : 0;

        if (!$topicId) {
            ApiResponse::badRequest('Category ID is required');
            return;
        }

        // Get pagination
        $pagination = $this->getPagination($params);

        // Get sorting
        $sorting = $this->getSorting($params, ['ID', 'post_date', 'post_modified', 'post_title']);

        try {
            $dbc = Registry::get('dbc');

            // Check if category exists
            $checkSql = "SELECT topic_title, topic_slug FROM tbl_topics WHERE ID = ? AND topic_status = 'Y'";
            $checkStmt = $dbc->prepare($checkSql);
            $checkStmt->execute([$topicId]);
            $topic = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$topic) {
                ApiResponse::notFound('Category not found');
                return;
            }

            // Get posts in this category
            $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified,
                           p.post_title, p.post_slug, p.post_summary, p.post_status,
                           p.post_visibility, p.post_tags, p.post_type, p.comment_status,
                           u.user_login as author_login, u.user_fullname as author_name
                    FROM tbl_posts p
                    INNER JOIN tbl_post_topic pt ON p.ID = pt.post_id
                    LEFT JOIN tbl_users u ON p.post_author = u.ID
                    WHERE pt.topic_id = ?
                    AND p.post_status = 'publish'
                    AND p.post_type = 'blog'
                    AND p.post_visibility = 'public'
                    ORDER BY p." . $sorting['sort_by'] . " " . $sorting['sort_order'] . "
                    LIMIT " . $pagination['per_page'] . " OFFSET " . $pagination['offset'];

            $stmt = $dbc->prepare($sql);
            $stmt->execute([$topicId]);
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get total count
            $countSql = "SELECT COUNT(*) as total
                         FROM tbl_posts p
                         INNER JOIN tbl_post_topic pt ON p.ID = pt.post_id
                         WHERE pt.topic_id = ?
                         AND p.post_status = 'publish'
                         AND p.post_type = 'blog'
                         AND p.post_visibility = 'public'";
            $countStmt = $dbc->prepare($countSql);
            $countStmt->execute([$topicId]);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Transform posts
            $transformedPosts = array_map([$this, 'transformPost'], $posts);

            // Include category info
            $response = [
                'category' => [
                    'id' => (int)$topicId,
                    'title' => $topic['topic_title'],
                    'slug' => $topic['topic_slug']
                ],
                'posts' => $transformedPosts
            ];

            ApiResponse::paginated($response, $pagination['page'], $pagination['per_page'], $total);
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
    public function store($params = [])
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
            $dbc = Registry::get('dbc');

            // Generate slug from title
            $slug = $this->generateSlug($this->requestData['topic_title']);

            // Check if slug already exists
            $checkSql = "SELECT ID FROM tbl_topics WHERE topic_slug = ?";
            $checkStmt = $dbc->prepare($checkSql);
            $checkStmt->execute([$slug]);

            if ($checkStmt->fetch()) {
                ApiResponse::conflict('A category with this title already exists');
                return;
            }

            // Insert topic
            $sql = "INSERT INTO tbl_topics (topic_title, topic_slug, topic_status) VALUES (?, ?, ?)";
            $stmt = $dbc->prepare($sql);
            $stmt->execute([
                $this->sanitize($this->requestData['topic_title']),
                $slug,
                isset($this->requestData['topic_status']) ? $this->requestData['topic_status'] : 'Y'
            ]);

            $topicId = $dbc->lastInsertId();

            // Fetch created topic
            $fetchSql = "SELECT * FROM tbl_topics WHERE ID = ?";
            $fetchStmt = $dbc->prepare($fetchSql);
            $fetchStmt->execute([$topicId]);
            $createdTopic = $fetchStmt->fetch(PDO::FETCH_ASSOC);

            ApiResponse::created($this->transformTopic($createdTopic), 'Category created successfully');
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

        $topicId = isset($params[0]) ? (int)$params[0] : 0;

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
            $dbc = Registry::get('dbc');

            // Check if topic exists
            $checkSql = "SELECT ID FROM tbl_topics WHERE ID = ?";
            $checkStmt = $dbc->prepare($checkSql);
            $checkStmt->execute([$topicId]);
            $topic = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$topic) {
                ApiResponse::notFound('Category not found');
                return;
            }

            // Build update query
            $updates = [];
            $values = [];

            if (isset($this->requestData['topic_title'])) {
                $updates[] = 'topic_title = ?';
                $values[] = $this->sanitize($this->requestData['topic_title']);
                $updates[] = 'topic_slug = ?';
                $values[] = $this->generateSlug($this->requestData['topic_title']);
            }

            if (isset($this->requestData['topic_status'])) {
                $updates[] = 'topic_status = ?';
                $values[] = in_array($this->requestData['topic_status'], ['Y', 'N']) ? $this->requestData['topic_status'] : 'Y';
            }

            if (empty($updates)) {
                ApiResponse::badRequest('No fields to update');
                return;
            }

            // Add topic ID to values
            $values[] = $topicId;

            // Execute update
            $sql = "UPDATE tbl_topics SET " . implode(', ', $updates) . " WHERE ID = ?";
            $stmt = $dbc->prepare($sql);
            $stmt->execute($values);

            // Fetch updated topic
            $fetchSql = "SELECT * FROM tbl_topics WHERE ID = ?";
            $fetchStmt = $dbc->prepare($fetchSql);
            $fetchStmt->execute([$topicId]);
            $updatedTopic = $fetchStmt->fetch(PDO::FETCH_ASSOC);

            ApiResponse::success($this->transformTopic($updatedTopic), 200, 'Category updated successfully');
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

        $topicId = isset($params[0]) ? (int)$params[0] : 0;

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
            $dbc = Registry::get('dbc');

            // Check if topic exists
            $checkSql = "SELECT ID FROM tbl_topics WHERE ID = ?";
            $checkStmt = $dbc->prepare($checkSql);
            $checkStmt->execute([$topicId]);
            $topic = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$topic) {
                ApiResponse::notFound('Category not found');
                return;
            }

            // Delete post-topic relationships first
            $deleteRelSql = "DELETE FROM tbl_post_topic WHERE topic_id = ?";
            $deleteRelStmt = $dbc->prepare($deleteRelSql);
            $deleteRelStmt->execute([$topicId]);

            // Delete the topic
            $deleteSql = "DELETE FROM tbl_topics WHERE ID = ?";
            $deleteStmt = $dbc->prepare($deleteSql);
            $deleteStmt->execute([$topicId]);

            ApiResponse::success(null, 200, 'Category deleted successfully');
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to delete category: ' . $e->getMessage(), 500, 'DELETE_ERROR');
        }
    }

    /**
     * Transform topic data for API response
     *
     * @param array $topic
     * @return array
     */
    private function transformTopic($topic)
    {
        return [
            'id' => (int)$topic['ID'],
            'title' => $topic['topic_title'],
            'slug' => $topic['topic_slug'],
            'status' => $topic['topic_status'],
            'post_count' => isset($topic['post_count']) ? (int)$topic['post_count'] : 0,
            'url' => $this->getCategoryUrl($topic['topic_slug'])
        ];
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
     * Get category URL
     *
     * @param string $slug
     * @return string
     */
    private function getCategoryUrl($slug)
    {
        $appUrl = $this->getAppUrl();
        return $appUrl . '/category/' . $slug;
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
