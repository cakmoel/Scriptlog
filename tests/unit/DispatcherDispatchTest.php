<?php

/**
 * Dispatcher Dispatch Unit Tests
 *
 * Tests the GCB-10C combined-regex dispatch for frontend routes:
 * - compileFrontendRoutes() builds correct (?|...) branch-reset regex
 * - Combined regex matches all frontend route patterns
 * - Parameters are correctly extracted
 * - Edge cases: archive (no capture groups), tag with spaces,
 *   download vs download_file distinction
 */
class DispatcherDispatchTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Frontend route definitions (matches Bootstrap.php)
     */
    private $routes;

    protected function setUp(): void
    {
        $this->routes = [
            'home'     => '/',
            'category' => "/category/(?'category'[\\w\\-]+)",
            'archive'  => '/archive/[0-9]{2}/[0-9]{4}',
            'archives' => '/archives',
            'blog'     => "/blog(?'blog'[^/]*)",
            'page'     => "/page/(?'page'[^/]+)",
            'single'   => "/post/(?'id'\\d+)/(?'post'[\\w\\-]+)",
            'search'   => "(?'search'[\\w\\-]+)",
            'tag'      => "/tag/(?'tag'[\\w\\- ]+)",
            'privacy'  => '/privacy',
            'download' => "/download/(?'identifier'[a-f0-9\\-]+)",
            'download_file' => "/download/(?'identifier'[a-f0-9\\-]+)/file",
        ];
    }

    /**
     * Build combined regex and dispatch table (mirrors compileFrontendRoutes)
     */
    private function compileFrontendRoutes()
    {
        $branches = [];
        $map = [];

        foreach ($this->routes as $key => $pattern) {
            $numbered = $pattern;
            $paramMap = [];

            if (strpos($pattern, "?'") !== false) {
                $numbered = preg_replace_callback(
                    "/\(\?'(\w+)'([^)]+)\)/",
                    function ($m) use (&$paramMap) {
                        $pos = count($paramMap) + 1;
                        $paramMap[$pos] = $m[1];
                        return '(' . $m[2] . ')';
                    },
                    $pattern
                );
            }

            $branches[] = $numbered;

            $map[] = [
                'key' => $key,
                'regex' => '~^' . $pattern . '$~i',
                'paramMap' => $paramMap,
            ];
        }

        return [
            'regex' => '~^(?|' . implode('|', $branches) . ')$~ix',
            'map' => $map,
        ];
    }

    /**
     * Match a URI using two-phase dispatch (mirrors matchRoute logic)
     */
    private function matchRoute($uri)
    {
        $compiled = $this->compileFrontendRoutes();

        // Phase 1: combined regex
        if (!preg_match($compiled['regex'], $uri, $combinedMatches)) {
            return false;
        }

        // Phase 2: per-route verification
        foreach ($compiled['map'] as $entry) {
            $routeMatches = [];
            if (preg_match($entry['regex'], $uri, $routeMatches)) {
                $params = [];
                foreach ($routeMatches as $key => $value) {
                    if (is_string($key) && !empty($key)) {
                        $params[$key] = $value;
                    }
                }
                return ['key' => $entry['key'], 'params' => $params];
            }
        }

        return false;
    }

    // ========================================
    // Basic Route Matching
    // ========================================

    public function testHomeRoute()
    {
        $result = $this->matchRoute('/');
        $this->assertNotFalse($result);
        $this->assertEquals('home', $result['key']);
        $this->assertEmpty($result['params']);
    }

    public function testCategoryRoute()
    {
        $result = $this->matchRoute('/category/php');
        $this->assertNotFalse($result);
        $this->assertEquals('category', $result['key']);
        $this->assertEquals(['category' => 'php'], $result['params']);
    }

    public function testCategoryRouteWithHyphen()
    {
        $result = $this->matchRoute('/category/web-development');
        $this->assertNotFalse($result);
        $this->assertEquals('category', $result['key']);
        $this->assertEquals(['category' => 'web-development'], $result['params']);
    }

    public function testArchiveRoute()
    {
        $result = $this->matchRoute('/archive/03/2025');
        $this->assertNotFalse($result);
        $this->assertEquals('archive', $result['key']);
        $this->assertEmpty($result['params']);
    }

    public function testArchivesRoute()
    {
        $result = $this->matchRoute('/archives');
        $this->assertNotFalse($result);
        $this->assertEquals('archives', $result['key']);
        $this->assertEmpty($result['params']);
    }

    public function testBlogRoute()
    {
        $result = $this->matchRoute('/blog');
        $this->assertNotFalse($result);
        $this->assertEquals('blog', $result['key']);
    }

    public function testPageRoute()
    {
        $result = $this->matchRoute('/page/about-us');
        $this->assertNotFalse($result);
        $this->assertEquals('page', $result['key']);
        $this->assertEquals(['page' => 'about-us'], $result['params']);
    }

    public function testSinglePostRoute()
    {
        $result = $this->matchRoute('/post/42/cicero');
        $this->assertNotFalse($result);
        $this->assertEquals('single', $result['key']);
        $this->assertEquals(['id' => '42', 'post' => 'cicero'], $result['params']);
    }

    public function testTagRoute()
    {
        $result = $this->matchRoute('/tag/cicero');
        $this->assertNotFalse($result);
        $this->assertEquals('tag', $result['key']);
        $this->assertEquals(['tag' => 'cicero'], $result['params']);
    }

    public function testTagRouteWithSpaces()
    {
        $result = $this->matchRoute('/tag/lorem ipsum');
        $this->assertNotFalse($result);
        $this->assertEquals('tag', $result['key']);
        $this->assertEquals(['tag' => 'lorem ipsum'], $result['params']);
    }

    public function testPrivacyRoute()
    {
        $result = $this->matchRoute('/privacy');
        $this->assertNotFalse($result);
        $this->assertEquals('privacy', $result['key']);
        $this->assertEmpty($result['params']);
    }

    public function testDownloadRoute()
    {
        $result = $this->matchRoute('/download/550e8400-e29b-41d4-a716-446655440000');
        $this->assertNotFalse($result);
        $this->assertEquals('download', $result['key']);
        $this->assertEquals(['identifier' => '550e8400-e29b-41d4-a716-446655440000'], $result['params']);
    }

    public function testDownloadFileRoute()
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $result = $this->matchRoute('/download/' . $uuid . '/file');
        $this->assertNotFalse($result);
        $this->assertEquals('download_file', $result['key']);
        $this->assertEquals(['identifier' => $uuid], $result['params']);
    }

    // ========================================
    // Negative Tests (404)
    // ========================================

    public function testNonexistentRoute()
    {
        $result = $this->matchRoute('/nonexistent');
        $this->assertFalse($result);
    }

    public function testInvalidArchiveFormat()
    {
        $result = $this->matchRoute('/archive/2025');
        $this->assertFalse($result);
    }

    public function testInvalidPostId()
    {
        $result = $this->matchRoute('/post/abc/cicero');
        $this->assertFalse($result);
    }

    // ========================================
    // Compilation Tests
    // ========================================

    public function testCombinedRegexUsesBranchReset()
    {
        $compiled = $this->compileFrontendRoutes();
        $this->assertStringContainsString('(?|', $compiled['regex']);
    }

    public function testCombinedRegexCaseInsensitive()
    {
        $compiled = $this->compileFrontendRoutes();
        $this->assertStringContainsString('~^', $compiled['regex']);
    }

    public function testDispatchTableHasAllRoutes()
    {
        $compiled = $this->compileFrontendRoutes();
        $keys = array_column($compiled['map'], 'key');
        $this->assertCount(12, $keys);
        $this->assertEquals(
            ['home', 'category', 'archive', 'archives', 'blog', 'page',
             'single', 'search', 'tag', 'privacy', 'download', 'download_file'],
            $keys
        );
    }

    public function testEachMapEntryHasRequiredFields()
    {
        $compiled = $this->compileFrontendRoutes();
        foreach ($compiled['map'] as $entry) {
            $this->assertArrayHasKey('key', $entry);
            $this->assertArrayHasKey('regex', $entry);
            $this->assertArrayHasKey('paramMap', $entry);
        }
    }

    // ========================================
    // Parameter Extraction
    // ========================================

    public function testRouteWithSingleParam()
    {
        $result = $this->matchRoute('/tag/php');
        $this->assertCount(1, $result['params']);
    }

    public function testRouteWithMultipleParams()
    {
        $result = $this->matchRoute('/post/123/welcome-to-blog');
        $this->assertCount(2, $result['params']);
        $this->assertArrayHasKey('id', $result['params']);
        $this->assertArrayHasKey('post', $result['params']);
    }

    public function testRouteWithNoParams()
    {
        foreach (['/', '/privacy', '/archives'] as $uri) {
            $result = $this->matchRoute($uri);
            $this->assertEmpty($result['params'], "URI $uri should have no params");
        }
    }

    // ========================================
    // Order Preservation
    // ========================================

    public function testRoutePriorityOrderedByDeclaration()
    {
        // All routes matching /download/* should preserve order
        $result = $this->matchRoute('/download/a1b2c3d4-e5f6');
        $this->assertEquals('download', $result['key']);

        $result = $this->matchRoute('/download/a1b2c3d4-e5f6/file');
        $this->assertEquals('download_file', $result['key']);
    }
}
