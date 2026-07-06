<?php

use PHPUnit\Framework\TestCase;

class HomeHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../../src/lib/core/ThemeRenderer.php';
        require_once __DIR__ . '/../../../src/lib/handler/FrontRequestHandler.php';
        require_once __DIR__ . '/../../../src/lib/handler/HomeHandler.php';
    }

    public function testHandlerImplementsInterface(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new HomeHandler($renderer);
        $this->assertInstanceOf(FrontRequestHandler::class, $handler);
    }

    public function testHandlerHasHandleMethod(): void
    {
        $this->assertTrue(method_exists(HomeHandler::class, 'handle'));
    }

    public function testHandlerAcceptsRendererInConstructor(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new HomeHandler($renderer);
        $this->assertNotNull($handler);
    }

    public function testHandleCallsRender(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with('home');
        $handler = new HomeHandler($renderer);
        $handler->handle([]);
    }
}
