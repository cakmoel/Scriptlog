<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;
use Scriptlog\Dto\PostRequestDto;

class PostRequestDtoTest extends TestCase
{
    public function testFromGlobals(): void
    {
        $_POST = [
            'post_title' => 'Test Title',
            'post_content' => 'Content here',
        ];
        $_FILES = [];

        $dto = PostRequestDto::fromGlobals();

        $this->assertInstanceOf(PostRequestDto::class, $dto);
        $this->assertSame('Test Title', $dto->postTitle);
        $this->assertSame('Content here', $dto->postContent);
    }

    public function testIsSubmitted(): void
    {
        $_POST['postFormSubmit'] = '1';
        $dto = new PostRequestDto([], []);
        $this->assertTrue($dto->isSubmitted());

        unset($_POST['postFormSubmit']);
        $this->assertFalse($dto->isSubmitted());
    }

    public function testIsProtected(): void
    {
        $dto = new PostRequestDto(['visibility' => 'protected'], []);
        $this->assertTrue($dto->isProtected());

        $dto2 = new PostRequestDto(['visibility' => 'public'], []);
        $this->assertFalse($dto2->isProtected());
    }

    public function testIsNewPost(): void
    {
        $dto = new PostRequestDto([], []);
        $this->assertTrue($dto->isNewPost());

        $dto2 = new PostRequestDto(['post_id' => '5'], []);
        $this->assertFalse($dto2->isNewPost());
    }

    public function testCatIdsArray(): void
    {
        $dto = new PostRequestDto(['catID' => '2'], []);
        $this->assertIsArray($dto->catIds);
        $this->assertContains('2', $dto->catIds);
    }

    public function testImageIdCastsToInt(): void
    {
        $dto = new PostRequestDto(['image_id' => '42'], []);
        $this->assertSame(42, $dto->imageId);
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_FILES = [];
    }
}
