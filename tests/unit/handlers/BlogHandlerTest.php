<?php

use PHPUnit\Framework\TestCase;

class BlogHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../../src/lib/core/ThemeRenderer.php';
        require_once __DIR__ . '/../../../src/lib/handler/FrontRequestHandler.php';
        require_once __DIR__ . '/../../../src/lib/handler/BlogHandler.php';
    }

    public function testHandlerImplementsInterface(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new BlogHandler($renderer);
        $this->assertInstanceOf(FrontRequestHandler::class, $handler);
    }

    public function testHandlerHasHandleMethod(): void
    {
        $this->assertTrue(method_exists(BlogHandler::class, 'handle'));
    }

    public function testHandlerAcceptsRendererInConstructor(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new BlogHandler($renderer);
        $this->assertNotNull($handler);
    }

    public function testHandleCallsRender(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with('blog');
        $handler = new BlogHandler($renderer);
        $handler->handle([]);
    }
}
