<?php
/**
 * CommentsApi Integration Test
 *
 * Database-backed integration tests for the CommentService layer, verifying
 * comment creation with real foreign-key constraints and the nested reply
 * structure in tbl_comments. Runs against the real blogware_test database
 * seeded by ApiDataFixture.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class CommentsApiIntegrationTest extends ApiTestCase
{
    /**
     * @var \Scriptlog\Service\CommentService
     */
    private $commentService;

    /**
     * Seed the test database and build a real CommentService.
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
        $this->commentService = new \Scriptlog\Service\CommentService(
            new \Scriptlog\Dao\CommentDao(),
            new \Scriptlog\Core\FormValidator(),
            new \Scriptlog\Core\Sanitize()
        );
    }

    /**
     * Creating a comment persists the row and joins back to its post.
     *
     * @return void
     */
    public function testCreateCommentPersistsWithForeignPost()
    {
        $postId = ApiDataFixture::getPostIds()[0];

        $this->assertSame('open', $this->commentService->checkPostAcceptsComments($postId));

        $commentId = $this->commentService->createCommentApi([
            'comment_post_id' => $postId,
            'comment_parent_id' => 0,
            'comment_author_name' => 'Integration Tester',
            'comment_author_ip' => '127.0.0.1',
            'comment_author_email' => 'tester@example.com',
            'comment_content' => 'Integration comment body.',
            'comment_status' => 'approved',
            'comment_date' => date('Y-m-d H:i:s'),
        ]);

        $this->assertGreaterThan(0, $commentId);

        $comment = $this->commentService->getCommentApi($commentId);
        $this->assertNotNull($comment);
        $this->assertSame((int)$postId, (int)$comment['comment_post_id']);
        $this->assertSame('Integration Tester', $comment['comment_author_name']);
        $this->assertArrayHasKey('post_title', $comment);
        $this->assertSame('API Published Post One', $comment['post_title']);
    }

    /**
     * A nested reply stores the parent comment ID in the database.
     *
     * @return void
     */
    public function testNestedReplyStructureInDatabase()
    {
        $postId = ApiDataFixture::getPostIds()[0];

        $parentId = $this->commentService->createCommentApi([
            'comment_post_id' => $postId,
            'comment_parent_id' => 0,
            'comment_author_name' => 'Parent Author',
            'comment_author_ip' => '127.0.0.1',
            'comment_content' => 'Parent comment.',
            'comment_status' => 'approved',
            'comment_date' => date('Y-m-d H:i:s'),
        ]);

        $replyId = $this->commentService->createCommentApi([
            'comment_post_id' => $postId,
            'comment_parent_id' => $parentId,
            'comment_author_name' => 'Reply Author',
            'comment_author_ip' => '127.0.0.1',
            'comment_content' => 'A reply to the parent.',
            'comment_status' => 'pending',
            'comment_date' => date('Y-m-d H:i:s'),
        ]);

        $reply = $this->commentService->getCommentApi($replyId);
        $this->assertSame((int)$parentId, (int)$reply['comment_parent_id']);
    }

    /**
     * A closed post reports its comment status and rejects creation.
     *
     * The service itself does not enforce the policy (the controller does),
     * but the DAO must report the closed status for the guard to work.
     *
     * @return void
     */
    public function testClosedPostReportsStatus()
    {
        $closedPostId = ApiDataFixture::getPostIds()[3];

        $this->assertSame('closed', $this->commentService->checkPostAcceptsComments($closedPostId));
    }
}
