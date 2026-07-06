<?php

use PHPUnit\Framework\TestCase;

class ArchiveHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../../src/lib/core/ThemeRenderer.php';
        require_once __DIR__ . '/../../../src/lib/handler/FrontRequestHandler.php';
        require_once __DIR__ . '/../../../src/lib/handler/ArchiveHandler.php';
    }

    public function testHandlerImplementsInterface(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new ArchiveHandler($renderer);
        $this->assertInstanceOf(FrontRequestHandler::class, $handler);
    }

    public function testHandlerHasHandleMethod(): void
    {
        $this->assertTrue(method_exists(ArchiveHandler::class, 'handle'));
    }

    public function testHandlerAcceptsRendererInConstructor(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new ArchiveHandler($renderer);
        $this->assertNotNull($handler);
    }

    public function testHandleWithValueCallsRender(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with('archive');
        $handler = new ArchiveHandler($renderer);
        $handler->handle(['value' => '03/2025']);
    }
}
