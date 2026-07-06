<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * API Router
 *
 * Handles routing of API requests to appropriate controllers
 * Supports GET, POST, PUT, PATCH, DELETE HTTP methods
 *
 * @category  Core Class
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ApiRouter
{
    /**
     * @var array Registered routes
     */
    private $routes = [];

    /**
     * @var array Route parameters captured from URI
     */
    private $params = [];

    /**
     * @var array Allowed HTTP methods
     */
    private $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * @var array|null Compiled combined regex cache per method
     */
    private $compiled = null;

    /**
     * @var int Max routes per combined regex chunk
     */
    const CHUNK_SIZE = 10;

    /**
     * Register a GET route
     *
     * @param string $pattern Route pattern
     * @param string $handler Controller@method
     * @return self
     */
    public function get($pattern, $handler)
    {
        return $this->addRoute('GET', $pattern, $handler);
    }

    /**
     * Register a POST route
     *
     * @param string $pattern Route pattern
     * @param string $handler Controller@method
     * @return self
     */
    public function post($pattern, $handler)
    {
        return $this->addRoute('POST', $pattern, $handler);
    }

    /**
     * Register a PUT route
     *
     * @param string $pattern Route pattern
     * @param string $handler Controller@method
     * @return self
     */
    public function put($pattern, $handler)
    {
        return $this->addRoute('PUT', $pattern, $handler);
    }

    /**
     * Register a PATCH route
     *
     * @param string $pattern Route pattern
     * @param string $handler Controller@method
     * @return self
     */
    public function patch($pattern, $handler)
    {
        return $this->addRoute('PATCH', $pattern, $handler);
    }

    /**
     * Register a DELETE route
     *
     * @param string $pattern Route pattern
     * @param string $handler Controller@method
     * @return self
     */
    public function delete($pattern, $handler)
    {
        return $this->addRoute('DELETE', $pattern, $handler);
    }

    /**
     * Register a route for any HTTP method
     *
     * @param string $method HTTP method
     * @param string $pattern Route pattern
     * @param string $handler Controller@method
     * @return self
     */
    public function any($method, $pattern, $handler)
    {
        return $this->addRoute($method, $pattern, $handler);
    }

    /**
     * Add a route to the routing table
     *
     * @param string $method HTTP method
     * @param string $pattern Route pattern
     * @param string $handler Controller@method
     * @return self
     */
    private function addRoute($method, $pattern, $handler)
    {
        // Convert route pattern to regex
        $regex = $this->convertToRegex($pattern);

        $this->routes[$method][$regex] = [
            'handler' => $handler,
            'pattern' => $pattern
        ];

        return $this;
    }

    /**
     * Convert route pattern to regex
     *
     * @param string $pattern Route pattern
     * @return string Regex pattern
     */
    private function convertToRegex($pattern)
    {
        // Check if pattern already uses named capture groups like (?P<name>...)
        if (strpos($pattern, '?P<') !== false) {
            // Pattern already has named groups, just add anchors
            return '#^' . $pattern . '$#';
        }

        // Replace route parameters with regex - handle (name) style
        $regex = preg_replace('/\((\w+)\)/', '(?P<$1>[^/]+)', $pattern);

        // Add start and end anchors
        $regex = '#^' . $regex . '$#';

        return $regex;
    }

    /**
     * Dispatch the request to the appropriate controller
     *
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @param array $queryParams Query parameters
     * @return void
     */
    public function dispatch($method, $uri, $queryParams = [])
    {
        // Clean the URI
        $uri = trim($uri, '/');

        // Check if method is allowed
        if (!in_array($method, $this->allowedMethods)) {
            ApiResponse::methodNotAllowed('HTTP method ' . $method . ' is not allowed');
            return;
        }

        // Try to find matching route
        $result = $this->matchRoute($method, $uri);

        if ($result === false) {
            ApiResponse::notFound('API endpoint not found: /' . $uri);
            return;
        }

        // Extract handler and parameters
        $handler = $result['handler'];
        $this->params = $result['params'];

        // Check if handler is a callable (closure/callback)
        if (is_callable($handler)) {
            try {
                call_user_func_array($handler, [$this->params]);
            } catch (\Throwable $e) {
                ApiResponse::error('Error executing request: ' . $e->getMessage(), 500, 'INTERNAL_ERROR');
            }
            return;
        }

        // Parse handler string (Controller@method)
        list($controllerName, $action) = explode('@', $handler);

        // Add 'Api' suffix to controller name if not present
        if (strpos($controllerName, 'Api') === false) {
            $controllerName = $controllerName;
        }

        // Check if controller file exists
        $controllerFile = __DIR__ . '/../controller/api/' . $controllerName . '.php';

        if (!file_exists($controllerFile)) {
            ApiResponse::notFound('Controller not found: ' . $controllerName);
            return;
        }

        // Include controller file
        require_once $controllerFile;

        // Check if controller class exists
        $fullControllerName = $controllerName;

        if (!class_exists($fullControllerName)) {
            ApiResponse::notFound('Controller class not found: ' . $fullControllerName);
            return;
        }

        // Create controller instance
        $controller = new $fullControllerName();

        // Check if action method exists
        if (!method_exists($controller, $action)) {
            ApiResponse::notFound('Action method not found: ' . $action);
            return;
        }

        // Call the controller action with parameters
        try {
            // Merge query params with route params
            $allParams = array_merge($this->params, $queryParams);

            // Execute the action
            call_user_func_array([$controller, $action], [$allParams]);
        } catch (\Throwable $e) {
            ApiResponse::error('Error executing request: ' . $e->getMessage(), 500, 'INTERNAL_ERROR');
        }
    }

    /**
     * Match the request URI to a route using combined-regex dispatch.
     *
     * Phase 1: Single preg_match per chunk quickly eliminates non-matching
     * URIs. Phase 2: Iterate the chunk's dispatch table to identify the
     * specific route using each entry's original regex. This keeps the
     * fast-fail path (non-matching URI) at O(1) preg_match per chunk,
     * while the identification path uses targeted per-route checks.
     *
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @return array|false Matched route or false
     */
    private function matchRoute($method, $uri)
    {
        if (!isset($this->routes[$method])) {
            return false;
        }

        if ($this->compiled === null) {
            $this->compiled = [];
        }

        if (!isset($this->compiled[$method])) {
            $this->compiled[$method] = $this->compileRoutes($method);
        }

        $chunks = $this->compiled[$method];

        if (empty($chunks)) {
            return false;
        }

        foreach ($chunks as $chunk) {
            if (!preg_match($chunk['regex'], $uri)) {
                continue;
            }

            foreach ($chunk['map'] as $route) {
                if (preg_match($route['regex'], $uri, $routeMatches)) {
                    $params = [];
                    foreach ($routeMatches as $key => $value) {
                        if (is_string($key) && !empty($key)) {
                            $params[$key] = $value;
                        }
                    }

                    return ['handler' => $route['handler'], 'params' => $params];
                }
            }
        }

        return false;
    }

    /**
     * Compile all routes for a given HTTP method into combined regex chunks.
     *
     * Each chunk contains up to CHUNK_SIZE routes combined into a single
     * (?|...) branch-reset regex. A dispatch table maps each branch to its
     * handler, parameter positions, verification prefix, and suffix.
     *
     * @param string $method
     * @return array|null
     */
    private function compileRoutes($method)
    {
        if (!isset($this->routes[$method]) || empty($this->routes[$method])) {
            return null;
        }

        $routeList = $this->routes[$method];
        $chunks = array_chunk($routeList, self::CHUNK_SIZE, true);
        $compiled = [];

        foreach ($chunks as $chunkRoutes) {
            $branches = [];
            $chunkMap = [];

            foreach ($chunkRoutes as $regex => $route) {
                $inner = substr($regex, 2, -2);

                $paramMap = [];
                $numbered = preg_replace_callback(
                    '/\(\?P<(\w+)>([^)]+)\)/',
                    function ($m) use (&$paramMap) {
                        $pos = count($paramMap) + 1;
                        $paramMap[$pos] = $m[1];
                        return '(' . $m[2] . ')';
                    },
                    $inner
                );

                $branches[] = $numbered;

                $chunkMap[] = [
                    'handler' => $route['handler'],
                    'regex' => $regex,
                    'paramMap' => $paramMap,
                ];
            }

            $combined = '~^(?|' . implode('|', $branches) . ')$~ix';

            $compiled[] = [
                'regex' => $combined,
                'map' => $chunkMap,
            ];
        }

        return $compiled;
    }

    /**
     * Get route parameters
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Get all registered routes
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}
