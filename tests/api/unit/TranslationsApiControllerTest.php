<?php
/**
 * TranslationsApiController Test
 *
 * Unit tests for Scriptlog\Controller\Api\TranslationsApiController driven
 * through the subprocess runner (ApiTestCase::runController). Controller
 * actions terminate the process via ApiResponse::send(), so each action runs
 * in an isolated PHP process against the seeded test database.
 *
 * Auth: index/show/export are public (requiresAuth=false at construction);
 * store/update/destroy/import/cache require an administrator.
 *
 * Note: the controller routes by language code ('code') and translation key
 * ('key') for reads, and by translation ID for update()/destroy(). update()
 * and destroy() return 200 on success (the plan's 204 is not what the
 * implementation returns). import() reads from php://input, which is empty in
 * the CLI runner, so only its 400 no-body branch is asserted here.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class TranslationsApiControllerTest extends ApiTestCase
{
    /**
     * @var string Fully-qualified controller class under test.
     */
    private const CONTROLLER = \Scriptlog\Controller\Api\TranslationsApiController::class;

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
     * index() returns the translations for the requested locale.
     *
     * The fixture seeds 10 translations for "en" and none for "ar".
     *
     * @return void
     */
    public function testIndexReturnsPaginatedTranslations()
    {
        $result = $this->runController(self::CONTROLLER, 'index', ['code' => 'en']);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertIsArray($body['data']);
        $this->assertSame(10, count($body['data']));
        $this->assertHasPagination($body);
    }

    /**
     * index() filters by locale (empty result set for a locale without keys).
     *
     * @return void
     */
    public function testIndexFiltersByLocale()
    {
        $result = $this->runController(self::CONTROLLER, 'index', ['code' => 'ar']);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame([], $body['data']);
    }

    /**
     * index() includes HATEOAS links.
     *
     * @return void
     */
    public function testIndexReturnsHateoasLinks()
    {
        $result = $this->runController(self::CONTROLLER, 'index', ['code' => 'en']);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertHasLinks($body);
    }

    /**
     * show() returns a single translation by key.
     *
     * @return void
     */
    public function testShowReturnsSingleTranslation()
    {
        $result = $this->runController(self::CONTROLLER, 'show', ['code' => 'en', 'key' => 'form.save']);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame('form.save', $body['data']['translation_key']);
        $this->assertSame('Save', $body['data']['translation_value']);
    }

    /**
     * show() returns 404 for an unknown translation key.
     *
     * @return void
     */
    public function testShowReturns404ForMissingTranslation()
    {
        $result = $this->runController(self::CONTROLLER, 'show', ['code' => 'en', 'key' => 'missing.key']);

        $body = $this->assertApiResponse($result, 404, false);
        $this->assertSame('NOT_FOUND', $body['error']['code'] ?? null);
    }

    /**
     * store() creates a translation and returns 201.
     *
     * @return void
     */
    public function testStoreCreatesTranslationReturns201()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'store',
            [],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/translations'],
                'post' => [
                    'lang_code' => 'en',
                    'translation_key' => 'footer.tagline',
                    'translation_value' => 'Tagline',
                ],
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
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/translations'],
                'post' => ['lang_code' => '', 'translation_key' => '', 'translation_value' => ''],
            ]
        );

        $body = $this->assertApiResponse($result, 422, false);
        $this->assertSame('VALIDATION_ERROR', $body['error']['code'] ?? null);
    }

    /**
     * update() modifies a translation and returns 200.
     *
     * @return void
     */
    public function testUpdateModifiesTranslation()
    {
        $translationId = ApiDataFixture::getTranslationIds()[0];

        $result = $this->runController(
            self::CONTROLLER,
            'update',
            ['id' => (string)$translationId],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/v1/translations/' . $translationId],
                'post' => ['translation_value' => 'Updated Save'],
            ]
        );

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertArrayHasKey('id', $body['data']);
    }

    /**
     * destroy() deletes a translation and returns 200.
     *
     * @return void
     */
    public function testDestroyDeletesTranslation()
    {
        $translationId = ApiDataFixture::getTranslationIds()[0];

        $result = $this->runController(
            self::CONTROLLER,
            'destroy',
            ['id' => (string)$translationId],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/api/v1/translations/' . $translationId],
            ]
        );

        $this->assertSame(200, (int)$result['code']);
    }

    /**
     * export() returns the full translation set for a locale.
     *
     * @return void
     */
    public function testExportReturnsAllTranslations()
    {
        $result = $this->runController(self::CONTROLLER, 'export', ['code' => 'en']);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertIsArray($body['data']);
        $this->assertSame(10, count($body['data']));
    }

    /**
     * import() returns 400 when no JSON body is supplied.
     *
     * The CLI runner provides an empty php://input, exercising the guard.
     *
     * @return void
     */
    public function testImportReturns400WithoutBody()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'import',
            ['code' => 'en'],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/translations/en/import'],
            ]
        );

        $body = $this->assertApiResponse($result, 400, false);
        $this->assertSame('BAD_REQUEST', $body['error']['code'] ?? null);
    }

    /**
     * cache() regenerates the translation cache and returns 200.
     *
     * @return void
     */
    public function testCacheRegeneratesCache()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'cache',
            ['code' => 'en'],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/translations/en/cache'],
            ]
        );

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertNull($body['data']);
    }
}
