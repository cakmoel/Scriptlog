<?php
/**
 * CommentApiDto Test
 *
 * Unit tests for Scriptlog\Dto\Api\CommentApiDto.
 *
 * The transform() output contract (verified against the implementation in
 * lib/dto/api/CommentApiDto.php) emits: id, post_id, parent_id,
 * author{name, email}, content, status, date. When the source row carries
 * post context (post_title) a nested "post" object is added. CommentApiDto
 * does not emit per-item _links nor a "replies" collection (nested replies
 * are surfaced via the parent_id field).
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../../ApiTestCase.php';
require_once __DIR__ . '/../../fixtures/ApiDataFixture.php';

class CommentApiDtoTest extends ApiTestCase
{
    /**
     * @var array Sample comment row used across tests.
     */
    private $comment;

    /**
     * Connect the test database, default permalinks to "no", and build a
     * sample comment row. Skips when the test database is unavailable.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists('Registry', false)) {
            class_alias('Scriptlog\Core\Registry', 'Registry');
        }

        ApiDataFixture::connect();
        if (!ApiDataFixture::isAvailable()) {
            $this->markTestSkipped('Test database unavailable');
        }

        ApiDataFixture::setRewriteStatus('no');

        $this->comment = [
            'ID' => 9,
            'comment_post_id' => 7,
            'comment_parent_id' => 0,
            'comment_author_name' => 'Alice',
            'comment_author_email' => 'alice@example.com',
            'comment_content' => 'Nice post.',
            'comment_status' => 'approved',
            'comment_date' => '2026-01-03 09:00:00',
        ];
    }

    /**
     * Restore permalinks to "no" after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ApiDataFixture::setRewriteStatus('no');
        parent::tearDown();
    }

    /**
     * transform() returns the core comment output keys.
     *
     * @return void
     */
    public function testTransformReturnsCoreKeys()
    {
        $output = \Scriptlog\Dto\Api\CommentApiDto::transform($this->comment, 'https://example.com');

        $this->assertSame(9, $output['id']);
        $this->assertSame(7, $output['post_id']);
        $this->assertSame(0, $output['parent_id']);
        $this->assertSame('Alice', $output['author']['name']);
        $this->assertSame('alice@example.com', $output['author']['email']);
        $this->assertSame('Nice post.', $output['content']);
        $this->assertSame('approved', $output['status']);
        $this->assertSame('2026-01-03 09:00:00', $output['date']);
    }

    /**
     * Without post context in the source row no "post" object is emitted.
     *
     * @return void
     */
    public function testTransformOmitsPostContextWhenNotProvided()
    {
        $output = \Scriptlog\Dto\Api\CommentApiDto::transform($this->comment, 'https://example.com');

        $this->assertArrayNotHasKey('post', $output);
    }

    /**
     * With post context the nested "post" object is emitted and its URL
     * falls back to ?p={id} when permalinks are disabled.
     *
     * @return void
     */
    public function testTransformAddsPostContextWhenProvided()
    {
        $this->comment['post_title'] = 'Hello World';
        $this->comment['post_slug'] = 'hello-world';

        $output = \Scriptlog\Dto\Api\CommentApiDto::transform($this->comment, 'https://example.com');

        $this->assertSame('Hello World', $output['post']['title']);
        $this->assertSame('hello-world', $output['post']['slug']);
        $this->assertSame('https://example.com/?p=7', $output['post']['url']);
    }

    /**
     * The nested post URL uses the permalink format when enabled.
     *
     * @return void
     */
    public function testTransformPostUrlUsesPermalinkWhenRewriteEnabled()
    {
        ApiDataFixture::setRewriteStatus('yes');

        $this->comment['post_title'] = 'Hello World';
        $this->comment['post_slug'] = 'hello-world';

        $output = \Scriptlog\Dto\Api\CommentApiDto::transform($this->comment, 'https://example.com');

        $this->assertSame('https://example.com/post/7/hello-world', $output['post']['url']);
    }

    /**
     * Missing optional fields fall back to safe defaults.
     *
     * @return void
     */
    public function testTransformHandlesMissingFields()
    {
        $minimal = [
            'ID' => 1,
            'comment_post_id' => 1,
        ];

        $output = \Scriptlog\Dto\Api\CommentApiDto::transform($minimal, 'https://example.com');

        $this->assertSame(0, $output['parent_id']);
        $this->assertSame('', $output['author']['name']);
        $this->assertSame('', $output['author']['email']);
        $this->assertSame('', $output['content']);
        $this->assertSame('pending', $output['status']);
        $this->assertSame('', $output['date']);
        $this->assertArrayNotHasKey('post', $output);
    }

    /**
     * transformCollection() maps every item through transform().
     *
     * @return void
     */
    public function testTransformCollectionMapsAllItems()
    {
        $output = \Scriptlog\Dto\Api\CommentApiDto::transformCollection([$this->comment], 'https://example.com');

        $this->assertCount(1, $output);
        $this->assertSame(9, $output[0]['id']);
        $this->assertArrayHasKey('author', $output[0]);
    }
}
