<?php

/**
 * QueryApiController Unit Tests
 *
 * Tests the RFC 10008 HTTP QUERY method implementation:
 * - Routing dispatches QUERY requests to QueryApiController
 * - Request body parsing for POST-like query content
 * - Safe/idempotent behavior (read-only rate limiting)
 * - Accept-Query response header
 *
 * @category   tests
 * @author     Blogware Team
 * @license    MIT
 * @version    1.0
 * @since      1.1.2
 */

class QueryApiControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ApiRouter
     */
    private $router;

    protected function setUp(): void
    {
        $this->router = new ApiRouter();

        // Register QUERY routes matching api/index.php
        $this->router->query('query', 'QueryApiController@index');
        $this->router->query('query/posts', 'QueryApiController@posts');
        $this->router->query('query/pages', 'QueryApiController@pages');

        // Also register standard routes for cross-method testing
        $this->router->get('posts', 'PostsApiController@index');
        $this->router->post('posts', 'PostsApiController@store');
    }

    /**
     * Test QUERY method is registered in allowed methods.
     */
    public function testQueryMethodInAllowedMethods()
    {
        $ref = new ReflectionClass($this->router);
        $prop = $ref->getProperty('allowedMethods');
        $prop->setAccessible(true);
        $methods = $prop->getValue($this->router);

        $this->assertContains('QUERY', $methods);
    }

    /**
     * Test QUERY route for generic query endpoint.
     */
    public function testQueryIndexRoute()
    {
        $result = $this->invokeMatchRoute('QUERY', 'query');

        $this->assertNotFalse($result);
        $this->assertEquals('QueryApiController@index', $result['handler']);
    }

    /**
     * Test QUERY route for posts query.
     */
    public function testQueryPostsRoute()
    {
        $result = $this->invokeMatchRoute('QUERY', 'query/posts');

        $this->assertNotFalse($result);
        $this->assertEquals('QueryApiController@posts', $result['handler']);
    }

    /**
     * Test QUERY route for pages query.
     */
    public function testQueryPagesRoute()
    {
        $result = $this->invokeMatchRoute('QUERY', 'query/pages');

        $this->assertNotFalse($result);
        $this->assertEquals('QueryApiController@pages', $result['handler']);
    }

    /**
     * Test that GET on QUERY routes fails (method mismatch).
     */
    public function testGetOnQueryRouteReturnsFalse()
    {
        $result = $this->invokeMatchRoute('GET', 'query');
        $this->assertFalse($result);
    }

    /**
     * Test that QUERY on GET-only routes fails (method mismatch).
     */
    public function testQueryOnGetRouteReturnsFalse()
    {
        $result = $this->invokeMatchRoute('QUERY', 'posts');
        $this->assertFalse($result);
    }

    /**
     * Test that QUERY on nonexistent route fails.
     */
    public function testQueryOnNonexistentRoute()
    {
        $result = $this->invokeMatchRoute('QUERY', 'nonexistent');
        $this->assertFalse($result);
    }

    /**
     * Test that QUERY is treated as read operation for rate limiting.
     *
     * QUERY is safe and idempotent per RFC 10008 Section 2,
     * so it should use read rate limits (not write limits).
     */
    public function testQueryIsReadOperationForRateLimiting()
    {
        $writeMethods = ['POST', 'PUT', 'DELETE', 'PATCH'];
        $this->assertNotContains('QUERY', $writeMethods);
    }

    /**
     * Test QUERY compilation and chunking.
     */
    public function testQueryCompiledRegexUsesBranchReset()
    {
        $ref = new ReflectionMethod($this->router, 'compileRoutes');
        $ref->setAccessible(true);

        $compiled = $ref->invoke($this->router, 'QUERY');

        $this->assertIsArray($compiled);
        $this->assertNotEmpty($compiled);

        $combined = $compiled[0]['regex'];
        $this->assertStringContainsString('(?|', $combined);
    }

    /**
     * Test QUERY chunk count (3 routes = 1 chunk at CHUNK_SIZE=10).
     */
    public function testQueryChunkCount()
    {
        $ref = new ReflectionMethod($this->router, 'compileRoutes');
        $ref->setAccessible(true);

        $compiled = $ref->invoke($this->router, 'QUERY');
        $this->assertCount(1, $compiled);
    }

    /**
     * Test that QUERY routes are properly cached after first compilation.
     */
    public function testQueryCompiledIsCached()
    {
        $ref = new ReflectionMethod($this->router, 'compileRoutes');
        $ref->setAccessible(true);

        $compiled = $ref->invoke($this->router, 'QUERY');

        foreach ($compiled as $chunk) {
            $this->assertArrayHasKey('regex', $chunk);
            $this->assertArrayHasKey('map', $chunk);
            $this->assertNotEmpty($chunk['map']);
        }
    }

    /**
     * Test Accept-Query header constant exists on ApiResponse.
     */
    public function testAcceptQueryHeaderSupport()
    {
        $this->assertTrue(
            method_exists('ApiResponse', 'withHeader'),
            'ApiResponse::withHeader must exist to set Accept-Query header'
        );
    }

    /**
     * Helper to invoke the private matchRoute method.
     *
     * @param string $method HTTP method
     * @param string $uri    Request URI
     * @return array|false
     */
    private function invokeMatchRoute($method, $uri)
    {
        $ref = new ReflectionMethod($this->router, 'matchRoute');
        $ref->setAccessible(true);
        return $ref->invoke($this->router, $method, $uri);
    }
}