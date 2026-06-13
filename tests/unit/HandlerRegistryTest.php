<?php

use PHPUnit\Framework\TestCase;

class HandlerRegistryTest extends TestCase
{
    private HandlerRegistry $registry;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../../lib/handler/HandlerRegistry.php';
        require_once __DIR__ . '/../../lib/handler/FrontRequestHandler.php';
        $this->registry = new HandlerRegistry();
    }

    public function testRegisterAndGetHandler(): void
    {
        $handler = $this->createMock(FrontRequestHandler::class);
        $this->registry->register('test', $handler);
        $this->assertTrue($this->registry->has('test'));
        $this->assertSame($handler, $this->registry->get('test'));
    }

    public function testHasReturnsFalseForUnknownKey(): void
    {
        $this->assertFalse($this->registry->has('nonexistent'));
    }

    public function testGetReturnsNullForUnknownKey(): void
    {
        $this->assertNull($this->registry->get('nonexistent'));
    }

    public function testRegisterOverwritesExisting(): void
    {
        $handler1 = $this->createMock(FrontRequestHandler::class);
        $handler2 = $this->createMock(FrontRequestHandler::class);
        $this->registry->register('key', $handler1);
        $this->registry->register('key', $handler2);
        $this->assertSame($handler2, $this->registry->get('key'));
    }

    public function testMultipleHandlers(): void
    {
        $handlerA = $this->createMock(FrontRequestHandler::class);
        $handlerB = $this->createMock(FrontRequestHandler::class);
        $this->registry->register('a', $handlerA);
        $this->registry->register('b', $handlerB);
        $this->assertTrue($this->registry->has('a'));
        $this->assertTrue($this->registry->has('b'));
        $this->assertSame($handlerA, $this->registry->get('a'));
        $this->assertSame($handlerB, $this->registry->get('b'));
    }
}
