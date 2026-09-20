<?php
/**
 * ArchivesApiController Test
 *
 * Unit tests for Scriptlog\Controller\Api\ArchivesApiController driven
 * through the subprocess runner (ApiTestCase::runController). Controller
 * actions terminate the process via ApiResponse::send(), so each action runs
 * in an isolated PHP process against the seeded test database.
 *
 * Auth: all archive endpoints are public (requiresAuth=false at construction).
 *
 * The fixture seeds two published blog posts dated today, so the archive
 * index contains a single year/month bucket with post_count 2 (the static
 * page is excluded from the archive index).
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class ArchivesApiControllerTest extends ApiTestCase
{
    /**
     * @var string Fully-qualified controller class under test.
     */
    private const CONTROLLER = \Scriptlog\Controller\Api\ArchivesApiController::class;

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
     * index() returns archives grouped by year and month.
     *
     * @return void
     */
    public function testIndexReturnsGroupedArchives()
    {
        $result = $this->runController(self::CONTROLLER, 'index');

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertIsArray($body['data']['archives']);
        $this->assertNotEmpty($body['data']['archives']);
        $this->assertSame(1, (int)$body['data']['total_years']);

        $first = $body['data']['archives'][0];
        $this->assertSame((int)date('Y'), (int)$first['year']);
        $this->assertArrayHasKey('months', $first);
        $this->assertGreaterThan(0, (int)$first['total_posts']);
    }

    /**
     * index() places the HATEOAS _links at the response root, not in data.
     *
     * @return void
     */
    public function testIndexHasRootLevelLinks()
    {
        $result = $this->runController(self::CONTROLLER, 'index');

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertArrayHasKey('_links', $body);
        $this->assertArrayNotHasKey('_links', $body['data']);
    }

    /**
     * year() returns the posts published in the given year.
     *
     * @return void
     */
    public function testYearReturnsPostsForYear()
    {
        $year = (string)date('Y');

        $result = $this->runController(self::CONTROLLER, 'year', ['year' => $year]);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame((int)$year, (int)$body['data']['year']);
        $this->assertIsArray($body['data']['posts']);
        $this->assertNotEmpty($body['data']['posts']);
        $this->assertHasPagination($body);
    }

    /**
     * year() returns 404 for a year without published posts.
     *
     * @return void
     */
    public function testYearReturns404ForEmptyYear()
    {
        $result = $this->runController(self::CONTROLLER, 'year', ['year' => '1999']);

        $body = $this->assertApiResponse($result, 404, false);
        $this->assertSame('NOT_FOUND', $body['error']['code'] ?? null);
    }

    /**
     * year() returns 400 for an out-of-range year.
     *
     * @return void
     */
    public function testYearReturns400ForInvalidYear()
    {
        $result = $this->runController(self::CONTROLLER, 'year', ['year' => '50']);

        $body = $this->assertApiResponse($result, 400, false);
        $this->assertSame('BAD_REQUEST', $body['error']['code'] ?? null);
    }

    /**
     * month() returns the posts published in the given month.
     *
     * @return void
     */
    public function testMonthReturnsPostsForMonth()
    {
        $year = (string)date('Y');
        $month = (string)date('n');

        $result = $this->runController(self::CONTROLLER, 'month', ['year' => $year, 'month' => $month]);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame((int)$year, (int)$body['data']['year']);
        $this->assertSame((int)$month, (int)$body['data']['month']);
        $this->assertIsArray($body['data']['posts']);
        $this->assertNotEmpty($body['data']['posts']);
    }

    /**
     * month() returns 404 for a month without published posts.
     *
     * @return void
     */
    public function testMonthReturns404ForEmptyMonth()
    {
        $year = (string)date('Y');

        $result = $this->runController(self::CONTROLLER, 'month', ['year' => $year, 'month' => '1']);

        $body = $this->assertApiResponse($result, 404, false);
        $this->assertSame('NOT_FOUND', $body['error']['code'] ?? null);
    }
}
