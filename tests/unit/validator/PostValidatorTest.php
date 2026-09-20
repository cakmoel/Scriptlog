<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;
use Scriptlog\Dto\PostRequestDto;
use Scriptlog\Validator\PostValidator;
use Scriptlog\Validator\ValidationResult;

class PostValidatorTest extends TestCase
{
    public function testEmptyTitleFails(): void
    {
        $dto = new PostRequestDto([
            'post_title' => '',
            'post_content' => 'Some content',
            'post_status' => 'publish',
            'visibility' => 'public',
            'comment_status' => 'open',
        ], []);
        $validator = new PostValidator();
        $result = $validator->validate($dto);
        $this->assertFalse($result->isValid());
    }

    public function testEmptyContentFails(): void
    {
        $dto = new PostRequestDto([
            'post_title' => 'A Title',
            'post_content' => '',
            'post_status' => 'publish',
            'visibility' => 'public',
            'comment_status' => 'open',
        ], []);
        $validator = new PostValidator();
        $result = $validator->validate($dto);
        $this->assertFalse($result->isValid());
    }

    public function testInvalidStatusFails(): void
    {
        $dto = new PostRequestDto([
            'post_title' => 'Title',
            'post_content' => 'Content',
            'post_status' => 'invalid_status',
            'visibility' => 'public',
            'comment_status' => 'open',
        ], []);
        $validator = new PostValidator();
        $result = $validator->validate($dto);
        $this->assertFalse($result->isValid());
        $this->assertContains(MESSAGE_INVALID_SELECTBOX, $result->getErrors());
    }

    public function testInvalidVisibilityFails(): void
    {
        $dto = new PostRequestDto([
            'post_title' => 'Title',
            'post_content' => 'Content',
            'post_status' => 'publish',
            'visibility' => 'top_secret',
            'comment_status' => 'open',
        ], []);
        $validator = new PostValidator();
        $result = $validator->validate($dto);
        $this->assertFalse($result->isValid());
        $this->assertContains(MESSAGE_INVALID_SELECTBOX, $result->getErrors());
    }

    public function testValidPostPasses(): void
    {
        $dto = new PostRequestDto([
            'post_title' => 'Valid Title',
            'post_content' => 'Valid content here',
            'post_status' => 'publish',
            'visibility' => 'public',
            'comment_status' => 'open',
        ], []);
        $validator = new PostValidator();
        $result = $validator->validate($dto);
        $this->assertTrue($result->isValid());
    }
}
