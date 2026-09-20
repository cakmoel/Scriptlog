<?php
/**
 * SearchApiController Test (named ApiSearchControllerTest)
 *
 * Unit tests for Scriptlog\Controller\Api\SearchApiController driven through
 * the subprocess runner (ApiTestCase::runController). Controller actions
 * terminate the process via ApiResponse::send(), so each action runs in an
 * isolated PHP process against the seeded test database.
 *
 * Auth: the endpoint is public (requiresAuth=false at construction). Input is
 * read from the $_GET superglobal, so query parameters are passed via the
 * runner's "get" option rather than the action params.
 *
 * Naming note: the class is ApiSearchControllerTest (not
 * SearchApiControllerTest) because tests/unit/SearchApiControllerTest.php
 * already declares a global SearchApiControllerTest class; the recursive
 * "Scriptlog Test Suite" in phpunit.xml would otherwise load two classes with
 * the same name and fatal.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class ApiSearchControllerTest extends ApiTestCase
{
    /**
     * @var string Fully-qualified controller class under test.
     */
    private const CONTROLLER = \Scriptlog\Controller\Api\SearchApiController::class;

    /**
     * Seed a known dataset and skip when the test database is unavailable.
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
     * index() with q returns a non-empty result set.
     *
     * The fixture seeds posts whose titles contain "api"; the draft post is
     * excluded, so two blog posts and one page are expected.
     *
     * @return void
     */
    public function testIndexWithKeywordReturnsResults()
    {
        $result = $this->runController(self::CONTROLLER, 'index', [], ['get' => ['q' => 'api']]);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertIsArray($body['data']['results']);
        $this->assertNotEmpty($body['data']['results']);
        $this->assertSame(3, (int)$body['data']['total']);
        $this->assertSame('api', $body['data']['keyword']);
    }

    /**
     * index() without q returns 400 BAD_REQUEST.
     *
     * @return void
     */
    public function testIndexWithoutKeywordReturns400()
    {
        $result = $this->runController(self::CONTROLLER, 'index');

        $body = $this->assertApiResponse($result, 400, false);
        $this->assertSame('BAD_REQUEST', $body['error']['code'] ?? null);
    }

    /**
     * index() rejects a keyword shorter than 2 characters.
     *
     * @return void
     */
    public function testIndexRejectsShortKeyword()
    {
        $result = $this->runController(self::CONTROLLER, 'index', [], ['get' => ['q' => 'x']]);

        $body = $this->assertApiResponse($result, 400, false);
        $this->assertSame('BAD_REQUEST', $body['error']['code'] ?? null);
    }

    /**
     * index() includes pagination metadata and HATEOAS links.
     *
     * @return void
     */
    public function testIndexIncludesPaginationAndLinks()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'index',
            [],
            ['get' => ['q' => 'api', 'page' => '1', 'per_page' => '2']]
        );

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertArrayHasKey('pagination', $body['data']);
        $this->assertSame(2, (int)$body['data']['pagination']['per_page']);
        $this->assertArrayHasKey('_links', $body['data']);
    }

    /**
     * index() with type=all returns both blog posts and pages.
     *
     * @return void
     */
    public function testIndexSearchesAcrossPostsAndPages()
    {
        $result = $this->runController(self::CONTROLLER, 'index', [], ['get' => ['q' => 'api']]);

        $body = $this->assertApiResponse($result, 200, true);

        $types = array_unique(array_column($body['data']['results'], 'type'));
        $this->assertContains('blog', $types);
        $this->assertContains('page', $types);
    }

    /**
     * index() with type=posts returns blog posts only.
     *
     * @return void
     */
    public function testIndexWithPostsTypeReturnsPostsOnly()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'index',
            [],
            ['get' => ['q' => 'api', 'type' => 'posts']]
        );

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertNotEmpty($body['data']['results']);
        foreach ($body['data']['results'] as $item) {
            $this->assertSame('blog', $item['type']);
        }
    }

    /**
     * index() with type=pages returns pages only.
     *
     * @return void
     */
    public function testIndexWithPagesTypeReturnsPagesOnly()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'index',
            [],
            ['get' => ['q' => 'api', 'type' => 'pages']]
        );

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertNotEmpty($body['data']['results']);
        foreach ($body['data']['results'] as $item) {
            $this->assertSame('page', $item['type']);
        }
    }

    /**
     * index() returns an empty result set when nothing matches.
     *
     * @return void
     */
    public function testIndexReturnsEmptyResultsForNoMatch()
    {
        $result = $this->runController(self::CONTROLLER, 'index', [], ['get' => ['q' => 'zzzzzz']]);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame([], $body['data']['results']);
        $this->assertSame(0, (int)$body['data']['total']);
    }
}
