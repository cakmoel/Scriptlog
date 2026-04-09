<?php

/**
 * ApiHateoas Unit Tests
 *
 * Tests for the HATEOAS link generator covering:
 * - Pagination links
 * - Resource links (posts, categories, comments, archives)
 * - Root API links
 * - URL construction
 */
class ApiHateoasTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ApiHateoas
     */
    private $hateoas;

    /**
     * @var string
     */
    private $appUrl;

    protected function setUp(): void
    {
        $_SERVER['SERVER_NAME'] = 'blogware.site';
        $_SERVER['REQUEST_URI'] = '/api/v1/posts';

        // Read the actual app URL from config
        $configPath = __DIR__ . '/../../config.php';
        if (file_exists($configPath)) {
            $config = require $configPath;
            $this->appUrl = rtrim($config['app']['url'] ?? 'http://blogware.site', '/');
        } else {
            // When config.php doesn't exist (e.g., during testing), use localhost
            $this->appUrl = 'http://localhost';
        }

        $this->hateoas = new ApiHateoas($this->appUrl . '/api/v1');
    }

    // ========================================
    // Pagination Links Tests
    // ========================================

    public function testPaginationLinksSinglePage()
    {
        $links = $this->hateoas->paginationLinks('posts', 1, 10, 5);

        $this->assertArrayHasKey('self', $links);
        $this->assertStringContainsString('page=1', $links['self']['href']);
        $this->assertStringContainsString('per_page=10', $links['self']['href']);
        $this->assertEquals('self', $links['self']['rel']);
        $this->assertEquals('GET', $links['self']['type']);

        // Single page should not have prev/next
        $this->assertArrayNotHasKey('prev', $links);
        $this->assertArrayNotHasKey('next', $links);
        $this->assertArrayNotHasKey('first', $links);
        $this->assertArrayNotHasKey('last', $links);
    }

    public function testPaginationLinksFirstPage()
    {
        $links = $this->hateoas->paginationLinks('posts', 1, 10, 50);

        $this->assertArrayHasKey('self', $links);
        $this->assertArrayHasKey('next', $links);
        $this->assertArrayHasKey('last', $links);
        $this->assertStringContainsString('page=2', $links['next']['href']);
        $this->assertStringContainsString('page=5', $links['last']['href']);

        // First page should not have prev/first
        $this->assertArrayNotHasKey('prev', $links);
        $this->assertArrayNotHasKey('first', $links);
    }

    public function testPaginationLinksMiddlePage()
    {
        $links = $this->hateoas->paginationLinks('posts', 3, 10, 50);

        $this->assertArrayHasKey('self', $links);
        $this->assertArrayHasKey('first', $links);
        $this->assertArrayHasKey('prev', $links);
        $this->assertArrayHasKey('next', $links);
        $this->assertArrayHasKey('last', $links);

        $this->assertStringContainsString('page=1', $links['first']['href']);
        $this->assertStringContainsString('page=2', $links['prev']['href']);
        $this->assertStringContainsString('page=4', $links['next']['href']);
        $this->assertStringContainsString('page=5', $links['last']['href']);
    }

    public function testPaginationLinksLastPage()
    {
        $links = $this->hateoas->paginationLinks('posts', 5, 10, 50);

        $this->assertArrayHasKey('self', $links);
        $this->assertArrayHasKey('first', $links);
        $this->assertArrayHasKey('prev', $links);

        // Last page should not have next/last
        $this->assertArrayNotHasKey('next', $links);
        $this->assertArrayNotHasKey('last', $links);
    }

    public function testPaginationLinksWithExtraParams()
    {
        $links = $this->hateoas->paginationLinks('posts', 1, 10, 50, ['sort_by' => 'post_date']);

        $this->assertStringContainsString('sort_by=post_date', $links['self']['href']);
        $this->assertStringContainsString('sort_by=post_date', $links['next']['href']);
    }

    public function testPaginationLinksZeroItems()
    {
        $links = $this->hateoas->paginationLinks('posts', 1, 10, 0);

        $this->assertArrayHasKey('self', $links);
        $this->assertArrayNotHasKey('first', $links);
        $this->assertArrayNotHasKey('prev', $links);
        $this->assertArrayNotHasKey('next', $links);
        $this->assertArrayNotHasKey('last', $links);
    }

    // ========================================
    // Post Links Tests
    // ========================================

    public function testPostLinksWithSlug()
    {
        $links = $this->hateoas->postLinks(42, 'my-first-post');

        $this->assertArrayHasKey('self', $links);
        $this->assertEquals($this->appUrl . '/api/v1/posts/42', $links['self']['href']);
        $this->assertEquals('self', $links['self']['rel']);

        $this->assertArrayHasKey('comments', $links);
        $this->assertEquals($this->appUrl . '/api/v1/posts/42/comments', $links['comments']['href']);

        $this->assertArrayHasKey('canonical', $links);
        $this->assertEquals($this->appUrl . '/post/42/my-first-post', $links['canonical']['href']);

        $this->assertArrayHasKey('collection', $links);
        $this->assertEquals($this->appUrl . '/api/v1/posts', $links['collection']['href']);
    }

    public function testPostLinksWithoutSlug()
    {
        $links = $this->hateoas->postLinks(42);

        $this->assertArrayHasKey('self', $links);
        $this->assertArrayHasKey('comments', $links);
        $this->assertArrayHasKey('collection', $links);
        $this->assertArrayNotHasKey('canonical', $links);
    }

    // ========================================
    // Category Links Tests
    // ========================================

    public function testCategoryLinksWithSlug()
    {
        $links = $this->hateoas->categoryLinks(5, 'technology');

        $this->assertArrayHasKey('self', $links);
        $this->assertEquals($this->appUrl . '/api/v1/categories/5', $links['self']['href']);

        $this->assertArrayHasKey('posts', $links);
        $this->assertEquals($this->appUrl . '/api/v1/categories/5/posts', $links['posts']['href']);

        $this->assertArrayHasKey('canonical', $links);
        $this->assertEquals($this->appUrl . '/category/technology', $links['canonical']['href']);

        $this->assertArrayHasKey('collection', $links);
        $this->assertEquals($this->appUrl . '/api/v1/categories', $links['collection']['href']);
    }

    public function testCategoryLinksWithoutSlug()
    {
        $links = $this->hateoas->categoryLinks(5);

        $this->assertArrayHasKey('self', $links);
        $this->assertArrayHasKey('posts', $links);
        $this->assertArrayHasKey('collection', $links);
        $this->assertArrayNotHasKey('canonical', $links);
    }

    // ========================================
    // Comment Links Tests
    // ========================================

    public function testCommentLinksWithPostId()
    {
        $links = $this->hateoas->commentLinks(10, 42);

        $this->assertArrayHasKey('self', $links);
        $this->assertEquals($this->appUrl . '/api/v1/comments/10', $links['self']['href']);

        $this->assertArrayHasKey('post', $links);
        $this->assertEquals($this->appUrl . '/api/v1/posts/42', $links['post']['href']);

        $this->assertArrayHasKey('collection', $links);
        $this->assertEquals($this->appUrl . '/api/v1/comments', $links['collection']['href']);
    }

    public function testCommentLinksWithoutPostId()
    {
        $links = $this->hateoas->commentLinks(10, 0);

        $this->assertArrayHasKey('self', $links);
        $this->assertArrayHasKey('collection', $links);
        $this->assertArrayNotHasKey('post', $links);
    }

    // ========================================
    // Archive Links Tests
    // ========================================

    public function testArchiveLinksWithMonth()
    {
        $links = $this->hateoas->archiveLinks(2024, 6);

        $this->assertArrayHasKey('self', $links);
        $this->assertEquals($this->appUrl . '/api/v1/archives/2024/6', $links['self']['href']);

        $this->assertArrayHasKey('year', $links);
        $this->assertEquals($this->appUrl . '/api/v1/archives/2024', $links['year']['href']);

        $this->assertArrayHasKey('collection', $links);
    }

    public function testArchiveLinksYearOnly()
    {
        $links = $this->hateoas->archiveLinks(2024);

        $this->assertArrayHasKey('self', $links);
        $this->assertEquals($this->appUrl . '/api/v1/archives/2024', $links['self']['href']);

        $this->assertArrayHasKey('collection', $links);
        $this->assertArrayNotHasKey('year', $links);
    }

    // ========================================
    // Root Links Tests
    // ========================================

    public function testRootLinks()
    {
        $links = $this->hateoas->rootLinks();

        $this->assertArrayHasKey('self', $links);
        $this->assertEquals($this->appUrl . '/api/v1', $links['self']['href']);

        $this->assertArrayHasKey('posts', $links);
        $this->assertEquals($this->appUrl . '/api/v1/posts', $links['posts']['href']);

        $this->assertArrayHasKey('categories', $links);
        $this->assertEquals($this->appUrl . '/api/v1/categories', $links['categories']['href']);

        $this->assertArrayHasKey('comments', $links);
        $this->assertEquals($this->appUrl . '/api/v1/comments', $links['comments']['href']);

        $this->assertArrayHasKey('archives', $links);
        $this->assertEquals($this->appUrl . '/api/v1/archives', $links['archives']['href']);

        $this->assertArrayHasKey('search', $links);
        $this->assertEquals($this->appUrl . '/api/v1/search?q={query}', $links['search']['href']);
        $this->assertTrue($links['search']['templated']);

        $this->assertArrayHasKey('openapi', $links);
        $this->assertEquals($this->appUrl . '/api/v1/openapi.json', $links['openapi']['href']);
        $this->assertEquals('service-desc', $links['openapi']['rel']);
    }

    // ========================================
    // Link Structure Tests
    // ========================================

    public function testAllLinksHaveRequiredFields()
    {
        $links = $this->hateoas->rootLinks();

        foreach ($links as $rel => $link) {
            $this->assertArrayHasKey('href', $link, "Link '$rel' missing 'href'");
            $this->assertArrayHasKey('rel', $link, "Link '$rel' missing 'rel'");
            $this->assertArrayHasKey('type', $link, "Link '$rel' missing 'type'");
        }
    }

    public function testPaginationLinksHaveRequiredFields()
    {
        $links = $this->hateoas->paginationLinks('posts', 2, 10, 50);

        foreach ($links as $rel => $link) {
            $this->assertArrayHasKey('href', $link, "Link '$rel' missing 'href'");
            $this->assertArrayHasKey('rel', $link, "Link '$rel' missing 'rel'");
            $this->assertArrayHasKey('type', $link, "Link '$rel' missing 'type'");
        }
    }
}
