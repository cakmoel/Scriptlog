<?php
/**
 * PostApiDto Test
 *
 * Unit tests for Scriptlog\Dto\Api\PostApiDto.
 *
 * The transform() output contract (verified against the implementation in
 * lib/dto/api/PostApiDto.php) emits: id, title, slug, content, summary,
 * excerpt, status, visibility, tags, comment_status, type, author{id,
 * login, name}, date, modified, url.
 *
 * rewrite_status() is resolved from the test database (tbl_settings) so
 * both the permalink and the ?p=/?pg= fallback URL branches are exercised.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../../ApiTestCase.php';
require_once __DIR__ . '/../../fixtures/ApiDataFixture.php';

class PostApiDtoTest extends ApiTestCase
{
    /**
     * @var array Sample post row used across tests.
     */
    private $post;

    /**
     * Connect the test database, default permalinks to "no", and build a
     * sample post row. Skips when the test database is unavailable.
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

        $this->post = [
            'ID' => 7,
            'post_author' => 2,
            'author_login' => 'admin',
            'author_name' => 'Administrator',
            'post_date' => '2026-01-01 10:00:00',
            'post_modified' => '2026-01-02 10:00:00',
            'post_title' => 'Hello World',
            'post_slug' => 'hello-world',
            'post_content' => '<p>Short content</p>',
            'post_summary' => 'A short summary.',
            'post_status' => 'publish',
            'post_visibility' => 'public',
            'post_tags' => 'php,api,test',
            'comment_status' => 'open',
            'post_type' => 'blog',
        ];
    }

    /**
     * Restore permalinks to "no" after each test so other tests are not
     * affected by the rewrite setting.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        ApiDataFixture::setRewriteStatus('no');
        parent::tearDown();
    }

    /**
     * transform() returns every documented output key.
     *
     * @return void
     */
    public function testTransformReturnsAllRequiredKeys()
    {
        $output = \Scriptlog\Dto\Api\PostApiDto::transform($this->post, 'https://example.com');

        $this->assertSame(7, $output['id']);
        $this->assertSame('Hello World', $output['title']);
        $this->assertSame('hello-world', $output['slug']);
        $this->assertSame('<p>Short content</p>', $output['content']);
        $this->assertSame('A short summary.', $output['summary']);
        $this->assertSame('A short summary.', $output['excerpt']);
        $this->assertSame('publish', $output['status']);
        $this->assertSame('public', $output['visibility']);
        $this->assertSame(['php', 'api', 'test'], $output['tags']);
        $this->assertSame('open', $output['comment_status']);
        $this->assertSame('blog', $output['type']);

        $this->assertSame(2, $output['author']['id']);
        $this->assertSame('admin', $output['author']['login']);
        $this->assertSame('Administrator', $output['author']['name']);

        $this->assertSame('2026-01-01 10:00:00', $output['date']);
        $this->assertSame('2026-01-02 10:00:00', $output['modified']);
        $this->assertArrayHasKey('url', $output);
    }

    /**
     * With permalinks disabled a blog post URL falls back to ?p={id}.
     *
     * @return void
     */
    public function testTransformUsesPostUrlFallbackWhenRewriteDisabled()
    {
        $output = \Scriptlog\Dto\Api\PostApiDto::transform($this->post, 'https://example.com');

        $this->assertSame('https://example.com/?p=7', $output['url']);
    }

    /**
     * With permalinks disabled a page URL falls back to ?pg={id}.
     *
     * @return void
     */
    public function testTransformUsesPageUrlFallbackWhenRewriteDisabled()
    {
        $this->post['post_type'] = 'page';

        $output = \Scriptlog\Dto\Api\PostApiDto::transform($this->post, 'https://example.com');

        $this->assertSame('https://example.com/?pg=7', $output['url']);
    }

    /**
     * With permalinks enabled the DTO emits pretty post and page URLs.
     *
     * @return void
     */
    public function testTransformUsesPermalinkUrlsWhenRewriteEnabled()
    {
        ApiDataFixture::setRewriteStatus('yes');

        $postOutput = \Scriptlog\Dto\Api\PostApiDto::transform($this->post, 'https://example.com');
        $this->assertSame('https://example.com/post/7/hello-world', $postOutput['url']);

        $this->post['post_type'] = 'page';
        $pageOutput = \Scriptlog\Dto\Api\PostApiDto::transform($this->post, 'https://example.com');
        $this->assertSame('https://example.com/page/hello-world', $pageOutput['url']);
    }

    /**
     * When a summary exists it is used as the excerpt verbatim.
     *
     * @return void
     */
    public function testTransformPrefersSummaryForExcerpt()
    {
        $this->post['post_summary'] = 'Custom summary text.';
        $this->post['post_content'] = str_repeat('x', 200);

        $output = \Scriptlog\Dto\Api\PostApiDto::transform($this->post, 'https://example.com');

        $this->assertSame('Custom summary text.', $output['excerpt']);
    }

    /**
     * Without a summary the excerpt is derived from stripped content and is
     * truncated to 150 characters with an ellipsis.
     *
     * @return void
     */
    public function testTransformDerivesTruncatedExcerptFromContent()
    {
        $this->post['post_summary'] = '';
        $this->post['post_content'] = '<p>' . str_repeat('a', 200) . '</p>';

        $output = \Scriptlog\Dto\Api\PostApiDto::transform($this->post, 'https://example.com');

        $this->assertSame(153, strlen($output['excerpt']));
        $this->assertSame('...', substr($output['excerpt'], -3));
    }

    /**
     * An empty tag string resolves to an empty tags array.
     *
     * @return void
     */
    public function testTransformHandlesEmptyTags()
    {
        $this->post['post_tags'] = '';

        $output = \Scriptlog\Dto\Api\PostApiDto::transform($this->post, 'https://example.com');

        $this->assertSame([], $output['tags']);
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
            'post_author' => 1,
            'post_title' => 'No extras',
            'post_slug' => '',
            'post_status' => 'publish',
        ];

        $output = \Scriptlog\Dto\Api\PostApiDto::transform($minimal, 'https://example.com');

        $this->assertSame('', $output['content']);
        $this->assertSame('', $output['summary']);
        $this->assertSame('', $output['excerpt']);
        $this->assertSame('public', $output['visibility']);
        $this->assertSame([], $output['tags']);
        $this->assertSame('open', $output['comment_status']);
        $this->assertSame('blog', $output['type']);
        $this->assertSame('', $output['date']);
        $this->assertSame('', $output['modified']);
        $this->assertSame('https://example.com/?p=1', $output['url']);
    }

    /**
     * transformCollection() maps every item through transform().
     *
     * @return void
     */
    public function testTransformCollectionMapsAllItems()
    {
        $posts = [$this->post, $this->post];

        $output = \Scriptlog\Dto\Api\PostApiDto::transformCollection($posts, 'https://example.com');

        $this->assertCount(2, $output);
        $this->assertSame(7, $output[0]['id']);
        $this->assertSame(7, $output[1]['id']);
        $this->assertArrayHasKey('url', $output[0]);
        $this->assertArrayHasKey('url', $output[1]);
    }
}
