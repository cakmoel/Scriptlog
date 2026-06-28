<?php
/**
 * View Tests
 *
 * Phase 3.8: Core Classes - View (6 tests)
 * Tests for template rendering, variable assignment, error handling
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    public function testConstructorSetsFileProperty(): void
    {
        $view = new View('admin', 'ui', 'posts', 'test-view');

        $reflection = new ReflectionClass(View::class);
        $file = $reflection->getProperty('file');
        $file->setAccessible(true);

        $this->assertEquals('test-view', $file->getValue($view));
    }

    public function testSetStoresData(): void
    {
        $view = new View('admin', 'ui', 'posts', 'test-view');
        $view->set('pageTitle', 'Test Title');

        $reflection = new ReflectionClass(View::class);
        $data = $reflection->getProperty('data');
        $data->setAccessible(true);

        $stored = $data->getValue($view);
        $this->assertArrayHasKey('pageTitle', $stored);
        $this->assertEquals('Test Title', $stored['pageTitle']);
    }

    public function testGetReturnsStoredValue(): void
    {
        $view = new View('admin', 'ui', 'posts', 'test-view');
        $view->set('key', 'value');

        $result = $view->get('key');
        $this->assertEquals('value', $result);
    }

    public function testGetReturnsNullForMissingKey(): void
    {
        $view = new View('admin', 'ui', 'posts', 'test-view');
        $result = $view->get('nonexistent');

        $this->assertNull($result);
    }

    public function testSetMultipleValues(): void
    {
        $view = new View('admin', 'ui', 'posts', 'test-view');
        $view->set('title', 'Hello');
        $view->set('content', 'World');
        $view->set('count', 42);

        $this->assertEquals('Hello', $view->get('title'));
        $this->assertEquals('World', $view->get('content'));
        $this->assertEquals(42, $view->get('count'));
    }

    public function testConstructorWithPublicEventPath(): void
    {
        $view = new View('public', 'blog', null, 'index');

        $reflection = new ReflectionClass(View::class);
        $dir = $reflection->getProperty('dir');
        $dir->setAccessible(true);

        $directory = $dir->getValue($view);
        $this->assertNotEmpty($directory);
    }
}
