<?php
/**
 * PostsApi Integration Test
 *
 * Database-backed integration tests for the PostService/PostDao API layer.
 * Unlike the controller unit tests (which run each action in a subprocess
 * because ApiResponse::send() exits), these tests call the service methods
 * directly inside the PHPUnit process against the real blogware_test
 * database seeded by ApiDataFixture.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class PostsApiIntegrationTest extends ApiTestCase
{
    /**
     * @var \Scriptlog\Service\PostService
     */
    private $postService;

    /**
     * Seed the test database and build a real PostService -> PostDao chain.
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

        \Scriptlog\Core\Registry::set('app_url', get_test_config()['app']['url']);

        $this->postService = new \Scriptlog\Service\PostService(
            new \Scriptlog\Dao\PostDao(),
            new \Scriptlog\Core\FormValidator(),
            new \Scriptlog\Core\Sanitize()
        );
    }

    /**
     * Pagination boundaries match the seeded published post count.
     *
     * The fixture seeds two published blog posts, so a per_page of 1 must
     * produce exactly two pages and an empty page beyond the last one.
     *
     * @return void
     */
    public function testPaginationBoundariesMatchPublishedCount()
    {
        $total = $this->postService->countPublishedPostsApi();
        $this->assertSame(2, $total);

        $page1 = $this->postService->getPublishedPostsApi(1, 1);
        $page2 = $this->postService->getPublishedPostsApi(2, 1);
        $page3 = $this->postService->getPublishedPostsApi(3, 1);

        $this->assertCount(1, $page1);
        $this->assertCount(1, $page2);
        $this->assertCount(0, $page3);

        $this->assertNotSame($page1[0]['ID'], $page2[0]['ID']);
    }

    /**
     * Create, update and delete a post through the service layer.
     *
     * @return void
     */
    public function testCreateUpdateDeleteRoundTrip()
    {
        $adminId = ApiDataFixture::getAdminId();
        $before = $this->postService->countPublishedPostsApi();

        $postId = $this->postService->createPostApi([
            'post_author' => $adminId,
            'post_date' => date('Y-m-d H:i:s'),
            'post_modified' => date('Y-m-d H:i:s'),
            'post_title' => 'Integration Post',
            'post_slug' => 'integration-post',
            'post_content' => '<p>Integration body</p>',
            'post_summary' => '',
            'post_status' => 'publish',
            'post_visibility' => 'public',
            'post_tags' => '',
            'post_type' => 'blog',
            'comment_status' => 'open',
            'post_locale' => 'en',
        ]);

        $this->assertGreaterThan(0, $postId);

        $created = $this->postService->getPostByIdApi($postId);
        $this->assertNotNull($created);
        $this->assertSame('Integration Post', $created['post_title']);
        $this->assertSame($before + 1, $this->postService->countPublishedPostsApi());

        $this->postService->updatePostApi($postId, ['post_title' => 'Integration Post Updated']);
        $updated = $this->postService->getPostByIdApi($postId);
        $this->assertSame('Integration Post Updated', $updated['post_title']);

        $this->postService->removePostApi($postId);
        $this->assertNull($this->postService->getPostByIdApi($postId));
        $this->assertSame($before, $this->postService->countPublishedPostsApi());
    }

    /**
     * DTO transform produces a resolvable content URL for seeded posts.
     *
     * @return void
     */
    public function testHateoasLinksResolve()
    {
        $appUrl = get_test_config()['app']['url'];
        $posts = $this->postService->getPublishedPostsApi(1, 10);

        $dto = \Scriptlog\Dto\Api\PostApiDto::transformCollection($posts, $appUrl);

        $this->assertCount(2, $dto);
        foreach ($dto as $item) {
            $this->assertStringStartsWith($appUrl, $item['url']);
            $this->assertNotSame('', $item['url']);
        }
    }
}
