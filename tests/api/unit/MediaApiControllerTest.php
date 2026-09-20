<?php
/**
 * MediaApiController Test
 *
 * Unit tests for Scriptlog\Controller\Api\MediaApiController driven through
 * the subprocess runner (ApiTestCase::runController). Controller actions
 * terminate the process via ApiResponse::send(), so each action runs in an
 * isolated PHP process against the seeded test database.
 *
 * Auth: MediaApiController declares no constructor, so the inherited
 * ApiController constructor runs with the default requiresAuth=true. Requests
 * must therefore carry a valid API key; upload() additionally requires an
 * admin session (scriptlog_session_login / scriptlog_session_level), which
 * the runner's "session" option populates.
 *
 * Implementation notes (deviations from the plan table): invalid upload types
 * return 400 (not 422), and unauthenticated uploads are rejected with the
 * generic 401 from the parent constructor. A successful upload writes image
 * files to public/files/pictures/, so it is intentionally not exercised at
 * the unit level to avoid filesystem side effects.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class MediaApiControllerTest extends ApiTestCase
{
    /**
     * @var string Fully-qualified controller class under test.
     */
    private const CONTROLLER = \Scriptlog\Controller\Api\MediaApiController::class;

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
     * Build the admin session payload used by authenticateAdminSession().
     *
     * @return array
     */
    private function adminSession()
    {
        return [
            'scriptlog_session_login' => 'administrator',
            'scriptlog_session_level' => 'administrator',
        ];
    }

    /**
     * upload() returns 401 when no admin session is present.
     *
     * @return void
     */
    public function testUploadRejectsUnauthenticatedRequest()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'upload',
            [],
            ['server' => ['REQUEST_METHOD' => 'POST']]
        );

        $body = $this->assertApiResponse($result, 401, false);
        $this->assertSame('UNAUTHORIZED', $body['error']['code'] ?? null);
    }

    /**
     * upload() returns 400 when no file is attached.
     *
     * @return void
     */
    public function testUploadReturns400WithoutFile()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'upload',
            [],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'session' => $this->adminSession(),
                'server' => ['REQUEST_METHOD' => 'POST'],
            ]
        );

        $body = $this->assertApiResponse($result, 400, false);
        $this->assertSame('BAD_REQUEST', $body['error']['code'] ?? null);
    }

    /**
     * upload() returns 400 for an unsupported file type.
     *
     * @return void
     */
    public function testUploadRejectsInvalidFileType()
    {
        $badFile = tempnam(sys_get_temp_dir(), 'api-media');
        file_put_contents($badFile, 'MZ fake executable content');

        try {
            $result = $this->runController(
                self::CONTROLLER,
                'upload',
                [],
                [
                    'api_key' => ApiDataFixture::getApiKey(),
                    'session' => $this->adminSession(),
                    'files' => [
                        'image' => [
                            'name' => 'bad.exe',
                            'type' => 'application/octet-stream',
                            'tmp_name' => $badFile,
                            'error' => UPLOAD_ERR_OK,
                            'size' => filesize($badFile),
                        ],
                    ],
                    'server' => ['REQUEST_METHOD' => 'POST'],
                ]
            );
        } finally {
            @unlink($badFile);
        }

        $body = $this->assertApiResponse($result, 400, false);
        $this->assertSame('BAD_REQUEST', $body['error']['code'] ?? null);
    }
}
