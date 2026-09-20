<?php
/**
 * PostsApiController Test
 *
 * Unit tests for Scriptlog\Controller\Api\PostsApiController driven through
 * the subprocess runner (ApiTestCase::runController). Controller actions
 * terminate the process via ApiResponse::send(), so each action runs in an
 * isolated PHP process against the seeded test database.
 *
 * Auth: the controller is public for GET actions (requiresAuth=false at
 * construction) but write actions re-enable requiresAuth and enforce
 * permissions via ApiAuth::hasPermission(). The fixture's API key
 * (administrator) is supplied for all write assertions.
 *
 * Note: the Location header set by ApiResponse::created() is not observable
 * through the runner because headers_list() is empty in CLI mode; the 201 +
 * Location pair is asserted at the HTTP level by the integration suite.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class PostsApiControllerTest extends ApiTestCase
{
    /**
     * @var string Fully-qualified controller class under test.
     */
    private const CONTROLLER = \Scriptlog\Controller\Api\PostsApiController::class;

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
        $result = $this->runController(self::CONTROLLER, 'index', ['per_page' => '5']);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame(5, (int)$body['pagination']['per_page']);
    }

    /**
     * index() applies sort_by/sort_order to the returned list.
     *
     * @return void
     */
    public function testIndexRespectsSortingParams()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'index',
            ['sort_by' => 'post_title', 'sort_order' => 'ASC', 'per_page' => '10']
        );

        $body = $this->assertApiResponse($result, 200, true);

        $titles = array_column($body['data'], 'title');
        $expected = $titles;
        sort($expected);
        $this->assertSame($expected, $titles);
    }

    /**
     * show() returns the requested post with a self link.
     *
     * @return void
     */
    public function testShowReturnsSinglePost()
    {
        $postId = ApiDataFixture::getPostIds()[0];

        $result = $this->runController(self::CONTROLLER, 'show', ['id' => (string)$postId]);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame((int)$postId, (int)$body['data']['id']);
        $this->assertArrayHasKey('_links', $body);
        $this->assertArrayHasKey('self', $body['_links']);
    }

    /**
     * show() includes canonical and comments HATEOAS links.
     *
     * @return void
     */
    public function testShowIncludesCanonicalAndCommentsLinks()
    {
        $postId = ApiDataFixture::getPostIds()[0];

        $result = $this->runController(self::CONTROLLER, 'show', ['id' => (string)$postId]);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertArrayHasKey('canonical', $body['_links']);
        $this->assertArrayHasKey('comments', $body['_links']);
    }

    /**
     * show() returns 404 for a missing post.
     *
     * @return void
     */
    public function testShowReturns404ForMissingPost()
    {
        $result = $this->runController(self::CONTROLLER, 'show', ['id' => '999999']);

        $body = $this->assertApiResponse($result, 404, false);
        $this->assertSame('NOT_FOUND', $body['error']['code'] ?? null);
    }

    /**
     * show() response matches the PostApiDto shape.
     *
     * @return void
     */
    public function testShowReturnsDtoShape()
    {
        $postId = ApiDataFixture::getPostIds()[0];

        $result = $this->runController(self::CONTROLLER, 'show', ['id' => (string)$postId]);

        $body = $this->assertApiResponse($result, 200, true);

        $data = $body['data'];
        foreach (['id', 'title', 'slug', 'content', 'status', 'visibility', 'type', 'url'] as $key) {
            $this->assertArrayHasKey($key, $data, 'PostApiDto field missing: ' . $key);
        }
        $this->assertArrayHasKey('author', $data);
        $this->assertIsArray($data['author']);
    }

    /**
     * store() creates a post and returns 201.
     *
     * The Location header is not observable in CLI (see class docblock).
     *
     * @return void
     */
    public function testStoreCreatesPostReturns201()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'store',
            [],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/posts'],
                'post' => ['post_title' => 'Created Post', 'post_content' => '<p>Created body</p>'],
            ]
        );

        $body = $this->assertApiResponse($result, 201, true);
        $this->assertArrayHasKey('id', $body['data']);
    }

    /**
     * store() returns 422 when required fields are missing.
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
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/posts'],
                'post' => ['post_title' => '', 'post_content' => ''],
            ]
        );

        $body = $this->assertApiResponse($result, 422, false);
        $this->assertSame('VALIDATION_ERROR', $body['error']['code'] ?? null);
    }

    /**
     * update() modifies an existing post and returns 200.
     *
     * @return void
     */
    public function testUpdateModifiesExistingPost()
    {
        $postId = ApiDataFixture::getPostIds()[0];

        $result = $this->runController(
            self::CONTROLLER,
            'update',
            ['id' => (string)$postId],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/v1/posts/' . $postId],
                'post' => ['post_title' => 'Updated Title'],
            ]
        );

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame('Updated Title', $body['data']['title']);
    }

    /**
     * update() returns 404 for a missing post.
     *
     * @return void
     */
    public function testUpdateReturns404ForMissingPost()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'update',
            ['id' => '999999'],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/v1/posts/999999'],
                'post' => ['post_title' => 'Updated Title'],
            ]
        );

        $body = $this->assertApiResponse($result, 404, false);
        $this->assertSame('NOT_FOUND', $body['error']['code'] ?? null);
    }

    /**
     * destroy() deletes a post and returns 204.
     *
     * @return void
     */
    public function testDestroyDeletesPostReturns204()
    {
        $postId = ApiDataFixture::getPostIds()[0];

        $result = $this->runController(
            self::CONTROLLER,
            'destroy',
            ['id' => (string)$postId],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/api/v1/posts/' . $postId],
            ]
        );

        $this->assertSame(204, (int)$result['code']);
    }

    /**
     * destroy() returns 404 for a missing post.
     *
     * @return void
     */
    public function testDestroyReturns404ForMissingPost()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'destroy',
            ['id' => '999999'],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/api/v1/posts/999999'],
            ]
        );

        $body = $this->assertApiResponse($result, 404, false);
        $this->assertSame('NOT_FOUND', $body['error']['code'] ?? null);
    }
}
