<?php
/**
 * SearchApi Integration Test
 *
 * Database-backed integration tests for the SearchFinder engine against real
 * seeded posts. Verifies result counts, pagination metadata and multi-word
 * queries against tbl_posts' FULLTEXT index.
 *
 * Note: the engine uses MATCH ... AGAINST ... IN BOOLEAN MODE, which matches
 * whole tokens; prefix/partial matches ("sta" for "static") are not supported
 * unless a wildcard is appended, so full-word queries are asserted instead.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class SearchApiIntegrationTest extends ApiTestCase
{
    /**
     * Seed the test database before each test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!ApiDataFixture::isAvailable()) {
            $this->markTestSkipped('Test database unavailable');
        }

        ApiDataFixture::seed();
    }

    /**
     * searchAll() finds published posts and pages but not drafts.
     *
     * @return void
     */
    public function testSearchFindsPublishedPostsAndPagesOnly()
    {
        $finder = new \Scriptlog\Core\SearchFinder();
        $result = $finder->searchAll('api', 1, 10);

        $this->assertSame(3, $result['totalRows']);
        $this->assertCount(3, $result['results']);

        $types = [];
        foreach ($result['results'] as $row) {
            $this->assertSame('publish', $row->post_status);
            $types[] = $row->post_type;
        }
        $this->assertContains('blog', $types);
        $this->assertContains('page', $types);
    }

    /**
     * searchAll() pagination metadata matches the actual result count.
     *
     * @return void
     */
    public function testPaginationMetadataMatchesCounts()
    {
        $finder = new \Scriptlog\Core\SearchFinder();

        $page1 = $finder->searchAll('api', 1, 2);
        $this->assertSame(3, $page1['totalRows']);
        $this->assertSame(2, $page1['perPage']);
        $this->assertCount(2, $page1['results']);
        $this->assertSame(2, $page1['totalPages']);

        $page2 = $finder->searchAll('api', 2, 2);
        $this->assertCount(1, $page2['results']);
        $this->assertSame(2, $page2['totalPages']);
        $this->assertSame(2, $page2['page']);

        $ids1 = array_map(function ($row) {
            return $row->ID;
        }, $page1['results']);
        $ids2 = array_map(function ($row) {
            return $row->ID;
        }, $page2['results']);

        $this->assertEmpty(array_intersect($ids1, $ids2));
    }

    /**
     * Multi-word queries match posts containing every term.
     *
     * @return void
     */
    public function testMultiWordQueryMatchesAllTerms()
    {
        $finder = new \Scriptlog\Core\SearchFinder();
        $result = $finder->searchAll('published post', 1, 10);

        $this->assertSame(2, $result['totalRows']);
        foreach ($result['results'] as $row) {
            $this->assertSame('blog', $row->post_type);
        }
    }

    /**
     * A full-word query resolves to the matching page.
     *
     * @return void
     */
    public function testFullWordQueryMatches()
    {
        $finder = new \Scriptlog\Core\SearchFinder();
        $result = $finder->searchAll('static', 1, 10);

        $this->assertSame(1, $result['totalRows']);
        $this->assertSame('api-static-page', $result['results'][0]->post_slug);
        $this->assertSame('page', $result['results'][0]->post_type);
    }

    /**
     * A keyword with no matches returns an empty, well-formed result.
     *
     * @return void
     */
    public function testNoMatchReturnsEmptyResult()
    {
        $finder = new \Scriptlog\Core\SearchFinder();
        $result = $finder->searchAll('zzzzzz', 1, 10);

        $this->assertSame(0, $result['totalRows']);
        $this->assertSame([], $result['results']);
        $this->assertArrayNotHasKey('error', $result);
    }
}
