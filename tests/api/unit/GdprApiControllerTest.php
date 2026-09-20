<?php
/**
 * GdprApiController Test
 *
 * Unit tests for Scriptlog\Controller\Api\GdprApiController driven through
 * the subprocess runner (ApiTestCase::runController). Controller actions
 * terminate the process via ApiResponse::send(), so each action runs in an
 * isolated PHP process against the seeded test database.
 *
 * Auth: GDPR endpoints are public (requiresAuth=false at construction).
 *
 * Implementation notes (deviations from the plan table): consent() returns
 * 200 (not 201), validates the "status" field rather than an email address
 * and answers 400 INVALID_STATUS (not 422) for bad input, and
 * getConsentStatus() always returns 200 with a consent_given boolean (there
 * is no 404 path). The fixture truncates tbl_consents so the status is
 * deterministic per test.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class GdprApiControllerTest extends ApiTestCase
{
    /**
     * @var string Fully-qualified controller class under test.
     */
    private const CONTROLLER = \Scriptlog\Controller\Api\GdprApiController::class;

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
     * consent() records a valid consent and echoes the status.
     *
     * @return void
     */
    public function testConsentRecordsAcceptedConsent()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'consent',
            [],
            [
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/gdpr/consent'],
                'post' => ['status' => 'accepted'],
            ]
        );

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame('accepted', $body['data']['status']);
        $this->assertArrayHasKey('timestamp', $body['data']);
        $this->assertHasLinks($body);
    }

    /**
     * consent() rejects an unknown consent status.
     *
     * @return void
     */
    public function testConsentRejectsInvalidStatus()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'consent',
            [],
            [
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/gdpr/consent'],
                'post' => ['status' => 'maybe'],
            ]
        );

        $body = $this->assertApiResponse($result, 400, false);
        $this->assertSame('INVALID_STATUS', $body['error']['code'] ?? null);
    }

    /**
     * consent() returns 405 for a non-POST request.
     *
     * @return void
     */
    public function testConsentRejectsWrongMethod()
    {
        $result = $this->runController(self::CONTROLLER, 'consent');

        $body = $this->assertApiResponse($result, 405, false);
        $this->assertSame('METHOD_NOT_ALLOWED', $body['error']['code'] ?? null);
    }

    /**
     * getConsentStatus() reports the current cookie consent state.
     *
     * With a freshly truncated tbl_consents no consent has been recorded yet.
     *
     * @return void
     */
    public function testGetConsentStatusDefaultsToNotConsented()
    {
        $result = $this->runController(self::CONTROLLER, 'getConsentStatus');

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertFalse($body['data']['consent_given']);
        $this->assertSame('cookie', $body['data']['consent_type']);
    }

    /**
     * getConsentStatus() reflects a previously recorded consent.
     *
     * @return void
     */
    public function testGetConsentStatusReflectsRecordedConsent()
    {
        $this->runController(
            self::CONTROLLER,
            'consent',
            [],
            [
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/gdpr/consent'],
                'post' => ['status' => 'accepted'],
            ]
        );

        $result = $this->runController(self::CONTROLLER, 'getConsentStatus');

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertTrue($body['data']['consent_given']);
    }

    /**
     * getConsentStatus() returns 405 for a non-GET request.
     *
     * @return void
     */
    public function testGetConsentStatusRejectsWrongMethod()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'getConsentStatus',
            [],
            ['server' => ['REQUEST_METHOD' => 'POST']]
        );

        $body = $this->assertApiResponse($result, 405, false);
        $this->assertSame('METHOD_NOT_ALLOWED', $body['error']['code'] ?? null);
    }

    /**
     * Both actions expose HATEOAS root links.
     *
     * @return void
     */
    public function testResponsesIncludeHateoasLinks()
    {
        $result = $this->runController(self::CONTROLLER, 'getConsentStatus');

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertHasLinks($body);
        $this->assertArrayHasKey('self', $body['_links']);
    }
}
