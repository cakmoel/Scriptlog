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
        $_POST['post_title'] = 'Test Post';
        $_POST['post_content'] = 'Content';
        $_POST['post_status'] = 'publish';
        $_POST['visibility'] = 'public';
        $_POST['comment_status'] = 'open';
        $_POST['post_locale'] = 'en';

        $this->postService->expects($this->once())
            ->method('addPost');

        $this->postService->method('postAuthorId')->willReturn(1);

        $this->appService->createPost(
            '', '', '', 0, '', '', 'administrator', null
        );
    }

    public function testUpdatePostCallsModifyPost(): void
    {
        $_POST['post_title'] = 'Updated Post';
        $_POST['post_content'] = 'Updated content';
        $_POST['post_status'] = 'publish';
        $_POST['visibility'] = 'public';
        $_POST['comment_status'] = 'open';
        $_POST['post_locale'] = 'en';

        $this->postService->expects($this->once())
            ->method('modifyPost');

        $this->postService->method('postAuthorId')->willReturn(1);
        $this->postService->method('grabPost')->willReturn(null);

        $this->appService->updatePost(
            1, '', '', '', 0, '', '', 'administrator', null, null
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
