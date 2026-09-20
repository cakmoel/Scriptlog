<?php
/**
 * API Test Case
 *
 * Abstract base class for the Phase 4.7 API test suite (tests/api/).
 *
 * Controllers terminate the process via ApiResponse::send() (exit), so
 * every controller action is executed inside an isolated PHP subprocess
 * (see runner.php). This base class provides:
 *
 *   - runController()/runRouter(): execute a controller action or router
 *     dispatch in a subprocess and return the decoded result.
 *   - Assertion helpers for the standard API JSON envelope (success/status/
 *     data), _links, and pagination metadata.
 *   - runInSubprocess(): low-level subprocess execution and result parsing.
 *
 * @package Scriptlog\Tests
 */

abstract class ApiTestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Copy of $_SERVER captured before the test ran.
     *
     * @var array
     */
    private $serverBackup = [];

    /**
     * @var string Marker that prefixes the runner result payload.
     */
    private const RUNNER_MARKER = 'API_RESULT_START';

    /**
     * @var resource|null PHP built-in web server process.
     */
    private static $httpServer = null;

    /**
     * @var string|null Base URL of the local test web server.
     */
    private static $httpBaseUrl = null;

    /**
     * Capture $_SERVER before each test so it can be restored afterwards.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
    }

    /**
     * Restore $_SERVER after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
    }

    /**
     * Execute a controller action in an isolated subprocess.
     *
     * @param string $controller Fully qualified controller class name
     * @param string $action Action method to invoke
     * @param array $params Parameters passed to the action (route + query)
     * @param array $options Extra runner options (server, get, post, files,
     *                       api_key, session_user, session)
     * @return array Decoded runner result {code, headers, body, rate}
     */
    protected function runController($controller, $action, array $params = [], array $options = [])
    {
        $env = array_merge([
            'controller' => $controller,
            'action' => $action,
            'params' => $params,
        ], $options);

        return $this->runInSubprocess($env);
    }

    /**
     * Dispatch a request through a fresh ApiRouter in an isolated subprocess.
     *
     * @param array $routes Route registrations [{method, pattern, handler}]
     * @param string $method HTTP method to dispatch
     * @param string $uri Request URI relative to the API base path
     * @param array $query Query parameters
     * @param array $options Extra runner options (server, get, post, ...)
     * @return array Decoded runner result {code, headers, body, rate}
     */
    protected function runRouter(array $routes, $method, $uri, array $query = [], array $options = [])
    {
        $env = array_merge([
            'dispatch' => [
                'method' => $method,
                'uri' => $uri,
                'query' => $query,
            ],
            'routes' => $routes,
        ], $options);

        return $this->runInSubprocess($env);
    }

    /**
     * Write an environment file and execute the runner in a subprocess.
     *
     * @param array $env Runner environment payload
     * @return array Decoded runner result {code, headers, body, rate}
     */
    protected function runInSubprocess(array $env)
    {
        $envFile = tempnam(sys_get_temp_dir(), 'api-env');
        if ($envFile === false) {
            $this->fail('Unable to create temporary environment file');
        }

        file_put_contents($envFile, json_encode($env));

        $command = escapeshellarg(PHP_BINARY)
            . ' ' . escapeshellarg(__DIR__ . '/runner.php')
            . ' ' . escapeshellarg($envFile)
            . ' 2>&1';

        $output = (string)shell_exec($command);

        @unlink($envFile);

        $markerPos = strrpos($output, self::RUNNER_MARKER);
        if ($markerPos === false) {
            $this->fail('Runner produced no result. Output: ' . $output);
        }

        $payloadLine = trim(substr($output, $markerPos + strlen(self::RUNNER_MARKER)));
        $result = json_decode($payloadLine, true);

        if (!is_array($result)) {
            $this->fail('Runner produced an invalid result payload: ' . $payloadLine);
        }

        $result['raw_output'] = $output;

        return $result;
    }

    /**
     * Start the PHP built-in web server once for header-level assertions.
     *
     * Headers cannot be observed via headers_list() in CLI, so tests that
     * assert real HTTP behaviour (OPTIONS Allow header, health endpoint)
     * run against a local php -S server. The process is started lazily on
     * first use and terminated via a shutdown function.
     *
     * @return void
     */
    private function startHttpServer()
    {
        if (self::$httpBaseUrl !== null) {
            return;
        }

        $root = dirname(__DIR__, 3);
        $router = __DIR__ . '/router.php';
        $port = random_int(20000, 21000);
        $command = sprintf(
            '%s -S 127.0.0.1:%d -t %s %s',
            escapeshellarg(PHP_BINARY),
            $port,
            escapeshellarg($root),
            escapeshellarg($router)
        );

        $pipes = [];
        $proc = proc_open(
            $command,
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        if (!is_resource($proc)) {
            $this->fail('Unable to start the PHP built-in web server');
        }

        usleep(300000);

        self::$httpServer = $proc;
        self::$httpBaseUrl = 'http://127.0.0.1:' . $port;

        register_shutdown_function([__CLASS__, 'stopHttpServer']);
    }

    /**
     * Terminate the PHP built-in web server (shutdown hook).
     *
     * @return void
     */
    public static function stopHttpServer()
    {
        if (self::$httpServer !== null) {
            proc_terminate(self::$httpServer);
            proc_close(self::$httpServer);
            self::$httpServer = null;
        }
    }

    /**
     * Perform an HTTP request against the local test web server.
     *
     * @param string $method HTTP method (GET, POST, OPTIONS, ...)
     * @param string $path API path relative to /api/v1 (e.g. "/posts")
     * @param array $headers Request headers as name => value pairs
     * @return array{code: int, headers: array, body: string}
     */
    protected function httpRequest($method, $path, array $headers = [])
    {
        $this->startHttpServer();

        $url = self::$httpBaseUrl . '/api/v1' . $path;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, false);

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }
        if (!empty($headerLines)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            $this->fail('HTTP request failed: ' . $error);
        }

        $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

        $headerBlock = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $parsedHeaders = [];
        foreach (explode("\r\n", $headerBlock) as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            list($name, $value) = explode(':', $line, 2);
            $parsedHeaders[strtolower(trim($name))] = trim($value);
        }

        return ['code' => $statusCode, 'headers' => $parsedHeaders, 'body' => $body];
    }

    /**
     * Assert the standard API JSON envelope and HTTP status code.
     *
     * Parses the JSON body from a runner result and asserts the success flag
     * and HTTP status. Returns the decoded body for further assertions.
     *
     * @param array $result Runner result from runController()/runRouter()
     * @param int $status Expected HTTP status code
     * @param bool $success Expected success flag
     * @return array Decoded JSON body
     */
    protected function assertApiResponse(array $result, $status = 200, $success = true)
    {
        $this->assertSame(
            $status,
            (int)$result['code'],
            'HTTP status mismatch. Raw output: ' . ($result['raw_output'] ?? '')
        );

        $body = json_decode($result['body'] ?? 'null', true);
        $this->assertIsArray($body, 'Response body is not a JSON object: ' . ($result['body'] ?? ''));
        $this->assertSame($success, $body['success'] ?? null, 'Unexpected success flag in body: ' . ($result['body'] ?? ''));

        return $body;
    }

    /**
     * Assert that a decoded API body contains a _links object.
     *
     * @param array $body Decoded JSON body
     * @return void
     */
    protected function assertHasLinks(array $body)
    {
        $this->assertArrayHasKey('_links', $body);
        $this->assertIsArray($body['_links']);
    }

    /**
     * Assert that a decoded API body contains pagination metadata.
     *
     * @param array $body Decoded JSON body
     * @return void
     */
    protected function assertHasPagination(array $body)
    {
        $this->assertArrayHasKey('pagination', $body);
        $this->assertIsArray($body['pagination']);
        $this->assertArrayHasKey('current_page', $body['pagination']);
        $this->assertArrayHasKey('per_page', $body['pagination']);
        $this->assertArrayHasKey('total_items', $body['pagination']);
        $this->assertArrayHasKey('total_pages', $body['pagination']);
    }
}
