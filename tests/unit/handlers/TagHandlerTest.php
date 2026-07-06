<?php

use PHPUnit\Framework\TestCase;

class TagHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../../src/lib/core/ThemeRenderer.php';
        require_once __DIR__ . '/../../../src/lib/handler/FrontRequestHandler.php';
        require_once __DIR__ . '/../../../src/lib/handler/TagHandler.php';
    }

    public function testHandlerImplementsInterface(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new TagHandler($renderer);
        $this->assertInstanceOf(FrontRequestHandler::class, $handler);
    }

    public function testHandlerHasHandleMethod(): void
    {
        $this->assertTrue(method_exists(TagHandler::class, 'handle'));
    }

    public function testHandlerAcceptsRendererInConstructor(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new TagHandler($renderer);
        $this->assertNotNull($handler);
    }

    public function testHandleWithValueCallsRender(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with('tag');
        $handler = new TagHandler($renderer);
        $handler->handle(['value' => 'cicero']);
    }
}
