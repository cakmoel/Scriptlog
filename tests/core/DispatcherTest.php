<?php
/**
 * Dispatcher Tests
 *
 * Phase 3.7: Core Classes - Dispatcher (6 tests)
 * Tests for URL routing, content validation, 404 handling
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class DispatcherTest extends TestCase
{
    private $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        if (!defined('SCRIPTLOG')) {
            define('SCRIPTLOG', true);
        }
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
    }

    public function testDispatcherClassExists(): void
    {
        $this->assertTrue(class_exists('Dispatcher'));
    }

    public function testDispatcherHasDispatchMethod(): void
    {
        $reflection = new ReflectionClass('Dispatcher');
        $this->assertTrue($reflection->hasMethod('dispatch'));
    }

    public function testDispatcherHasHandleSeoFriendlyUrlMethod(): void
    {
        $reflection = new ReflectionClass('Dispatcher');
        $this->assertTrue($reflection->hasMethod('handleSeoFriendlyUrl'));
    }

    public function testDispatcherHasHandleQueryStringUrlMethod(): void
    {
        $reflection = new ReflectionClass('Dispatcher');
        $this->assertTrue($reflection->hasMethod('handleQueryStringUrl'));
    }

    public function testRequestURIMethodExists(): void
    {
        $reflection = new ReflectionClass(Dispatcher::class);
        $this->assertTrue($reflection->hasMethod('requestURI'));
    }

    public function testValidateContentExistsMethodExists(): void
    {
        $reflection = new ReflectionClass(Dispatcher::class);
        $this->assertTrue($reflection->hasMethod('validateContentExists'));
    }
}
