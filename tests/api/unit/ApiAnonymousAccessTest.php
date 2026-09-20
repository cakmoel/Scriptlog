<?php
/**
 * ApiAnonymousAccessTest
 *
 * Regression tests (F1) locking in the anonymous-access fix: public
 * endpoints return 200 without authentication while write endpoints still
 * reject anonymous callers.
 *
 * All public controllers set $this->requiresAuth = false before
 * parent::__construct(), so GET index() works without any auth header.
 * Write actions re-assert $this->requiresAuth = true inside the action and
 * then enforce permissions via ApiAuth::hasPermission() — an anonymous
 * caller fails that check and receives 403 FORBIDDEN. (The base
 * ApiController's 401 branch only fires when requiresAuth is true at
 * construction time, so anonymous writes are 403, not 401.)
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class ApiAnonymousAccessTest extends ApiTestCase
{
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
     * Public GET /posts returns 200 without any auth header.
     *
     * @return void
     */
    public function testPublicGetPostsWorksWithoutAuthHeader()
    {
        $result = $this->runController(\Scriptlog\Controller\Api\PostsApiController::class, 'index');

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
    }

    /**
     * Public GET /categories returns 200 without auth.
     *
     * @return void
     */
    public function testPublicGetCategoriesWorksWithoutAuth()
    {
        $result = $this->runController(\Scriptlog\Controller\Api\CategoriesApiController::class, 'index');

        $this->assertApiResponse($result, 200, true);
    }

    /**
     * Public GET /languages returns 200 without auth.
     *
     * @return void
     */
    public function testPublicGetLanguagesWorksWithoutAuth()
    {
        $result = $this->runController(\Scriptlog\Controller\Api\LanguagesApiController::class, 'index');

        $this->assertApiResponse($result, 200, true);
    }

    /**
     * Write POST /posts without any auth header is still rejected.
     *
     * The write actions re-enable requiresAuth and enforce permissions
     * themselves; anonymous callers receive 403 FORBIDDEN.
     *
     * @return void
     */
    public function testWritePostWithoutAuthIsRejected()
    {
        $result = $this->runController(
            \Scriptlog\Controller\Api\PostsApiController::class,
            'store',
            [],
            [
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/posts'],
                'post' => ['post_title' => 'Anonymous post', 'post_content' => 'must not be created'],
            ]
        );

        $body = $this->assertApiResponse($result, 403, false);
        $this->assertSame('FORBIDDEN', $body['error']['code'] ?? null);
    }

    /**
     * Public GET still works when a valid API key is supplied.
     *
     * @return void
     */
    public function testPublicGetWorksWhenApiKeySupplied()
    {
        $result = $this->runController(
            \Scriptlog\Controller\Api\PostsApiController::class,
            'index',
            [],
            ['api_key' => ApiDataFixture::getApiKey()]
        );

        $this->assertApiResponse($result, 200, true);
    }
}
