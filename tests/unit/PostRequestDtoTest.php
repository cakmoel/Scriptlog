<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class PostRequestDtoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_FILES = [];
        parent::tearDown();
    }

    public function testConstructorMapsPostFields(): void
    {
        $post = [
            'post_title' => 'Test Title',
            'post_content' => '<p>Hello world</p>',
            'post_summary' => 'A brief summary',
            'post_date' => '2026-07-20 10:00:00',
            'post_modified' => '2026-07-20 12:00:00',
            'image_id' => '5',
            'catID' => [1, 2, 3],
            'post_tags' => 'php, testing',
            'post_status' => 'publish',
            'visibility' => 'public',
            'post_password' => '',
            'post_headlines' => '1',
            'comment_status' => 'open',
            'post_locale' => 'en',
            'post_id' => '10',
        ];

        $dto = new \Scriptlog\Dto\PostRequestDto($post, []);

        $this->assertEquals('Test Title', $dto->postTitle);
        $this->assertEquals('<p>Hello world</p>', $dto->postContent);
        $this->assertEquals('A brief summary', $dto->postSummary);
        $this->assertEquals('2026-07-20 10:00:00', $dto->postDate);
        $this->assertEquals('2026-07-20 12:00:00', $dto->postModified);
        $this->assertEquals(5, $dto->imageId);
        $this->assertEquals([1, 2, 3], $dto->catIds);
        $this->assertEquals('php, testing', $dto->postTags);
        $this->assertEquals('publish', $dto->postStatus);
        $this->assertEquals('public', $dto->visibility);
        $this->assertEquals('', $dto->postPassword);
        $this->assertEquals(1, $dto->postHeadlines);
        $this->assertEquals('open', $dto->commentStatus);
        $this->assertEquals('en', $dto->postLocale);
        $this->assertEquals(10, $dto->postId);
    }

    public function testConstructorDefaults(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto([], []);

        $this->assertNull($dto->postTitle);
        $this->assertNull($dto->postContent);
        $this->assertNull($dto->postSummary);
        $this->assertNull($dto->postDate);
        $this->assertNull($dto->postModified);
        $this->assertNull($dto->imageId);
        $this->assertEquals([], $dto->catIds);
        $this->assertNull($dto->postTags);
        $this->assertNull($dto->postStatus);
        $this->assertNull($dto->visibility);
        $this->assertNull($dto->postPassword);
        $this->assertNull($dto->postHeadlines);
        $this->assertNull($dto->commentStatus);
        $this->assertEquals('en', $dto->postLocale);
        $this->assertNull($dto->postId);
    }

    public function testConstructorParsesMediaFile(): void
    {
        $files = [
            'media' => [
                'tmp_name' => '/tmp/upload.tmp',
                'type' => 'image/jpeg',
                'name' => 'image.jpg',
                'size' => 5000,
                'error' => UPLOAD_ERR_OK,
            ],
        ];

        $dto = new \Scriptlog\Dto\PostRequestDto([], $files);

        $this->assertInstanceOf(\Scriptlog\Dto\UploadedFileDto::class, $dto->mediaFile);
        $this->assertEquals('image.jpg', $dto->mediaFile->name);
    }

    public function testConstructorNullMediaFileWhenNoUpload(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto([], []);
        $this->assertNull($dto->mediaFile);
    }

    public function testIsProtectedReturnsTrue(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto(
            ['visibility' => 'protected'],
            []
        );
        $this->assertTrue($dto->isProtected());
    }

    public function testIsProtectedReturnsFalse(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto(
            ['visibility' => 'public'],
            []
        );
        $this->assertFalse($dto->isProtected());
    }

    public function testIsNewPostReturnsTrueWhenNoPostId(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto([], []);
        $this->assertTrue($dto->isNewPost());
    }

    public function testIsNewPostReturnsFalseWhenPostIdSet(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto(
            ['post_id' => '10'],
            []
        );
        $this->assertFalse($dto->isNewPost());
    }

    public function testIsSubmittedReturnsTrue(): void
    {
        $_POST['postFormSubmit'] = 'Save';
        $this->assertTrue(\Scriptlog\Dto\PostRequestDto::fromGlobals()->isSubmitted());
    }

    public function testIsSubmittedReturnsFalse(): void
    {
        $this->assertFalse(\Scriptlog\Dto\PostRequestDto::fromGlobals()->isSubmitted());
    }

    public function testFromGlobalsCapturesSuperglobals(): void
    {
        $_POST['post_title'] = 'Global Title';
        $_POST['post_content'] = 'Global content';

        $dto = \Scriptlog\Dto\PostRequestDto::fromGlobals();

        $this->assertEquals('Global Title', $dto->postTitle);
        $this->assertEquals('Global content', $dto->postContent);
    }

    public function testPostLocaleDefaultsToEnWhenNotSet(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto(
            [],
            []
        );
        $this->assertEquals('en', $dto->postLocale);
    }

    public function testPostLocalePreservesEmptyString(): void
    {
        $dto = new \Scriptlog\Dto\PostRequestDto(
            ['post_locale' => ''],
            []
        );
        $this->assertEquals('', $dto->postLocale);
    }
}
