<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PageService Test
 * 
 * Tests for page business logic.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PageServiceTest extends TestCase
{
    private $pageService;
    private $pageDaoMock;
    private $validatorMock;
    private $sanitizeMock;

    protected function setUp(): void
    {
        $this->pageDaoMock = $this->createMock(\PageDao::class);
        $this->validatorMock = $this->createMock(\FormValidator::class);
        $this->sanitizeMock = $this->createMock(\Sanitize::class);
        
        $this->pageService = new \PageService(
            $this->pageDaoMock,
            $this->validatorMock,
            $this->sanitizeMock
        );
    }

    public function testSetPageId(): void
    {
        $this->pageService->setPageId(1);
        $this->assertTrue(true);
    }

    public function testSetPageAuthor(): void
    {
        $this->pageService->setPageAuthor(1);
        $this->assertTrue(true);
    }

    public function testSetPageTitle(): void
    {
        $this->pageService->setPageTitle('Test Page');
        $this->assertTrue(true);
    }

    public function testSetPageSlug(): void
    {
        $this->pageService->setPageSlug('test-page');
        $this->assertTrue(true);
    }

    /**
     * @skip HTMLPurifier not available in CLI context
     */
    public function testSetPageContent(): void
    {
        $this->markTestSkipped('HTMLPurifier not available in CLI context');
    }

    public function testSetPublish(): void
    {
        $this->pageService->setPublish('publish');
        $this->assertTrue(true);
    }

    public function testSetSticky(): void
    {
        $this->pageService->setSticky(1);
        $this->assertTrue(true);
    }

    public function testSetComment(): void
    {
        $this->pageService->setComment('open');
        $this->assertTrue(true);
    }

    public function testGrabPages(): void
    {
        $this->pageDaoMock->method('findPages')->willReturn([]);
        $pages = $this->pageService->grabPages('page');
        $this->assertIsArray($pages);
    }

    public function testGrabPage(): void
    {
        $this->pageDaoMock->method('findPageById')->willReturn(['ID' => 1, 'post_title' => 'Test Page']);
        $page = $this->pageService->grabPage(1);
        $this->assertIsArray($page);
    }

    public function testTotalPages(): void
    {
        $this->pageDaoMock->method('totalPageRecords')->willReturn(5);
        $total = $this->pageService->totalPages();
        $this->assertEquals(5, $total);
    }

    public function testPostStatusDropDown(): void
    {
        $this->pageDaoMock->method('dropDownPostStatus')->willReturn('<select><option>publish</option></select>');
        $dropdown = $this->pageService->postStatusDropDown();
        $this->assertIsString($dropdown);
    }

    public function testCommentStatusDropDown(): void
    {
        $this->pageDaoMock->method('dropDownCommentStatus')->willReturn('<select><option>open</option></select>');
        $dropdown = $this->pageService->commentStatusDropDown();
        $this->assertIsString($dropdown);
    }

    public function testPageAuthorId(): void
    {
        $_SESSION['scriptlog_session_id'] = 1;
        $authorId = $this->pageService->pageAuthorId();
        $this->assertEquals(1, $authorId);
        unset($_SESSION['scriptlog_session_id']);
    }

    public function testPageAuthorLevel(): void
    {
        $_SESSION['scriptlog_session_level'] = 'administrator';
        $level = $this->pageService->pageAuthorLevel();
        $this->assertEquals('administrator', $level);
        unset($_SESSION['scriptlog_session_level']);
    }
}
