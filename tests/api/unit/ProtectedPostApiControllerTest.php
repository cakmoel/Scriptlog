<?php
/**
 * ProtectedPostApiController Test
 *
 * Unit tests for Scriptlog\Controller\Api\ProtectedPostApiController driven
 * through the subprocess runner (ApiTestCase::runController). Controller
 * actions terminate the process via ApiResponse::send(), so each action runs
 * in an isolated PHP process against the seeded test database.
 *
 * Auth: both actions are public (requiresAuth=false at construction).
 *
 * Coverage note: unlock()/verify() read the password from the JSON request
 * body (ApiController::getJsonBody() reads php://input). The CLI runner
 * provides an empty php://input, so the guard branches (400 for a missing
 * post ID and missing password) are asserted here; the password-verification
 * branches (401 incorrect password / 200 unlock) require an HTTP request with
 * a JSON body and a password-protected post, which is out of reach of the
 * unit harness and deferred to an HTTP-level integration test.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class ProtectedPostApiControllerTest extends ApiTestCase
{
    /**
     * @var string Fully-qualified controller class under test.
     */
    private const CONTROLLER = \Scriptlog\Controller\Api\ProtectedPostApiController::class;

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
     * unlock() returns 400 when no post ID is supplied.
     *
     * @return void
     */
    public function testUnlockReturns400WithoutPostId()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'unlock',
            [],
            ['server' => ['REQUEST_METHOD' => 'POST']]
        );

        $this->assertSame(400, (int)$result['code']);
    }

    /**
     * unlock() returns 400 when the password is missing.
     *
     * php://input is empty in the CLI runner, so the password guard fires.
     *
     * @return void
     */
    public function testUnlockReturns400WithoutPassword()
    {
        $postId = ApiDataFixture::getPostIds()[0];

        $result = $this->runController(
            self::CONTROLLER,
            'unlock',
            ['id' => (string)$postId],
            ['server' => ['REQUEST_METHOD' => 'POST']]
        );

        $body = $this->assertApiResponse($result, 400, false);
        $this->assertSame('ERROR', $body['error']['code'] ?? null);
    }

    /**
     * verify() returns 400 when no post ID is supplied.
     *
     * @return void
     */
    public function testVerifyReturns400WithoutPostId()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'verify',
            [],
            ['server' => ['REQUEST_METHOD' => 'POST']]
        );

        $this->assertSame(400, (int)$result['code']);
    }

    /**
     * verify() returns 400 when the password is missing.
     *
     * @return void
     */
    public function testVerifyReturns400WithoutPassword()
    {
        $postId = ApiDataFixture::getPostIds()[0];

        $result = $this->runController(
            self::CONTROLLER,
            'verify',
            ['id' => (string)$postId],
            ['server' => ['REQUEST_METHOD' => 'POST']]
        );

        $this->assertSame(400, (int)$result['code']);
    }
}
