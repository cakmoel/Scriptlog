<?php

use PHPUnit\Framework\TestCase;

class PrivacyHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../../src/lib/core/ThemeRenderer.php';
        require_once __DIR__ . '/../../../src/lib/handler/FrontRequestHandler.php';
        require_once __DIR__ . '/../../../src/lib/handler/PrivacyHandler.php';
    }

    public function testHandlerImplementsInterface(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new PrivacyHandler($renderer);
        $this->assertInstanceOf(FrontRequestHandler::class, $handler);
    }

    public function testHandlerHasHandleMethod(): void
    {
        $this->assertTrue(method_exists(PrivacyHandler::class, 'handle'));
    }

    public function testHandlerAcceptsRendererInConstructor(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $handler = new PrivacyHandler($renderer);
        $this->assertNotNull($handler);
    }

    public function testHandleCallsRender(): void
    {
        $renderer = $this->createMock(ThemeRendererInterface::class);
        $renderer->expects($this->once())
            ->method('render')
            ->with('privacy');
        $handler = new PrivacyHandler($renderer);
        $handler->handle([]);
    }
}
