<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

/**
 * PostApplicationServiceSchedulingTest
 *
 * Verifies the scheduled-posting normalization logic in PostApplicationService
 * through its public createPost()/updatePost() workflows:
 *
 *   - 'publish' + future date  -> stored as 'scheduled'
 *   - 'scheduled' + past date  -> promoted to 'publish'
 *   - ordinary publish/draft   -> unchanged
 *   - media access follows the normalized status
 *
 * PostService is mocked; no database required.
 *
 * @category Tests
 * @version  1.0
 */
class PostApplicationServiceSchedulingTest extends TestCase
{
    private $postService;
    private $appService;

    private const FUTURE_DATE = '2030-01-01 00:00:00';
    private const PAST_DATE = '2000-01-01 00:00:00';

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

    private function createFiltered(array $overrides = []): array
    {
        return array_merge([
            'post_title' => 'Scheduling Test Post',
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
        ], $overrides);
    }

    public function testCreatePostWithFutureDateStoresAsScheduled(): void
    {
        $_POST['post_status'] = 'publish';
        $_POST['post_date'] = self::FUTURE_DATE;

        $this->postService->expects($this->once())
            ->method('setPublish')
            ->with('scheduled');

        $this->postService->method('postAuthorId')->willReturn(1);

        $this->appService->createPost(
            '', '', '', 0, '', '', 'administrator',
            $this->createFiltered(['post_date' => self::FUTURE_DATE])
        );
    }

    public function testCreatePostWithPastOrNoDateStaysPublished(): void
    {
        $_POST['post_status'] = 'publish';

        $this->postService->expects($this->once())
            ->method('setPublish')
            ->with('publish');

        $this->postService->method('postAuthorId')->willReturn(1);

        $this->appService->createPost(
            '', '', '', 0, '', '', 'administrator',
            $this->createFiltered(['post_date' => self::PAST_DATE])
        );
    }

    public function testCreatePostScheduledStatusKeepsScheduled(): void
    {
        $_POST['post_status'] = 'scheduled';
        $_POST['post_date'] = self::FUTURE_DATE;

        $this->postService->expects($this->once())
            ->method('setPublish')
            ->with('scheduled');

        $this->postService->method('postAuthorId')->willReturn(1);

        $this->appService->createPost(
            '', '', '', 0, '', '', 'administrator',
            $this->createFiltered([
                'post_status' => 'scheduled',
                'post_date' => self::FUTURE_DATE,
            ])
        );
    }

    public function testCreatePostFutureDateMarksMediaPrivate(): void
    {
        $_POST['post_status'] = 'publish';
        $_POST['post_date'] = self::FUTURE_DATE;

        $this->postService->expects($this->once())
            ->method('processPostImage')
            ->with(
                $this->anything(), $this->anything(), $this->anything(),
                $this->anything(), $this->anything(), $this->anything(),
                $this->anything(), $this->anything(),
                'private',
                $this->anything(), $this->anything(),
                $this->anything(), $this->anything()
            );

        $this->postService->method('postAuthorId')->willReturn(1);

        $this->appService->createPost(
            '', '', '', 0, '', '', 'administrator',
            $this->createFiltered(['post_date' => self::FUTURE_DATE])
        );
    }

    public function testUpdatePostWithFutureDateStoresAsScheduled(): void
    {
        $filtered = $this->createFiltered([
            'post_id' => 1,
            'post_status' => 'publish',
            'post_date' => self::FUTURE_DATE,
        ]);

        $this->postService->expects($this->once())
            ->method('setPublish')
            ->with('scheduled');

        $this->postService->expects($this->once())
            ->method('setPostDate')
            ->with(self::FUTURE_DATE);

        $this->postService->method('postAuthorId')->willReturn(1);
        $this->postService->method('grabPost')->willReturn(null);

        $this->appService->updatePost(
            1, '', '', '', 0, '', '', 'administrator', null, $filtered
        );
    }

    public function testUpdatePostPastScheduledDatePromotesToPublish(): void
    {
        $filtered = $this->createFiltered([
            'post_id' => 1,
            'post_status' => 'scheduled',
            'post_date' => self::PAST_DATE,
        ]);

        $this->postService->expects($this->once())
            ->method('setPublish')
            ->with('publish');

        $this->postService->expects($this->never())
            ->method('setPostDate');

        $this->postService->method('postAuthorId')->willReturn(1);
        $this->postService->method('grabPost')->willReturn(null);

        $this->appService->updatePost(
            1, '', '', '', 0, '', '', 'administrator', null, $filtered
        );
    }

    public function testUpdatePostOrdinaryPublishKeepsPublish(): void
    {
        $filtered = $this->createFiltered([
            'post_id' => 1,
            'post_status' => 'publish',
        ]);

        $this->postService->expects($this->once())
            ->method('setPublish')
            ->with('publish');

        $this->postService->expects($this->never())
            ->method('setPostDate');

        $this->postService->method('postAuthorId')->willReturn(1);
        $this->postService->method('grabPost')->willReturn(null);

        $this->appService->updatePost(
            1, '', '', '', 0, '', '', 'administrator', null, $filtered
        );
    }

    public function testUpdatePostScheduledMarksMediaPrivate(): void
    {
        $filtered = $this->createFiltered([
            'post_id' => 1,
            'post_status' => 'publish',
            'post_date' => self::FUTURE_DATE,
        ]);

        $this->postService->expects($this->once())
            ->method('processPostImage')
            ->with(
                $this->anything(), $this->anything(), $this->anything(),
                $this->anything(), $this->anything(), $this->anything(),
                $this->anything(), $this->anything(),
                'private',
                $this->anything(), $this->anything(),
                $this->anything(), $this->anything()
            );

        $this->postService->method('postAuthorId')->willReturn(1);
        $this->postService->method('grabPost')->willReturn(null);

        $this->appService->updatePost(
            1, '', '', '', 0, '', '', 'administrator', null, $filtered
        );
    }
}
