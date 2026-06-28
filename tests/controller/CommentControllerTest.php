<?php
/**
 * CommentController Tests
 *
 * Phase 4.3: Controller Tests - CommentController (6 tests)
 * Tests for comment CRUD operations
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class CommentControllerTest extends TestCase
{
    private $commentService;
    private $commentController;

    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];

        $this->commentService = $this->createMock(CommentService::class);
        $this->commentController = new CommentController($this->commentService);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    public function testControllerExtendsBaseApp(): void
    {
        $this->assertInstanceOf(BaseApp::class, $this->commentController);
    }

    public function testControllerImplementsAppInterface(): void
    {
        $this->assertInstanceOf(AppInterface::class, $this->commentController);
    }

    public function testListItemsDoesNotThrowException(): void
    {
        $this->commentService->method('grabComments')->willReturn([]);

        ob_start();
        $result = $this->commentController->listItems();
        ob_end_clean();

        $this->assertNull($result);
    }

    public function testListItemsClearsSessionError(): void
    {
        $_SESSION['error'] = 'commentNotFound';
        $this->commentService->method('grabComments')->willReturn([]);

        ob_start();
        $this->commentController->listItems();
        ob_end_clean();

        $this->assertArrayNotHasKey('error', $_SESSION);
    }

    public function testListItemsClearsSessionStatus(): void
    {
        $_SESSION['status'] = 'commentApproved';
        $this->commentService->method('grabComments')->willReturn([]);

        ob_start();
        $this->commentController->listItems();
        ob_end_clean();

        $this->assertArrayNotHasKey('status', $_SESSION);
    }

    public function testSetViewIsProtectedMethod(): void
    {
        $reflection = new ReflectionClass(CommentController::class);
        $method = $reflection->getMethod('setView');
        $this->assertTrue($method->isProtected());
    }
}
