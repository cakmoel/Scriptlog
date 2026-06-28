<?php
/**
 * MediaController Tests
 *
 * Phase 4.4: Controller Tests - MediaController (6 tests)
 * Tests for media library CRUD operations
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class MediaControllerTest extends TestCase
{
    private $mediaService;
    private $mediaController;

    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];

        $this->mediaService = $this->createMock(MediaService::class);
        $this->mediaController = new MediaController($this->mediaService);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    public function testControllerExtendsBaseApp(): void
    {
        $this->assertInstanceOf(BaseApp::class, $this->mediaController);
    }

    public function testControllerImplementsAppInterface(): void
    {
        $this->assertInstanceOf(AppInterface::class, $this->mediaController);
    }

    public function testListItemsDoesNotThrowException(): void
    {
        $this->mediaService->method('grabMedia')->willReturn([]);

        ob_start();
        $result = $this->mediaController->listItems();
        ob_end_clean();

        $this->assertNull($result);
    }

    public function testListItemsClearsSessionError(): void
    {
        $_SESSION['error'] = 'mediaNotFound';
        $this->mediaService->method('grabMedia')->willReturn([]);

        ob_start();
        $this->mediaController->listItems();
        ob_end_clean();

        $this->assertArrayNotHasKey('error', $_SESSION);
    }

    public function testListItemsClearsSessionStatus(): void
    {
        $_SESSION['status'] = 'mediaDeleted';
        $this->mediaService->method('grabMedia')->willReturn([]);

        ob_start();
        $this->mediaController->listItems();
        ob_end_clean();

        $this->assertArrayNotHasKey('status', $_SESSION);
    }

    public function testSetViewIsProtectedMethod(): void
    {
        $reflection = new ReflectionClass(MediaController::class);
        $method = $reflection->getMethod('setView');
        $this->assertTrue($method->isProtected());
    }
}
