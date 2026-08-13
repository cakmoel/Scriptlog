<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

/**
 * PostValidatorScheduledStatusTest
 *
 * Verifies that PostValidator accepts the 'scheduled' status introduced with
 * the scheduled-posting feature.
 *
 * @category Tests
 * @version  1.0
 */
class PostValidatorScheduledStatusTest extends TestCase
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

    public function testValidateAcceptsScheduledStatus(): void
    {
        $_POST['post_title'] = 'Scheduled Post';
        $_POST['post_content'] = 'Content for a scheduled post';
        $_POST['post_status'] = 'scheduled';
        $_POST['comment_status'] = 'closed';
        $_POST['visibility'] = 'private';
        $_POST['post_date'] = '2026-09-01 10:00:00';

        $dto = new \Scriptlog\Dto\PostRequestDto($_POST, []);
        $result = $this->validator->validate($dto);

        $this->assertTrue($result->isValid());
        $this->assertSame('scheduled', $dto->postStatus);
    }

    public function testValidateStillRejectsUnknownStatus(): void
    {
        $_POST['post_title'] = 'Title';
        $_POST['post_content'] = 'Content';
        $_POST['post_status'] = 'future';
        $_POST['comment_status'] = 'open';
        $_POST['visibility'] = 'public';

        $dto = new \Scriptlog\Dto\PostRequestDto($_POST, []);
        $result = $this->validator->validate($dto);

        $this->assertFalse($result->isValid());
    }
}
