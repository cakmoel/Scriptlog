<?php
/**
 * TopicApiDto Test
 *
 * Unit tests for Scriptlog\Dto\Api\TopicApiDto.
 *
 * The transform() output contract (verified against the implementation in
 * lib/dto/api/TopicApiDto.php) emits: id, title, slug, status, post_count,
 * url. TopicApiDto does not emit per-item _links.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../../ApiTestCase.php';
require_once __DIR__ . '/../../fixtures/ApiDataFixture.php';

class TopicApiDtoTest extends ApiTestCase
{
    /**
     * @var array Sample topic row used across tests.
     */
    private $topic;

    /**
     * Connect the test database, default permalinks to "no", and build a
     * sample topic row. Skips when the test database is unavailable.
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

        $this->topic = [
            'ID' => 3,
            'topic_title' => 'Technology',
            'topic_slug' => 'technology',
            'topic_status' => 'Y',
            'post_count' => 4,
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
     * transform() returns the core topic output keys.
     *
     * @return void
     */
    public function testTransformReturnsCoreKeys()
    {
        $output = \Scriptlog\Dto\Api\TopicApiDto::transform($this->topic, 'https://example.com');

        $this->assertSame(3, $output['id']);
        $this->assertSame('Technology', $output['title']);
        $this->assertSame('technology', $output['slug']);
        $this->assertSame('Y', $output['status']);
        $this->assertSame(4, $output['post_count']);
        $this->assertArrayHasKey('url', $output);
    }

    /**
     * With permalinks disabled the category URL falls back to ?cat={id}.
     *
     * @return void
     */
    public function testTransformUsesCategoryUrlFallbackWhenRewriteDisabled()
    {
        $output = \Scriptlog\Dto\Api\TopicApiDto::transform($this->topic, 'https://example.com');

        $this->assertSame('https://example.com/?cat=3', $output['url']);
    }

    /**
     * With permalinks enabled the category URL is /category/{slug}.
     *
     * @return void
     */
    public function testTransformUsesPermalinkUrlWhenRewriteEnabled()
    {
        ApiDataFixture::setRewriteStatus('yes');

        $output = \Scriptlog\Dto\Api\TopicApiDto::transform($this->topic, 'https://example.com');

        $this->assertSame('https://example.com/category/technology', $output['url']);
    }

    /**
     * A missing post_count defaults to 0.
     *
     * @return void
     */
    public function testTransformDefaultsMissingPostCount()
    {
        unset($this->topic['post_count']);

        $output = \Scriptlog\Dto\Api\TopicApiDto::transform($this->topic, 'https://example.com');

        $this->assertSame(0, $output['post_count']);
    }

    /**
     * A missing status defaults to "published".
     *
     * @return void
     */
    public function testTransformDefaultsMissingStatus()
    {
        unset($this->topic['topic_status']);

        $output = \Scriptlog\Dto\Api\TopicApiDto::transform($this->topic, 'https://example.com');

        $this->assertSame('published', $output['status']);
    }

    /**
     * transformCollection() maps every item through transform().
     *
     * @return void
     */
    public function testTransformCollectionMapsAllItems()
    {
        $output = \Scriptlog\Dto\Api\TopicApiDto::transformCollection([$this->topic], 'https://example.com');

        $this->assertCount(1, $output);
        $this->assertSame(3, $output[0]['id']);
        $this->assertArrayHasKey('url', $output[0]);
    }
}
