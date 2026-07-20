<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class PostValidatorTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        $this->validator = new \Scriptlog\Validator\PostValidator();
        $_POST = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
    }

    public function testValidatePassesWithValidData(): void
    {
        $_POST['post_title'] = 'Valid Title';
        $_POST['post_content'] = 'Some content';
        $_POST['post_status'] = 'publish';
        $_POST['comment_status'] = 'open';
        $_POST['visibility'] = 'public';
        $_POST['post_date'] = '2026-07-20';

        $dto = new \Scriptlog\Dto\PostRequestDto($_POST, []);
        $result = $this->validator->validate($dto);

        $this->assertTrue($result->isValid());
    }

    public function testValidateFailsWhenTitleOrContentEmpty(): void
    {
        $_POST['post_status'] = 'publish';
        $_POST['comment_status'] = 'open';
        $_POST['visibility'] = 'public';

        $dto = new \Scriptlog\Dto\PostRequestDto($_POST, []);
        $result = $this->validator->validate($dto);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('required', implode(' ', $result->getErrors()));
    }

    public function testValidateFailsWhenStatusInvalid(): void
    {
        $_POST['post_title'] = 'Title';
        $_POST['post_content'] = 'Content';
        $_POST['post_status'] = 'invalid';
        $_POST['comment_status'] = 'open';
        $_POST['visibility'] = 'public';

        $dto = new \Scriptlog\Dto\PostRequestDto($_POST, []);
        $result = $this->validator->validate($dto);

        $this->assertFalse($result->isValid());
    }

    public function testValidateFailsWhenCommentStatusInvalid(): void
    {
        $_POST['post_title'] = 'Title';
        $_POST['post_content'] = 'Content';
        $_POST['post_status'] = 'publish';
        $_POST['comment_status'] = 'invalid';
        $_POST['visibility'] = 'public';

        $dto = new \Scriptlog\Dto\PostRequestDto($_POST, []);
        $result = $this->validator->validate($dto);

        $this->assertFalse($result->isValid());
    }

    public function testValidateFailsWhenVisibilityInvalid(): void
    {
        $_POST['post_title'] = 'Title';
        $_POST['post_content'] = 'Content';
        $_POST['post_status'] = 'publish';
        $_POST['comment_status'] = 'open';
        $_POST['visibility'] = 'invalid';

        $dto = new \Scriptlog\Dto\PostRequestDto($_POST, []);
        $result = $this->validator->validate($dto);

        $this->assertFalse($result->isValid());
    }

    public function testValidateAcceptsDraftStatus(): void
    {
        $_POST['post_title'] = 'Draft Title';
        $_POST['post_content'] = 'Draft content';
        $_POST['post_status'] = 'draft';
        $_POST['comment_status'] = 'closed';
        $_POST['visibility'] = 'private';

        $dto = new \Scriptlog\Dto\PostRequestDto($_POST, []);
        $result = $this->validator->validate($dto);

        $this->assertTrue($result->isValid());
    }

    public function testValidateAcceptsProtectedVisibility(): void
    {
        $_POST['post_title'] = 'Protected';
        $_POST['post_content'] = 'Secret content';
        $_POST['post_status'] = 'publish';
        $_POST['comment_status'] = 'open';
        $_POST['visibility'] = 'protected';

        $dto = new \Scriptlog\Dto\PostRequestDto($_POST, []);
        $result = $this->validator->validate($dto);

        $this->assertTrue($result->isValid());
    }

    public function testValidateFormSizeLimits(): void
    {
        $_POST['post_title'] = str_repeat('A', 201);
        $_POST['post_content'] = 'Content';
        $_POST['post_status'] = 'publish';
        $_POST['comment_status'] = 'open';
        $_POST['visibility'] = 'public';

        $dto = new \Scriptlog\Dto\PostRequestDto($_POST, []);
        $result = $this->validator->validate($dto);

        $this->assertFalse($result->isValid());
    }
}
