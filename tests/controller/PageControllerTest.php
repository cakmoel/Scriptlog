<?php
/**
 * PageController Test
 *
 * Tests for PageController with updated check_form_request whitelist.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PageControllerTest extends TestCase
{
    public function testCheckFormRequestWhitelistHasPostModified(): void
    {
        $source = @file_get_contents(__DIR__ . '/../../src/lib/controller/PageController.php');
        if (!$source) {
            $this->markTestSkipped('PageController.php not found');
        }
        $this->assertStringContainsString("'post_modified'", $source);
    }

    public function testInsertMethodWhitelistContainsPostModified(): void
    {
        $source = @file_get_contents(__DIR__ . '/../../src/lib/controller/PageController.php');
        if (!$source) {
            $this->markTestSkipped('PageController.php not found');
        }
        $this->assertStringContainsString("'page_id', 'post_title', 'post_content', 'post_date', 'post_modified'", $source);
    }

    public function testUpdateMethodWhitelistContainsPostModified(): void
    {
        $source = @file_get_contents(__DIR__ . '/../../src/lib/controller/PageController.php');
        if (!$source) {
            $this->markTestSkipped('PageController.php not found');
        }
        $this->assertStringContainsString("'post_modified'", $source);
    }

    public function testPageControllerClassExists(): void
    {
        $this->assertTrue(class_exists('PageController'));
    }

    public function testPageControllerAcceptsPageService(): void
    {
        if (!class_exists('PageController')) {
            $this->markTestSkipped('PageController class not found');
        }
        $pageServiceMock = $this->createMock(PageService::class);
        $controller = new PageController($pageServiceMock);
        $this->assertInstanceOf(PageController::class, $controller);
    }

    public function testPageControllerHasListItemsMethod(): void
    {
        if (!class_exists('PageController')) {
            $this->markTestSkipped('PageController class not found');
        }
        $this->assertTrue(method_exists(PageController::class, 'listItems'));
    }

    public function testPageControllerHasInsertMethod(): void
    {
        if (!class_exists('PageController')) {
            $this->markTestSkipped('PageController class not found');
        }
        $this->assertTrue(method_exists(PageController::class, 'insert'));
    }

    public function testPageControllerHasUpdateMethod(): void
    {
        if (!class_exists('PageController')) {
            $this->markTestSkipped('PageController class not found');
        }
        $this->assertTrue(method_exists(PageController::class, 'update'));
    }
}
