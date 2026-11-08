<?php
/**
 * API front-controller hand-off utility tests.
 *
 * Covers lib/utility/api-request.php: the helper that lets a front controller
 * hand /api/* to api/index.php when the web server has no explicit API rewrite
 * (DDEV/nginx, PHP's built-in server, default LiteSpeed).
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

if (!defined('SCRIPTLOG')) {
    define('SCRIPTLOG', true);
}

require_once __DIR__ . '/../../lib/utility/api-request.php';

class ApiFrontControllerHandoffTest extends TestCase
{
    public function testMatchesApiRootAndNestedPaths(): void
    {
        $this->assertTrue(scriptlog_is_api_request('/api', '/index.php'));
        $this->assertTrue(scriptlog_is_api_request('/api/', '/index.php'));
        $this->assertTrue(scriptlog_is_api_request('/api/v1', '/index.php'));
        $this->assertTrue(scriptlog_is_api_request('/api/v1/posts/1', '/index.php'));
    }

    public function testIgnoresQueryStringWhenMatching(): void
    {
        $this->assertTrue(scriptlog_is_api_request('/api/v1/search?q=cicero', '/index.php'));
        $this->assertTrue(scriptlog_is_api_request('/api/v1?page=2', '/index.php'));
    }

    public function testRejectsLookalikePrefixes(): void
    {
        $this->assertFalse(scriptlog_is_api_request('/apiary', '/index.php'));
        $this->assertFalse(scriptlog_is_api_request('/api-docs', '/index.php'));
        $this->assertFalse(scriptlog_is_api_request('/posts/api/v1', '/index.php'));
        $this->assertFalse(scriptlog_is_api_request('/', '/index.php'));
    }

    public function testStripsInstallBaseFromScriptName(): void
    {
        $this->assertTrue(scriptlog_is_api_request('/scriptlog/api/v1', '/scriptlog/index.php'));
        $this->assertFalse(scriptlog_is_api_request('/scriptlog/apiary', '/scriptlog/index.php'));
        $this->assertFalse(scriptlog_is_api_request('/scriptlog/post/1/slug', '/scriptlog/index.php'));
    }

    public function testRequestPathIsRelativeToFrontController(): void
    {
        $this->assertSame(
            '/api/v1',
            scriptlog_request_path_relative_to_front_controller('/api/v1?q=x', '/index.php')
        );

        $this->assertSame(
            '/api/v1',
            scriptlog_request_path_relative_to_front_controller('/scriptlog/api/v1', '/scriptlog/index.php')
        );

        $this->assertSame(
            '/',
            scriptlog_request_path_relative_to_front_controller('', '/index.php')
        );
    }

    public function testInstallBasePathHandlesRootAndSubdirectoryInstalls(): void
    {
        $this->assertSame('', scriptlog_install_base_path('', '/var/www/html'));
        $this->assertSame('', scriptlog_install_base_path('/var/www/html', '/var/www/html'));

        $documentRoot = dirname(APP_ROOT);
        $expected = '/' . basename(APP_ROOT);

        $this->assertSame($expected, scriptlog_install_base_path($documentRoot, APP_ROOT));
    }

    public function testDefaultsReadTheServerSuperglobals(): void
    {
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : null;

        $_SERVER['REQUEST_URI'] = '/api/v1/search?q=cicero';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        try {
            $this->assertTrue(scriptlog_is_api_request());
        } finally {
            if ($requestUri === null) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $requestUri;
            }

            if ($scriptName === null) {
                unset($_SERVER['SCRIPT_NAME']);
            } else {
                $_SERVER['SCRIPT_NAME'] = $scriptName;
            }
        }
    }
}
