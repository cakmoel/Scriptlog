<?php

/**
 * API Response Handler
 *
 * Provides consistent JSON responses for the RESTful API
 * following RFC 9457 Error Response specification
 * and OpenAPI 3.0 standards
 *
 * @category  Core Class
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ApiResponse
{
    /**
     * HTTP Status Codes
     */
    public const HTTP_OK = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_NO_CONTENT = 204;
    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHORIZED = 401;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_CONFLICT = 409;
    public const HTTP_UNPROCESSABLE_ENTITY = 422;
    public const HTTP_TOO_MANY_REQUESTS = 429;
    public const HTTP_INTERNAL_SERVER_ERROR = 500;
    public const HTTP_SERVICE_UNAVAILABLE = 503;

    /**
     * Rate limiting settings
     */
    public const RATE_LIMIT = 60;
    public const RATE_WINDOW = 60;

    /**
     * Track rate limit for current request
     */
    private static $rateLimitRemaining;
    private static $rateLimitReset;

    /**
     * Initialize rate limiting
     */
    public static function initRateLimit()
    {
        self::$rateLimitRemaining = self::RATE_LIMIT;
        self::$rateLimitReset = time() + self::RATE_WINDOW;
    }

    /**
     * Set rate limit headers
     */
    private static function setRateLimitHeaders()
    {
        header('X-RateLimit-Limit: ' . self::RATE_LIMIT);
        header('X-RateLimit-Remaining: ' . self::$rateLimitRemaining);
        header('X-RateLimit-Reset: ' . self::$rateLimitReset);
    }

    /**
     * Send a successful JSON response
     *
     * @param mixed $data The data to be encoded as JSON
     * @param int $statusCode HTTP status code (default: 200)
     * @param string $message Optional success message
     * @return void
     */
    public static function success($data = null, $statusCode = self::HTTP_OK, $message = null)
    {
        self::send([
            'success' => true,
            'status' => $statusCode,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    /**
     * Send a created response (201)
     *
     * @param mixed $data The created resource data
     * @param string $message Optional success message
     * @return void
     */
    public static function created($data, $message = 'Resource created successfully')
    {
        self::send([
            'success' => true,
            'status' => self::HTTP_CREATED,
            'message' => $message,
            'data' => $data
        ], self::HTTP_CREATED);
    }

    /**
     * Send a no content response (204)
     *
     * @return void
     */
    public static function noContent()
    {
        self::send(null, self::HTTP_NO_CONTENT);
    }

    /**
     * Send an error response
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param string $errorCode Optional machine-readable error code
     * @param mixed $errors Optional validation errors array
     * @return void
     */
    public static function error($message, $statusCode = self::HTTP_INTERNAL_SERVER_ERROR, $errorCode = null, $errors = null)
    {
        $response = [
            'success' => false,
            'status' => $statusCode,
            'error' => [
                'code' => $errorCode ?? self::getErrorCode($statusCode),
                'message' => $message
            ]
        ];

        if ($errors !== null) {
            $response['error']['details'] = $errors;
        }

        self::send($response, $statusCode);
    }

    /**
     * Send a 400 Bad Request error
     *
     * @param string $message Error message
     * @param mixed $errors Optional validation errors
     * @return void
     */
    public static function badRequest($message = 'Bad Request', $errors = null)
    {
        self::error($message, self::HTTP_BAD_REQUEST, 'BAD_REQUEST', $errors);
    }

    /**
     * Send a 401 Unauthorized error
     *
     * @param string $message Error message
     * @return void
     */
    public static function unauthorized($message = 'Unauthorized - Authentication required')
    {
        self::error($message, self::HTTP_UNAUTHORIZED, 'UNAUTHORIZED');
    }

    /**
     * Send a 403 Forbidden error
     *
     * @param string $message Error message
     * @return void
     */
    public static function forbidden($message = 'Forbidden - Access denied')
    {
        self::error($message, self::HTTP_FORBIDDEN, 'FORBIDDEN');
    }

    /**
     * Send a 409 Conflict error
     *
     * @param string $message Error message
     * @return void
     */
    public static function conflict($message = 'Conflict - Resource already exists')
    {
        self::error($message, self::HTTP_CONFLICT, 'CONFLICT');
    }

    /**
     * Send a 404 Not Found error
     *
     * @param string $message Error message
     * @return void
     */
    public static function notFound($message = 'Resource not found')
    {
        self::error($message, self::HTTP_NOT_FOUND, 'NOT_FOUND');
    }

    /**
     * Send a 422 Unprocessable Entity error
     *
     * @param string $message Error message
     * @param mixed $errors Validation errors
     * @return void
     */
    public static function unprocessableEntity($message = 'Validation failed', $errors = null)
    {
        self::error($message, self::HTTP_UNPROCESSABLE_ENTITY, 'VALIDATION_ERROR', $errors);
    }

    /**
     * Send a 429 Too Many Requests error
     *
     * @param string $message Error message
     * @param int $retryAfter Seconds until retry
     * @return void
     */
    public static function tooManyRequests($message = 'Too many requests - Please slow down', $retryAfter = 60)
    {
        header('Retry-After: ' . $retryAfter);
        self::error($message, self::HTTP_TOO_MANY_REQUESTS, 'RATE_LIMIT_EXCEEDED');
    }

    /**
     * Send a method not allowed error
     *
     * @param string $message Error message
     * @return void
     */
    public static function methodNotAllowed($message = 'Method not allowed')
    {
        self::error($message, self::HTTP_METHOD_NOT_ALLOWED, 'METHOD_NOT_ALLOWED');
    }

    /**
     * Send a paginated response
     *
     * @param array $data The paginated data
     * @param int $currentPage Current page number
     * @param int $perPage Items per page
     * @param int $totalItems Total number of items
     * @return void
     */
    public static function paginated($data, $currentPage, $perPage, $totalItems)
    {
        $totalPages = ceil($totalItems / $perPage);

        $response = [
            'success' => true,
            'status' => self::HTTP_OK,
            'data' => $data,
            'pagination' => [
                'current_page' => (int) $currentPage,
                'per_page' => (int) $perPage,
                'total_items' => (int) $totalItems,
                'total_pages' => (int) $totalPages,
                'has_next_page' => $currentPage < $totalPages,
                'has_previous_page' => $currentPage > 1
            ]
        ];

        self::send($response, self::HTTP_OK);
    }

    /**
     * Send raw JSON response
     *
     * @param mixed $data Data to be encoded
     * @param int $statusCode HTTP status code
     * @return void
     */
    private static function send($data, $statusCode)
    {
        // Set HTTP status code
        http_response_code($statusCode);

        // Set content type - OpenAPI compliant
        header('Content-Type: application/json; charset=utf-8');

        // Prevent caching for API responses
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Add API version header - OpenAPI compliant
        header('X-API-Version: ' . API_VERSION);

        // Add rate limiting headers - OpenAPI compliant
        self::setRateLimitHeaders();

        // Add allow header for method not allowed
        if ($statusCode === self::HTTP_METHOD_NOT_ALLOWED) {
            header('Allow: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        }

        // Encode and output JSON
        if ($data === null) {
            echo json_encode(null);
        } else {
            // Ensure proper JSON encoding with UTF-8 support
            $jsonOptions = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
            echo json_encode($data, $jsonOptions);
        }

        // End script execution
        exit;
    }

    /**
     * Get error code from status code
     *
     * @param int $statusCode HTTP status code
     * @return string Machine-readable error code
     */
    private static function getErrorCode($statusCode)
    {
        $errorCodes = [
            self::HTTP_BAD_REQUEST => 'BAD_REQUEST',
            self::HTTP_UNAUTHORIZED => 'UNAUTHORIZED',
            self::HTTP_FORBIDDEN => 'FORBIDDEN',
            self::HTTP_NOT_FOUND => 'NOT_FOUND',
            self::HTTP_METHOD_NOT_ALLOWED => 'METHOD_NOT_ALLOWED',
            self::HTTP_CONFLICT => 'CONFLICT',
            self::HTTP_UNPROCESSABLE_ENTITY => 'VALIDATION_ERROR',
            self::HTTP_TOO_MANY_REQUESTS => 'RATE_LIMIT_EXCEEDED',
            self::HTTP_INTERNAL_SERVER_ERROR => 'INTERNAL_SERVER_ERROR',
            self::HTTP_SERVICE_UNAVAILABLE => 'SERVICE_UNAVAILABLE'
        ];

        return $errorCodes[$statusCode] ?? 'UNKNOWN_ERROR';
    }

    /**
     * Set CORS headers for cross-origin requests
     *
     * @param string $origin Allowed origin (default: *)
     * @return void
     */
    public static function setCorsHeaders($origin = '*')
    {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key, X-Requested-With');
        header('Access-Control-Max-Age: 3600');
    }
}
