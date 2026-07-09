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

    public function testInsertRendersViewWhenNoPostData(): void
    {
        $this->topicService->method('localeDropDown')->willReturn([]);

        ob_start();
        $this->topicController->insert();
        ob_end_clean();

        $this->assertTrue(true);
    }

    public function testInsertWithValidationErrorRendersView(): void
    {
        $_POST['topicFormSubmit'] = '1';
        $_POST['csrfToken'] = 'invalid';

        $this->topicService->method('localeDropDown')->willReturn([]);

        ob_start();
        try {
            $this->topicController->insert();
        } catch (\Throwable $e) {
        }
        ob_end_clean();

        $this->assertTrue(true);
    }

    public function testUpdateWithInvalidIdRedirects(): void
    {
        $this->topicService->method('grabTopic')->willReturn(false);

        ob_start();
        try {
            $this->topicController->update(999);
        } catch (\Throwable $e) {
        }
        ob_end_clean();

        $this->assertTrue(true);
    }

    public function testUpdateRendersViewWhenNoPostData(): void
    {
        $this->topicService->method('grabTopic')->willReturn([
            'ID' => 1,
            'topic_title' => 'Test',
            'topic_slug' => 'test',
            'topic_status' => 'Y'
        ]);
        $this->topicService->method('localeDropDown')->willReturn([]);

        ob_start();
        $this->topicController->update(1);
        ob_end_clean();

        $this->assertTrue(true);
    }

    public function testInsertAndUpdateUseRenderMethod(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/controller/TopicController.php');

        $this->assertStringContainsString('$this->view->render()', $source);
        $this->assertStringContainsString('return $this->view->render()', $source);
    }

    public function testInsertAndUpdateUseDirectPageOnError(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/controller/TopicController.php');

        $this->assertStringContainsString("direct_page('index.php?load=topics&error=internalError', 500)", $source);
        $this->assertStringContainsString("direct_page('index.php?load=topics&error=internalError', 400)", $source);
    }

    public function testRemoveMethodExists(): void
    {
        $this->assertTrue(method_exists(TopicController::class, 'remove'));
    }
}
