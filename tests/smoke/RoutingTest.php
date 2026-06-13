<?php
/**
 * Integration Smoke Tests for URL Routing
 *
 * Verifies that key routes return expected HTTP status codes.
 * These tests make actual HTTP requests to the running application.
 *
 * @category   IntegrationTests
 * @version    1.0.0
 * @since      June 2026
 */

use PHPUnit\Framework\TestCase;

class RoutingTest extends TestCase
{
    private static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        self::$baseUrl = getenv('TEST_BASE_URL') ?: 'http://blogware.site';
    }

    public function testHomePageReturns200(): void
    {
        $status = $this->getHttpStatus('/');
        $this->assertEquals(200, $status, 'Home page should return 200');
    }

    public function testExistingPostReturns200(): void
    {
        // Post ID 1 should exist in a standard installation
        $status = $this->getHttpStatus('/post/1/');
        $this->assertEquals(200, $status, 'Existing post should return 200');
    }

    public function testNonExistentUrlReturns404(): void
    {
        $status = $this->getHttpStatus('/nonexistent-page-12345');
        $this->assertEquals(404, $status, 'Non-existent URL should return 404');
    }

    public function testNonExistentPostReturns404(): void
    {
        $status = $this->getHttpStatus('/post/99999/nonexistent');
        $this->assertEquals(404, $status, 'Non-existent post ID should return 404');
    }

    public function testApiEndpointsReturnJson(): void
    {
        $response = $this->getUrl('/api/v1/posts');
        $this->assertStringStartsWith('200', $http_response_header[0] ?? '');
        $contentType = implode(', ', $http_response_header);
        $this->assertStringContainsString('application/json', $contentType, 'API should return JSON content type');
    }

    public function testApiInfoEndpointReturns200(): void
    {
        $response = @file_get_contents(self::$baseUrl . '/api/v1', false);
        $this->assertNotFalse($response, 'API info endpoint should be reachable');
        $data = json_decode($response, true);
        $this->assertNotNull($data, 'API info should return valid JSON');
    }

    public function testDownloadWithInvalidUuidReturns404(): void
    {
        $status = $this->getHttpStatus('/download/00000000-0000-0000-0000-000000000000');
        $this->assertEquals(404, $status, 'Download with invalid UUID should return 404');
    }

    public function testCategoryUrlReturns200(): void
    {
        $status = $this->getHttpStatus('/category/');
        $this->assertContains($status, [200, 404], 'Category URL should return 200 or 404 depending on content');
    }

    public function testArchivesUrlReturns200(): void
    {
        $status = $this->getHttpStatus('/archives');
        $this->assertContains($status, [200, 404], 'Archives URL should return 200 or 404');
    }

    public function testRssFeedReturns200(): void
    {
        $status = $this->getHttpStatus('/rss.php');
        $this->assertEquals(200, $status, 'RSS feed should return 200');
    }

    public function testAtomFeedReturns200(): void
    {
        $status = $this->getHttpStatus('/atom.php');
        $this->assertEquals(200, $status, 'Atom feed should return 200');
    }

    public function testSearchApiReturnsJson(): void
    {
        $response = @file_get_contents(self::$baseUrl . '/api/v1/search?q=test', false);
        $this->assertNotFalse($response, 'Search API should be reachable');
        $data = json_decode($response, true);
        $this->assertNotNull($data, 'Search API should return valid JSON');
        $this->assertArrayHasKey('data', $data, 'Search response should have data key');
    }

    public function testApiCategoriesReturnsJson(): void
    {
        $response = @file_get_contents(self::$baseUrl . '/api/v1/categories', false);
        $this->assertNotFalse($response, 'Categories API should be reachable');
        $data = json_decode($response, true);
        $this->assertNotNull($data, 'Categories API should return valid JSON');
    }

    public function testApiCommentsReturnsJson(): void
    {
        $response = @file_get_contents(self::$baseUrl . '/api/v1/comments?post_id=1', false);
        $this->assertNotFalse($response, 'Comments API should be reachable');
        $data = json_decode($response, true);
        $this->assertNotNull($data, 'Comments API should return valid JSON');
    }

    /**
     * Get HTTP status code for a URL path
     */
    private function getHttpStatus(string $path): int
    {
        $url = self::$baseUrl . $path;
        $headers = @get_headers($url);
        if ($headers === false) {
            return 0;
        }
        preg_match('/HTTP\/\d+\.\d+\s+(\d+)/', $headers[0], $matches);
        return (int)($matches[1] ?? 0);
    }

    /**
     * Get full URL response with headers
     */
    private function getUrl(string $path)
    {
        $url = self::$baseUrl . $path;
        return @file_get_contents($url);
    }
}
