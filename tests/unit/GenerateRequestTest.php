<?php
/**
 * Generate Request Utility Test
 *
 * Tests for generate_request() function that builds HTTP query strings
 * for CRUD functionality in admin pages.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

if (!defined('SCRIPTLOG')) {
    define('SCRIPTLOG', true);
}

// Stub for check_request_generated() - prevents HTTP/CLI conflicts
if (!function_exists('check_request_generated')) {
    function check_request_generated()
    {
        return false;
    }
}

// Stub for escape_html() - avoids laminas-escaper dependency
if (!function_exists('escape_html')) {
    function escape_html($input, $type = 'html', $encoding = 'utf-8', $mode = 'base')
    {
        return $input;
    }
}

// Stub for build_query() - avoids escape_html dependency chain
if (!function_exists('build_query')) {
    function build_query($base, $query_data)
    {
        return basename($base) . '?' . http_build_query($query_data);
    }
}

require_once __DIR__ . '/../../lib/utility/sanitize-urls.php';
require_once __DIR__ . '/../../lib/utility/generate-request.php';

class GenerateRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function testGenerateRequestGetWithLoadOnly(): void
    {
        $result = generate_request('index.php', 'get', ['posts']);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('link', $result);
        $this->assertStringContainsString('load=posts', $result['link']);
    }

    public function testGenerateRequestGetWithLoadAndAction(): void
    {
        $result = generate_request('index.php', 'get', ['posts', 'newPost']);
        $this->assertStringContainsString('load=posts', $result['link']);
        $this->assertStringContainsString('action=newPost', $result['link']);
    }

    public function testGenerateRequestGetWithLoadActionAndId(): void
    {
        $result = generate_request('index.php', 'get', ['posts', 'editPost', '5']);
        $this->assertStringContainsString('load=posts', $result['link']);
        $this->assertStringContainsString('action=editPost', $result['link']);
        $this->assertStringContainsString('Id=5', $result['link']);
    }

    public function testGenerateRequestGetWithAllParams(): void
    {
        $result = generate_request('index.php', 'get', ['users', 'editUser', '3', 'abc123']);
        $this->assertStringContainsString('load=users', $result['link']);
        $this->assertStringContainsString('action=editUser', $result['link']);
        $this->assertStringContainsString('Id=3', $result['link']);
        $this->assertStringContainsString('sessionId=abc123', $result['link']);
    }

    public function testGenerateRequestGetStringEncodedFalse(): void
    {
        $result = generate_request('index.php', 'get', ['posts'], false);
        $this->assertStringContainsString('load=posts', $result['link']);
        $this->assertStringNotContainsString('action=', $result['link']);
    }

    public function testGenerateRequestPostWithLoadAndAction(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $result = generate_request('index.php', 'post', ['posts', 'savePost']);
        $this->assertStringContainsString('load=posts', $result['link']);
        $this->assertStringContainsString('action=savePost', $result['link']);
    }

    public function testGenerateRequestPostWithAllParams(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $result = generate_request('index.php', 'post', ['users', 'updateUser', '7', 'xyz789']);
        $this->assertStringContainsString('load=users', $result['link']);
        $this->assertStringContainsString('action=updateUser', $result['link']);
        $this->assertStringContainsString('Id=7', $result['link']);
        $this->assertStringContainsString('sessionId=xyz789', $result['link']);
    }

    public function testGenerateRequestPostStringEncodedFalse(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $result = generate_request('index.php', 'post', ['posts'], false);
        $this->assertStringContainsString('load=posts', $result['link']);
        $this->assertStringNotContainsString('action=', $result['link']);
    }

    public function testGenerateRequestEmptyDataReturnsNullValues(): void
    {
        $result = generate_request('index.php', 'get', []);
        $this->assertArrayHasKey('link', $result);
        $this->assertStringContainsString('load=', $result['link']);
    }

    public function testGenerateRequestLogoutLoad(): void
    {
        $result = generate_request('index.php', 'get', ['logout', 'logMeOut', '42']);
        $this->assertStringContainsString('load=logout', $result['link']);
        $this->assertStringContainsString('action=logMeOut', $result['link']);
        $this->assertStringContainsString('logOutId=42', $result['link']);
    }

    public function testGenerateRequestSanitizesLoadValue(): void
    {
        $result = generate_request('index.php', 'get', ['admin/<script>']);
        $this->assertStringContainsString('load=admin', $result['link']);
        $this->assertStringNotContainsString('<script>', $result['link']);
    }

    public function testGenerateRequestDefaultsToGetType(): void
    {
        $result = generate_request('index.php', 'invalid_type', ['posts']);
        $this->assertArrayHasKey('link', $result);
        $this->assertStringContainsString('load=posts', $result['link']);
    }

    public function testGenerateRequestNoPhpStanIssetWarning(): void
    {
        // PHPStan reported isset.variable on $data param (always defined).
        // This test verifies calling with empty array produces no warnings/errors.
        $result = generate_request('index.php', 'get', []);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('link', $result);

        $result = generate_request('index.php', 'get', []);
        $this->assertIsArray($result);

        $result = generate_request('index.php', 'get', []);
        $this->assertIsArray($result);
    }
}
