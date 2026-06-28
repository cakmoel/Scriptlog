<?php
/**
 * TopicController Tests
 *
 * Phase 4.5: Controller Tests - TopicController (6 tests)
 * Tests for category/topic CRUD operations
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class TopicControllerTest extends TestCase
{
    private $topicService;
    private $topicController;

    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];

        $this->topicService = $this->createMock(TopicService::class);
        $this->topicController = new TopicController($this->topicService);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    public function testControllerExtendsBaseApp(): void
    {
        $this->assertInstanceOf(BaseApp::class, $this->topicController);
    }

    public function testControllerImplementsAppInterface(): void
    {
        $this->assertInstanceOf(AppInterface::class, $this->topicController);
    }

    public function testListItemsDoesNotThrowException(): void
    {
        $this->topicService->method('grabTopics')->willReturn([]);

        ob_start();
        $result = $this->topicController->listItems();
        ob_end_clean();

        $this->assertNull($result);
    }

    public function testListItemsClearsSessionError(): void
    {
        $_SESSION['error'] = 'topicNotFound';
        $this->topicService->method('grabTopics')->willReturn([]);

        ob_start();
        $this->topicController->listItems();
        ob_end_clean();

        $this->assertArrayNotHasKey('error', $_SESSION);
    }

    public function testListItemsClearsSessionStatus(): void
    {
        $_SESSION['status'] = 'topicAdded';
        $this->topicService->method('grabTopics')->willReturn([]);

        ob_start();
        $this->topicController->listItems();
        ob_end_clean();

        $this->assertArrayNotHasKey('status', $_SESSION);
    }
}
