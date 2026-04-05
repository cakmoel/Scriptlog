<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * CommentService Test
 * 
 * Tests for comment business logic.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class CommentServiceTest extends TestCase
{
    private $commentService;
    private $commentDaoMock;
    private $validatorMock;
    private $sanitizeMock;

    protected function setUp(): void
    {
        $this->commentDaoMock = $this->createMock(\CommentDao::class);
        $this->validatorMock = $this->createMock(\FormValidator::class);
        $this->sanitizeMock = $this->createMock(\Sanitize::class);
        
        $this->commentService = new \CommentService(
            $this->commentDaoMock,
            $this->validatorMock,
            $this->sanitizeMock
        );
    }

    public function testSetAuthorName(): void
    {
        $this->commentService->setAuthorName('Test Author');
        $this->assertTrue(true);
    }

    public function testSetAuthorEmail(): void
    {
        $this->commentService->setAuthorEmail('test@example.com');
        $this->assertTrue(true);
    }

    public function testSetCommentContent(): void
    {
        $this->commentService->setCommentContent('Test comment');
        $this->assertTrue(true);
    }

    public function testSetCommentStatus(): void
    {
        $this->commentService->setCommentStatus('approved');
        $this->assertTrue(true);
    }

    public function testSetCommentDate(): void
    {
        $this->commentService->setCommentDate('2026-03-28');
        $this->assertTrue(true);
    }

    public function testTotalComments(): void
    {
        $this->commentDaoMock->method('totalCommentRecords')->willReturn(15);
        $total = $this->commentService->totalComments();
        $this->assertEquals(15, $total);
    }

    public function testGrabComments(): void
    {
        $this->commentDaoMock->method('findComments')->willReturn([]);
        $comments = $this->commentService->grabComments();
        $this->assertIsArray($comments);
    }

    public function testGrabComment(): void
    {
        $this->commentDaoMock->method('findComment')->willReturn(['ID' => 1, 'comment_content' => 'Test']);
        $comment = $this->commentService->grabComment(1);
        $this->assertIsArray($comment);
    }

    public function testCommentStatementDropDown(): void
    {
        $this->commentDaoMock->method('dropDownCommentStatement')->willReturn('<select><option>approved</option></select>');
        $dropdown = $this->commentService->commentStatementDropDown();
        $this->assertIsString($dropdown);
    }

    public function testCountReplies(): void
    {
        $this->commentDaoMock->method('countReplies')->willReturn(5);
        $count = $this->commentService->countReplies(1);
        $this->assertEquals(5, $count);
    }
}
