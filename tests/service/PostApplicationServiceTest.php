<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class PostApplicationServiceTest extends TestCase
{
    private $postService;
    private $appService;

    protected function setUp(): void
    {
        $this->postService = $this->createMock(\Scriptlog\Service\PostService::class);
        $this->appService = new \Scriptlog\Service\PostApplicationService($this->postService);

        $_POST = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_SESSION = [];
    }

    public function testConstructorAcceptsPostService(): void
    {
        $this->assertInstanceOf(
            \Scriptlog\Service\PostApplicationService::class,
            $this->appService
        );
    }

    public function testCreatePostCallsAddPost(): void
    {
        $filtered = [
            'post_title' => 'Test Post',
            'post_content' => 'Content',
            'post_status' => 'publish',
            'visibility' => 'public',
            'comment_status' => 'open',
            'post_locale' => 'en',
            'post_summary' => '',
            'post_tags' => '',
            'post_headlines' => 0,
            'catID' => [1],
            'post_date' => '',
            'image_id' => '',
            'post_password' => '',
        ];

        $this->postService->expects($this->once())
            ->method('addPost');

        $this->postService->method('postAuthorId')->willReturn(1);

        $this->appService->createPost(
            '', '', '', 0, '', '', 'administrator', $filtered
        );
    }

    public function testUpdatePostCallsModifyPost(): void
    {
        $filtered = [
            'post_id' => 1,
            'post_title' => 'Updated Post',
            'post_content' => 'Updated content',
            'post_status' => 'publish',
            'visibility' => 'public',
            'comment_status' => 'open',
            'post_locale' => 'en',
            'post_summary' => '',
            'post_tags' => '',
            'post_headlines' => 0,
            'catID' => [1],
            'post_date' => '',
            'image_id' => '',
            'post_password' => '',
            'post_modified' => '',
        ];

        $this->postService->expects($this->once())
            ->method('modifyPost');

        $this->postService->method('postAuthorId')->willReturn(1);
        $this->postService->method('grabPost')->willReturn(null);

        $this->appService->updatePost(
            1, '', '', '', 0, '', '', 'administrator', null, $filtered
        );
    }

    public function testCreatePostWithFilteredData(): void
    {
        $filtered = [
            'post_title' => 'Prefiltered Title',
            'post_content' => 'Prefiltered content',
            'post_status' => 'draft',
            'visibility' => 'private',
            'comment_status' => 'closed',
            'post_locale' => 'en',
            'post_summary' => 'Summary',
            'post_tags' => '',
            'post_headlines' => 0,
            'catID' => [1],
            'post_date' => '',
            'image_id' => '',
            'post_password' => '',
        ];

        $this->postService->expects($this->once())
            ->method('addPost');

        $this->postService->method('postAuthorId')->willReturn(1);

        $this->appService->createPost(
            '', '', '', 0, '', '', 'editor', $filtered
        );
    }

    public function testUpdatePostWithFilteredData(): void
    {
        $filtered = [
            'post_id' => 1,
            'post_title' => 'Prefiltered Update',
            'post_content' => 'Updated prefiltered',
            'post_status' => 'publish',
            'visibility' => 'public',
            'comment_status' => 'open',
            'post_locale' => 'en',
            'post_summary' => 'New summary',
            'post_tags' => 'php, testing',
            'post_headlines' => 1,
            'catID' => [1],
            'post_date' => '',
            'image_id' => '',
            'post_password' => '',
            'post_modified' => '',
        ];

        $this->postService->expects($this->once())
            ->method('modifyPost');

        $this->postService->method('postAuthorId')->willReturn(1);
        $this->postService->method('grabPost')->willReturn(null);

        $this->appService->updatePost(
            1, '', '', '', 0, '', '', 'administrator', null, $filtered
        );
    }
}
