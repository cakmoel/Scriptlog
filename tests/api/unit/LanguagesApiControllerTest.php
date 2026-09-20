<?php
/**
 * LanguagesApiController Test
 *
 * Unit tests for Scriptlog\Controller\Api\LanguagesApiController driven
 * through the subprocess runner (ApiTestCase::runController). Controller
 * actions terminate the process via ApiResponse::send(), so each action runs
 * in an isolated PHP process against the seeded test database.
 *
 * Auth: index/show/default are public (requiresAuth=false at construction);
 * store/update/destroy/setDefault require an administrator.
 *
 * Note: this controller routes by language code ('code' param), not numeric
 * ID, and its write actions are admin-only. update()/destroy()/setDefault()
 * return 200 (not 204) on success.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class LanguagesApiControllerTest extends ApiTestCase
{
    /**
     * @var string Fully-qualified controller class under test.
     */
    private const CONTROLLER = \Scriptlog\Controller\Api\LanguagesApiController::class;

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
     * index() returns the active languages with pagination metadata.
     *
     * @return void
     */
    public function testIndexReturnsPaginatedLanguages()
    {
        $result = $this->runController(self::CONTROLLER, 'index');

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertIsArray($body['data']);
        $this->assertGreaterThan(0, count($body['data']));
        foreach ($body['data'] as $language) {
            $this->assertArrayHasKey('lang_code', $language);
        }
        $this->assertHasPagination($body);
    }

    /**
     * index() honours the per_page parameter passed via $_GET.
     *
     * The index action reads pagination from the $_GET superglobal.
     *
     * @return void
     */
    public function testIndexRespectsPerPageParam()
    {
        $result = $this->runController(self::CONTROLLER, 'index', [], ['get' => ['per_page' => '2']]);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame(2, (int)$body['pagination']['per_page']);
        $this->assertSame(2, count($body['data']));
    }

    /**
     * show() returns a single language by its code.
     *
     * @return void
     */
    public function testShowReturnsSingleLanguage()
    {
        $result = $this->runController(self::CONTROLLER, 'show', ['code' => 'en']);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame('en', $body['data']['lang_code']);
        $this->assertArrayHasKey('_links', $body);
    }

    /**
     * show() returns 404 for an unknown language code.
     *
     * @return void
     */
    public function testShowReturns404ForMissingLanguage()
    {
        $result = $this->runController(self::CONTROLLER, 'show', ['code' => 'xx']);

        $body = $this->assertApiResponse($result, 404, false);
        $this->assertSame('NOT_FOUND', $body['error']['code'] ?? null);
    }

    /**
     * default() returns the configured default language.
     *
     * @return void
     */
    public function testDefaultReturnsDefaultLanguage()
    {
        $result = $this->runController(self::CONTROLLER, 'default');

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame(1, (int)$body['data']['lang_is_default']);
    }

    /**
     * store() creates a language and returns 201.
     *
     * @return void
     */
    public function testStoreCreatesLanguageReturns201()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'store',
            [],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/languages'],
                'post' => ['lang_code' => 'de', 'lang_name' => 'German', 'lang_native' => 'Deutsch'],
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
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/languages'],
                'post' => ['lang_code' => '', 'lang_name' => '', 'lang_native' => ''],
            ]
        );

        $body = $this->assertApiResponse($result, 422, false);
        $this->assertSame('VALIDATION_ERROR', $body['error']['code'] ?? null);
    }

    /**
     * update() modifies a language and returns 200.
     *
     * @return void
     */
    public function testUpdateModifiesLanguage()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'update',
            ['code' => 'en'],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/v1/languages/en'],
                'post' => ['lang_name' => 'English Updated'],
            ]
        );

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertArrayHasKey('id', $body['data']);
    }

    /**
     * update() returns 404 for an unknown language code.
     *
     * @return void
     */
    public function testUpdateReturns404ForMissingLanguage()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'update',
            ['code' => 'xx'],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/v1/languages/xx'],
                'post' => ['lang_name' => 'English Updated'],
            ]
        );

        $body = $this->assertApiResponse($result, 404, false);
        $this->assertSame('NOT_FOUND', $body['error']['code'] ?? null);
    }

    /**
     * destroy() deletes a language and returns 200.
     *
     * @return void
     */
    public function testDestroyDeletesLanguage()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'destroy',
            ['code' => 'id'],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/api/v1/languages/id'],
            ]
        );

        $this->assertSame(200, (int)$result['code']);
    }

    /**
     * setDefault() marks a language as the default and returns 200.
     *
     * @return void
     */
    public function testSetDefaultMarksLanguageAsDefault()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'setDefault',
            ['code' => 'ar'],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/v1/languages/ar/default'],
            ]
        );

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertArrayHasKey('id', $body['data']);
    }
}
