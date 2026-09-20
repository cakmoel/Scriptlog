<?php
/**
 * CommentsApiController Test
 *
 * Unit tests for Scriptlog\Controller\Api\CommentsApiController driven
 * through the subprocess runner (ApiTestCase::runController). Controller
 * actions terminate the process via ApiResponse::send(), so each action runs
 * in an isolated PHP process against the seeded test database.
 *
 * Auth: index/show/store are public (requiresAuth=false at construction).
 * show() additionally restricts non-approved comments to authenticated
 * callers. update()/destroy() re-enable requiresAuth and enforce
 * administrator/editor permission.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class CommentsApiControllerTest extends ApiTestCase
{
    /**
     * @var string Fully-qualified controller class under test.
     */
    private const CONTROLLER = \Scriptlog\Controller\Api\CommentsApiController::class;

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
     * index() returns only approved comments with pagination and links.
     *
     * @return void
     */
    public function testIndexReturnsApprovedCommentsOnly()
    {
        $result = $this->runController(self::CONTROLLER, 'index');

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertIsArray($body['data']);
        $this->assertGreaterThan(0, count($body['data']));
        foreach ($body['data'] as $comment) {
            $this->assertSame('approved', $comment['status']);
        }
        $this->assertHasPagination($body);
        $this->assertHasLinks($body);
    }

    /**
     * index() filters comments by post_id.
     *
     * @return void
     */
    public function testIndexFiltersByPostId()
    {
        $postId = ApiDataFixture::getPostIds()[0];

        $result = $this->runController(self::CONTROLLER, 'index', ['post_id' => (string)$postId]);

        $body = $this->assertApiResponse($result, 200, true);
        foreach ($body['data'] as $comment) {
            $this->assertSame((int)$postId, (int)$comment['post_id']);
        }
    }

    /**
     * show() returns a single approved comment without authentication.
     *
     * @return void
     */
    public function testShowReturnsApprovedCommentWithoutAuth()
    {
        $commentId = ApiDataFixture::getCommentIds()[0];

        $result = $this->runController(self::CONTROLLER, 'show', ['id' => (string)$commentId]);

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame((int)$commentId, (int)$body['data']['id']);
        $this->assertArrayHasKey('_links', $body);
        $this->assertArrayHasKey('self', $body['_links']);
    }

    /**
     * show() rejects non-approved comments for unauthenticated callers.
     *
     * @return void
     */
    public function testShowNonApprovedCommentForbiddenWithoutAuth()
    {
        $commentId = ApiDataFixture::getCommentIds()[1];

        $result = $this->runController(self::CONTROLLER, 'show', ['id' => (string)$commentId]);

        $body = $this->assertApiResponse($result, 403, false);
        $this->assertSame('FORBIDDEN', $body['error']['code'] ?? null);
    }

    /**
     * show() returns non-approved comments for authenticated callers.
     *
     * @return void
     */
    public function testShowNonApprovedCommentAllowedWithAuth()
    {
        $commentId = ApiDataFixture::getCommentIds()[1];

        $result = $this->runController(
            self::CONTROLLER,
            'show',
            ['id' => (string)$commentId],
            ['api_key' => ApiDataFixture::getApiKey()]
        );

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame((int)$commentId, (int)$body['data']['id']);
    }

    /**
     * show() returns 404 for a missing comment.
     *
     * @return void
     */
    public function testShowReturns404ForMissingComment()
    {
        $result = $this->runController(self::CONTROLLER, 'show', ['id' => '999999']);

        $body = $this->assertApiResponse($result, 404, false);
        $this->assertSame('NOT_FOUND', $body['error']['code'] ?? null);
    }

    /**
     * store() creates a comment as an unauthenticated visitor and returns 201.
     *
     * @return void
     */
    public function testStoreCreatesCommentReturns201()
    {
        $postId = ApiDataFixture::getPostIds()[0];

        $result = $this->runController(
            self::CONTROLLER,
            'store',
            [],
            [
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/comments'],
                'post' => [
                    'comment_author_name' => 'Cara',
                    'comment_content' => 'A public comment from the API.',
                    'comment_post_id' => (string)$postId,
                ],
            ]
        );

        $body = $this->assertApiResponse($result, 201, true);
        $this->assertArrayHasKey('id', $body['data']);
        $this->assertSame('pending', $body['data']['status']);
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
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/comments'],
                'post' => ['comment_author_name' => '', 'comment_content' => '', 'comment_post_id' => ''],
            ]
        );

        $body = $this->assertApiResponse($result, 422, false);
        $this->assertSame('VALIDATION_ERROR', $body['error']['code'] ?? null);
    }

    /**
     * store() returns 403 when the post has comments closed.
     *
     * @return void
     */
    public function testStoreRejectsClosedPost()
    {
        $closedPostId = ApiDataFixture::getPostIds()[3];

        $result = $this->runController(
            self::CONTROLLER,
            'store',
            [],
            [
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/comments'],
                'post' => [
                    'comment_author_name' => 'Cara',
                    'comment_content' => 'Should be rejected.',
                    'comment_post_id' => (string)$closedPostId,
                ],
            ]
        );

        $body = $this->assertApiResponse($result, 403, false);
        $this->assertSame('FORBIDDEN', $body['error']['code'] ?? null);
    }

    /**
     * store() returns 404 when the target post does not exist.
     *
     * @return void
     */
    public function testStoreReturns404ForMissingPost()
    {
        $result = $this->runController(
            self::CONTROLLER,
            'store',
            [],
            [
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/api/v1/comments'],
                'post' => [
                    'comment_author_name' => 'Cara',
                    'comment_content' => 'Should be rejected.',
                    'comment_post_id' => '999999',
                ],
            ]
        );

        $body = $this->assertApiResponse($result, 404, false);
        $this->assertSame('NOT_FOUND', $body['error']['code'] ?? null);
    }

    /**
     * update() modifies a comment and returns 200.
     *
     * @return void
     */
    public function testUpdateModifiesExistingComment()
    {
        $commentId = ApiDataFixture::getCommentIds()[0];

        $result = $this->runController(
            self::CONTROLLER,
            'update',
            ['id' => (string)$commentId],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/v1/comments/' . $commentId],
                'post' => ['comment_content' => 'Updated comment content'],
            ]
        );

        $body = $this->assertApiResponse($result, 200, true);
        $this->assertSame('Updated comment content', $body['data']['content']);
    }

    /**
     * destroy() deletes a comment and returns 204.
     *
     * @return void
     */
    public function testDestroyDeletesCommentReturns204()
    {
        $commentId = ApiDataFixture::getCommentIds()[2];

        $result = $this->runController(
            self::CONTROLLER,
            'destroy',
            ['id' => (string)$commentId],
            [
                'api_key' => ApiDataFixture::getApiKey(),
                'server' => ['REQUEST_METHOD' => 'DELETE', 'REQUEST_URI' => '/api/v1/comments/' . $commentId],
            ]
        );

        $this->assertSame(204, (int)$result['code']);
    }
}
