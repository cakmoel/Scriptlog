<?php

use PHPUnit\Framework\TestCase;

class PageHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../../src/lib/core/ThemeRenderer.php';
        require_once __DIR__ . '/../../../src/lib/handler/FrontRequestHandler.php';
        require_once __DIR__ . '/../../../src/lib/handler/PageHandler.php';
    }

    public function testHandlerImplementsInterface(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new PageHandler($renderer);
        $this->assertInstanceOf(FrontRequestHandler::class, $handler);
    }

    public function testHandlerHasHandleMethod(): void
    {
        $this->assertTrue(method_exists(PageHandler::class, 'handle'));
    }

    public function testHandlerAcceptsRendererInConstructor(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new PageHandler($renderer);
        $this->assertNotNull($handler);
    }

    public function testConstructorStoresRenderer(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new PageHandler($renderer);
        $this->assertInstanceOf(PageHandler::class, $handler);
    }
}
