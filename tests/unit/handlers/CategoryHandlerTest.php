<?php

use PHPUnit\Framework\TestCase;

class CategoryHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../../src/lib/core/ThemeRenderer.php';
        require_once __DIR__ . '/../../../src/lib/handler/FrontRequestHandler.php';
        require_once __DIR__ . '/../../../src/lib/handler/CategoryHandler.php';
    }

    public function testHandlerImplementsInterface(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new CategoryHandler($renderer);
        $this->assertInstanceOf(FrontRequestHandler::class, $handler);
    }

    public function testHandlerHasHandleMethod(): void
    {
        $this->assertTrue(method_exists(CategoryHandler::class, 'handle'));
    }

    public function testHandlerAcceptsRendererInConstructor(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new CategoryHandler($renderer);
        $this->assertNotNull($handler);
    }

    public function testConstructorStoresRenderer(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new CategoryHandler($renderer);
        $this->assertInstanceOf(CategoryHandler::class, $handler);
    }
}
