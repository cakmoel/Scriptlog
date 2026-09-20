<?php
/**
 * PostController Image Upload Test
 *
 * Tests that image upload works correctly during post editing.
 * Simulates the processPostUpdate -> processPostImage -> processUploadedImage flow.
 */
class PostControllerImageUploadTest extends \PHPUnit\Framework\TestCase
{
    private $postService;
    private $topicDao;
    private $mediaDao;
    private $postDao;

    protected function setUp(): void
    {
        parent::setUp();
        
        if (!defined('SCRIPTLOG')) {
            define('SCRIPTLOG', true);
        }

        $this->postDao = $this->createMock(PostDao::class);
        $validator = $this->createMock(FormValidator::class);
        $sanitizer = $this->createMock(Sanitize::class);
        
        $this->postService = $this->getMockBuilder(PostService::class)
            ->setConstructorArgs([$this->postDao, $validator, $sanitizer])
            ->onlyMethods(['setPostImage'])
            ->getMock();

        $this->topicDao = $this->createMock(TopicDao::class);
        $this->mediaDao = $this->createMock(MediaDao::class);
    }

    /**
     * Test that processDefaultImage returns null for update when no image_id posted
     */
    public function testProcessDefaultImageReturnsNullForUpdateWithoutImageId(): void
    {
        $reflection = new \ReflectionMethod(PostService::class, 'processDefaultImage');
        if (\PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }

        $validator = $this->createMock(FormValidator::class);
        $sanitizer = $this->createMock(Sanitize::class);
        $service = new PostService($this->postDao, $validator, $sanitizer);

        $_POST = [];
        
        $filtered = [
            'post_title' => 'Test',
            'post_content' => 'Content',
        ];

        $result = $reflection->invoke($service, $filtered, 800, 600, 'public', 'administrator', true);
        $this->assertNull($result, 'processDefaultImage should return null for update without image_id');
    }

    /**
     * Test that processDefaultImage returns the image_id when posted during update
     */
    public function testProcessDefaultImageReturnsImageIdWhenPosted(): void
    {
        $reflection = new \ReflectionMethod(PostService::class, 'processDefaultImage');
        if (\PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }

        $validator = $this->createMock(FormValidator::class);
        $sanitizer = $this->createMock(Sanitize::class);
        $service = new PostService($this->postDao, $validator, $sanitizer);

        $_POST = ['image_id' => '5'];
        
        $filtered = [
            'post_title' => 'Test',
            'post_content' => 'Content',
            'image_id' => 5,
        ];

        $result = $reflection->invoke($service, $filtered, 800, 600, 'public', 'administrator', true);
        $this->assertSame(5, $result, 'processDefaultImage should return image_id when $_POST[image_id] is set');
    }

    /**
     * Test modifyPost includes media_id when setPostImage was called
     */
    public function testModifyPostIncludesMediaIdWhenImageSet(): void
    {
        $_POST = ['catID' => [0]];

        $this->postDao->expects($this->once())
            ->method('updatePost')
            ->with(
                $this->anything(),
                $this->callback(function ($postData) {
                    $this->assertArrayHasKey('media_id', $postData, 'media_id must be in update data');
                    $this->assertEquals('3', $postData['media_id']);
                    return true;
                }),
                $this->anything(),
                $this->anything()
            );

        $validator = $this->createMock(FormValidator::class);
        $sanitizer = $this->createMock(Sanitize::class);
        $service = new PostService($this->postDao, $validator, $sanitizer);

        $service->setPostId(1);
        $service->setPostAuthor(1);
        $service->setPostTitle('Test');
        $service->setPostSlug('test');
        $service->setPostContent('Content', true); // skipPurify=true
        $service->setPublish('publish');
        $service->setVisibility('public');
        $service->setComment('open');
        $service->setMetaDesc('');
        $service->setPostLocale('en');
        $service->setTopics(0);
        $service->setHeadlines(0);
        $service->setPostModified('2026-07-10 00:00:00');
        $service->setPostImage('3');

        $service->modifyPost();
    }

    /**
     * Test modifyPost does NOT include media_id when setPostImage was NOT called
     */
    public function testModifyPostOmitsMediaIdWhenImageNotSet(): void
    {
        $_POST = ['catID' => [0]];

        $this->postDao->expects($this->once())
            ->method('updatePost')
            ->with(
                $this->anything(),
                $this->callback(function ($postData) {
                    $this->assertArrayNotHasKey('media_id', $postData, 'media_id must NOT be in update data');
                    return true;
                }),
                $this->anything(),
                $this->anything()
            );

        $validator = $this->createMock(FormValidator::class);
        $sanitizer = $this->createMock(Sanitize::class);
        $service = new PostService($this->postDao, $validator, $sanitizer);

        $service->setPostId(1);
        $service->setPostAuthor(1);
        $service->setPostTitle('Test');
        $service->setPostSlug('test');
        $service->setPostContent('Content', true); // skipPurify=true
        $service->setPublish('publish');
        $service->setVisibility('public');
        $service->setComment('open');
        $service->setMetaDesc('');
        $service->setPostLocale('en');
        $service->setTopics(0);
        $service->setHeadlines(0);
        $service->setPostModified('2026-07-10 00:00:00');

        $service->modifyPost();
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_FILES = [];
        parent::tearDown();
    }
}
