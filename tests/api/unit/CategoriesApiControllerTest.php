<?php
/**
 * CategoriesApiController Test
 *
 * Unit tests for Scriptlog\Controller\Api\CategoriesApiController driven
 * through the subprocess runner (ApiTestCase::runController). Controller
 * actions terminate the process via ApiResponse::send(), so each action runs
 * in an isolated PHP process against the seeded test database.
 *
 * Auth: GET actions (index/show/posts) are public (requiresAuth=false at
 * construction); write actions re-enable requiresAuth and enforce permissions
 * via ApiAuth::hasPermission(). The fixture's API key (administrator) is
 * supplied for all write assertions.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class CategoriesApiControllerTest extends ApiTestCase
{
    /**
     * @var string Fully-qualified controller class under test.
     */
    private const CONTROLLER = \Scriptlog\Controller\Api\CategoriesApiController::class;

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
     * index() returns a paginated list with HATEOAS links.
     *
     * @return void
     */
    public function testIndexReturnsPaginatedListWithHateoas()
    {
        $result = $this->runController(self::CONTROLLER, 'index', ['page' => 1, 'per_page' => 10]);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertIsArray($body['data']);
        $this->assertHasLinks($body);
        $this->assertHasPagination($body);
    }

    /**
     * index() honours the per_page query parameter.
     *
     * @return void
     */
    public function testIndexRespectsPerPageParam()
    {
        $result = $this->runController(self::CONTROLLER, 'index', ['per_page' => '1']);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame(1, (int)$body['pagination']['per_page']);
        $this->assertSame(1, count($body['data']));
    }

    /**
     * show() returns the requested category with self and canonical links.
     *
     * @return void
     */
    public function testShowReturnsSingleCategoryWithLinks()
    {
        $topicId = ApiDataFixture::getTopicIds()[0];

        $result = $this->runController(self::CONTROLLER, 'show', ['id' => (string)$topicId]);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame((int)$topicId, (int)$body['data']['id']);
        $this->assertArrayHasKey('self', $body['_links']);
        $this->assertArrayHasKey('canonical', $body['_links']);
        $this->assertArrayHasKey('collection', $body['_links']);
    }

    /**
     * show() returns 404 for a missing category.
     *
     * @return void
     */
    public function testShowReturns404ForMissingCategory()
    {
        $result = $this->runController(self::CONTROLLER, 'show', ['id' => '999999']);

        $body = $this->assertApiResponse($result, 404, false);
        $this->assertSame('NOT_FOUND', $body['error']['code'] ?? null);
    }

    /**
     * show() DTO transforms with permalink format when rewrites are enabled.
     *
     * @return void
     */
    public function testShowDtoUsesPermalinkUrlWhenRewritesEnabled()
    {
        $topicId = ApiDataFixture::getTopicIds()[0];
        ApiDataFixture::setRewriteStatus('yes');

        $result = $this->runController(self::CONTROLLER, 'show', ['id' => (string)$topicId]);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertStringContainsString('/category/', $body['data']['url']);
        $this->assertStringNotContainsString('?cat=', $body['data']['url']);
    }

    /**
     * store() creates a category and returns 201.
     *
     * @return void
     */
    public function testStoreCreatesCategoryReturns201()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'store',
            [],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/categories'],
                'post' => ['topic_title' => 'Fresh Category'],
            ]
        );

        $body = $this->assertApiResponse($result, 201, true);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertSame('Fresh Category', $body['data']['title']);
    }

    /**
     * store() returns 422 when the required title is missing.
     *
     * @return void
     */
    public function testStoreReturns422OnValidationFailure()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'store',
            [],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/categories'],
                'post' => ['topic_title' => ''],
            ]
        );

        $body = $this->assertApiResponse($result, 422, false);
        $this->assertSame('VALIDATION_ERROR', $body['error']['code'] ?? null);
    }

    /**
     * store() returns 409 when a category with the same title exists.
     *
     * @return void
     */
    public function testStoreReturns409OnDuplicateTitle()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'store',
            [],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/categories'],
                'post' => ['topic_title' => 'Duplicate Slug Title'],
            ]
        );

        $this->assertApiResponse($result, 201, true);

        $result2 = $this->runController(
            self::CONTROLLER,
            'store',
            [],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/categories'],
                'post' => ['topic_title' => 'Duplicate Slug Title'],
            ]
        );

        $body = $this->assertApiResponse($result2, 409, false);
        $this->assertSame('CONFLICT', $body['error']['code'] ?? null);
    }

    /**
     * update() modifies an existing category and returns 200.
     *
     * @return void
     */
    public function testUpdateModifiesExistingCategory()
    {
        $topicId = ApiDataFixture::getTopicIds()[0];

        $result = $this->runController(
            self::CONTROLLER,
            'update',
            ['id' => (string)$topicId],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/v1/categories/' . $topicId],
                'post' => ['topic_title' => 'Renamed Category'],
            ]
        );

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame('Renamed Category', $body['data']['title']);
    }

    /**
     * update() returns 404 for a missing category.
     *
     * @return void
     */
    public function testUpdateReturns404ForMissingCategory()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'update',
            ['id' => '999999'],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/v1/categories/999999'],
                'post' => ['topic_title' => 'Renamed Category'],
            ]
        );

        $body = $this->assertApiResponse($result, 404, false);
        $this->assertSame('NOT_FOUND', $body['error']['code'] ?? null);
    }

    /**
     * destroy() deletes a category and returns 204.
     *
     * @return void
     */
    public function testDestroyDeletesCategoryReturns204()
    {
        $topicId = ApiDataFixture::getTopicIds()[0];

        $result = $this->runController(
            self::CONTROLLER,
            'destroy',
            ['id' => (string)$topicId],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/api/v1/categories/' . $topicId],
            ]
        );

        $this->assertSame(204, (int)$result['code']);
    }

    /**
     * posts() returns the category plus its posts and pagination.
     *
     * @return void
     */
    public function testPostsReturnsCategoryWithPostsAndPagination()
    {
        $topicId = ApiDataFixture::getTopicIds()[0];

        $result = $this->runController(self::CONTROLLER, 'posts', ['id' => (string)$topicId]);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame((int)$topicId, (int)$body['data']['category']['id']);
        $this->assertIsArray($body['data']['posts']);
        $this->assertHasPagination($body);
    }

    /**
     * posts() returns 404 for a missing category.
     *
     * @return void
     */
    public function testPostsReturns404ForMissingCategory()
    {
        $result = $this->runController(self::CONTROLLER, 'posts', ['id' => '999999']);

        $body = $this->assertApiResponse($result, 404, false);
        $this->assertSame('NOT_FOUND', $body['error']['code'] ?? null);
    }
}
