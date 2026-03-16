<?php
/**
 * API Base Controller
 * 
 * Base controller for all API controllers
 * Provides common functionality for request handling, validation, and authentication
 * 
 * @category  Controller Class
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ApiController
{
    
    /**
     * @var array Request data (GET, POST, PUT, PATCH)
     */
    protected $requestData = [];
    
    /**
     * @var array Query parameters
     */
    protected $queryParams = [];
    
    /**
     * @var string HTTP method
     */
    protected $method = 'GET';
    
    /**
     * @var array Request headers
     */
    protected $headers = [];
    
    /**
     * @var array Required authentication for endpoints
     */
    protected $requiresAuth = true;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // Get request method
        $this->method = $_SERVER['REQUEST_METHOD'];
        
        // Get request headers
        $this->headers = $this->getHeaders();
        
        // Get request data based on method
        $this->requestData = $this->getRequestData();
        
        // Attempt authentication (can be overridden in child controllers)
        if ($this->requiresAuth) {
            $this->authenticate();
        }
    }
    
    /**
     * Get request headers
     * 
     * @return array
     */
    protected function getHeaders()
    {
        $headers = [];
        
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        
        return $headers;
    }
    
    /**
     * Get request data based on HTTP method
     * 
     * @return array
     */
    protected function getRequestData()
    {
        $data = [];
        
        switch ($this->method) {
            case 'GET':
                $data = $_GET;
                break;
                
            case 'POST':
                $data = $_POST;
                // Also check for JSON body
                $jsonData = $this->getJsonBody();
                if ($jsonData) {
                    $data = array_merge($data, $jsonData);
                }
                break;
                
            case 'PUT':
            case 'PATCH':
            case 'DELETE':
                // For PUT/PATCH/DELETE, data comes from:
                // 1. JSON body
                // 2. Form data
                $jsonData = $this->getJsonBody();
                if ($jsonData) {
                    $data = $jsonData;
                } else {
                    parse_str(file_get_contents('php://input'), $data);
                    $data = array_merge($_POST, $data);
                }
                break;
        }
        
        return $data;
    }
    
    /**
     * Get JSON body from request
     * 
     * @return array|null
     */
    protected function getJsonBody()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $data;
            }
        }
        
        return null;
    }
    
    /**
     * Authenticate the request
     * 
     * @return void
     */
    protected function authenticate()
    {
        // Attempt authentication
        ApiAuth::authenticate();
        
        // If authentication is required but failed, send 401
        if ($this->requiresAuth && !ApiAuth::isAuthenticated()) {
            ApiResponse::unauthorized('Authentication required. Please provide a valid API key or Bearer token.');
        }
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool
     */
    protected function isAuthenticated()
    {
        return ApiAuth::isAuthenticated();
    }
    
    /**
     * Get authenticated user
     * 
     * @return array|null
     */
    protected function getUser()
    {
        return ApiAuth::getUser();
    }
    
    /**
     * Check if user has permission
     * 
     * @param string|array $requiredLevels
     * @return bool
     */
    protected function hasPermission($requiredLevels)
    {
        return ApiAuth::hasPermission($requiredLevels);
    }
    
    /**
     * Validate required fields
     * 
     * @param array $data Data to validate
     * @param array $required Required fields
     * @return array|null Validation errors or null if valid
     */
    protected function validateRequired($data, $required)
    {
        $errors = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = [
                    'field' => $field,
                    'message' => 'The ' . $field . ' field is required'
                ];
            }
        }
        
        return empty($errors) ? null : $errors;
    }
    
    /**
     * Validate email format
     * 
     * @param string $email
     * @return bool
     */
    protected function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Sanitize string input
     * 
     * @param string $value
     * @return string
     */
    protected function sanitize($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get pagination parameters
     * 
     * @param array $params Query parameters
     * @return array
     */
    protected function getPagination($params)
    {
        $page = isset($params['page']) ? max(1, (int)$params['page']) : 1;
        $perPage = isset($params['per_page']) ? min(max(1, (int)$params['per_page']), 100) : 10;
        $offset = ($page - 1) * $perPage;
        
        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => $offset
        ];
    }
    
    /**
     * Get sorting parameters
     * 
     * @param array $params Query parameters
     * @param array $allowedFields Allowed sort fields
     * @return array
     */
    protected function getSorting($params, $allowedFields = [])
    {
        $sortBy = 'ID';
        $sortOrder = 'DESC';
        
        if (isset($params['sort_by']) && !empty($allowedFields)) {
            $sortBy = in_array($params['sort_by'], $allowedFields) ? $params['sort_by'] : 'ID';
        }
        
        if (isset($params['sort_order'])) {
            $sortOrder = strtoupper($params['sort_order']) === 'ASC' ? 'ASC' : 'DESC';
        }
        
        return [
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder
        ];
    }
    
    /**
     * API info endpoint
     * 
     * @return void
     */
    public function info()
    {
        $apiInfo = [
            'name' => 'Blogware RESTful API',
            'version' => '1.0.0',
            'description' => 'RESTful API for Blogware content management system',
            'base_url' => '/api/v1',
            'authentication' => [
                'type' => 'API Key or Bearer Token',
                'header' => 'X-API-Key or Authorization: Bearer <token>',
                'required' => $this->requiresAuth
            ],
            'endpoints' => [
                'posts' => [
                    'GET /api/v1/posts' => 'List all published posts (paginated)',
                    'GET /api/v1/posts/{id}' => 'Get a single post by ID',
                    'GET /api/v1/posts/{id}/comments' => 'Get comments for a post',
                    'POST /api/v1/posts' => 'Create a new post (requires auth)',
                    'PUT /api/v1/posts/{id}' => 'Update a post (requires auth)',
                    'DELETE /api/v1/posts/{id}' => 'Delete a post (requires auth)'
                ],
                'categories' => [
                    'GET /api/v1/categories' => 'List all categories',
                    'GET /api/v1/categories/{id}' => 'Get a single category',
                    'GET /api/v1/categories/{id}/posts' => 'Get posts in a category',
                    'POST /api/v1/categories' => 'Create a category (requires auth)',
                    'PUT /api/v1/categories/{id}' => 'Update a category (requires auth)',
                    'DELETE /api/v1/categories/{id}' => 'Delete a category (requires auth)'
                ],
                'comments' => [
                    'GET /api/v1/comments' => 'List all approved comments',
                    'GET /api/v1/comments/{id}' => 'Get a single comment',
                    'POST /api/v1/comments' => 'Create a new comment',
                    'PUT /api/v1/comments/{id}' => 'Update a comment (requires auth)',
                    'DELETE /api/v1/comments/{id}' => 'Delete a comment (requires auth)'
                ],
                'archives' => [
                    'GET /api/v1/archives' => 'List available archive dates',
                    'GET /api/v1/archives/{year}' => 'Get posts from a specific year',
                    'GET /api/v1/archives/{year}/{month}' => 'Get posts from a specific month'
                ]
            ],
            'pagination' => [
                'parameters' => [
                    'page' => 'Page number (default: 1)',
                    'per_page' => 'Items per page (default: 10, max: 100)'
                ]
            ],
            'sorting' => [
                'parameters' => [
                    'sort_by' => 'Field to sort by',
                    'sort_order' => 'ASC or DESC'
                ]
            ]
        ];
        
        ApiResponse::success($apiInfo, 200, 'Welcome to Blogware RESTful API');
    }
}
