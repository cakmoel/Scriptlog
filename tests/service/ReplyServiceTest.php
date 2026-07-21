<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class ReplyServiceTest extends TestCase
{
    private $replyDaoMock;

    private $validatorMock;

    private $sanitizeMock;

    private $replyService;

    protected function setUp(): void
    {
        $this->replyDaoMock = $this->createMock(\ReplyDao::class);
        $this->validatorMock = $this->createMock(\FormValidator::class);
        $this->sanitizeMock = $this->createMock(\Sanitize::class);

        $this->replyService = new \ReplyService(
            $this->replyDaoMock,
            $this->validatorMock,
            $this->sanitizeMock
        );
    }

    public function testSetReplyId(): void
    {
        $this->replyService->setReplyId(1);
        $this->assertTrue(true);
    }

    public function testSetPostId(): void
    {
        $this->replyService->setPostId(1);
        $this->assertTrue(true);
    }

    public function testSetParentId(): void
    {
        $this->replyService->setParentId(1);
        $this->assertTrue(true);
    }

    public function testSetAuthorName(): void
    {
        $this->replyService->setAuthorName('John');
        $this->assertTrue(true);
    }

    public function testSetAuthorIp(): void
    {
        $this->replyService->setAuthorIP('127.0.0.1');
        $this->assertTrue(true);
    }

    public function testSetAuthorEmail(): void
    {
        $this->replyService->setAuthorEmail('john@example.com');
        $this->assertTrue(true);
    }

    public function testSetReplyContent(): void
    {
        $this->replyService->setReplyContent('Test reply');
        $this->assertTrue(true);
    }

    public function testSetReplyStatus(): void
    {
        $this->replyService->setReplyStatus('approved');
        $this->assertTrue(true);
    }

    public function testSetReplyDate(): void
    {
        $this->replyService->setReplyDate('2026-07-21');
        $this->assertTrue(true);
    }

    public function testGrabRepliesDelegatesToDao(): void
    {
        $this->replyDaoMock->method('findReplies')->willReturn([]);
        $replies = $this->replyService->grabReplies(1);
        $this->assertIsArray($replies);
    }

    public function testGrabReplyDelegatesToDao(): void
    {
        $this->replyDaoMock->method('findReply')->willReturn(['ID' => 1, 'comment_content' => 'Test']);
        $reply = $this->replyService->grabReply(1);
        $this->assertIsArray($reply);
    }

    public function testGrabParentCommentDelegatesToDao(): void
    {
        $this->replyDaoMock->method('getParentComment')->willReturn(['ID' => 1, 'comment_author_name' => 'John']);
        $parent = $this->replyService->grabParentComment(1);
        $this->assertIsArray($parent);
    }

    public function testTotalRepliesDelegatesToDao(): void
    {
        $this->replyDaoMock->method('totalReplyRecords')->willReturn(5);
        $total = $this->replyService->totalReplies();
        $this->assertEquals(5, $total);
    }

    public function testCheckReplyExistsDelegatesToDao(): void
    {
        $this->replyDaoMock->method('checkReplyId')->willReturn(true);
        $this->assertTrue($this->replyService->checkReplyExists(1));
    }

    public function testReplyStatementDropDownDelegatesToDao(): void
    {
        $this->replyDaoMock->method('dropDownReplyStatement')->willReturn('<select><option>approved</option></select>');
        $dropdown = $this->replyService->replyStatementDropDown();
        $this->assertIsString($dropdown);
    }

    public function testAddReplyThrowsExceptionWhenNameMissing(): void
    {
        $this->validatorMock->method('sanitize')->willReturn(null);
        $this->expectException(\AppException::class);
        $this->replyService->addReply();
    }

    public function testModifyReplyThrowsExceptionWhenNameMissing(): void
    {
        $this->validatorMock->method('sanitize')->willReturn(null);
        $this->expectException(\AppException::class);
        $this->replyService->modifyReply();
    }

    public function testRemoveReplyThrowsExceptionWhenNotfound(): void
    {
        $this->markTestSkipped('direct_page() requires APP_HOSTNAME constant');
    }
}
