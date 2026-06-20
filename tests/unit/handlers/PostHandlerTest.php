<?php

use PHPUnit\Framework\TestCase;

class PostHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../../src/lib/core/ThemeRenderer.php';
        require_once __DIR__ . '/../../../src/lib/handler/FrontRequestHandler.php';
        require_once __DIR__ . '/../../../src/lib/handler/PostHandler.php';
    }

    public function testHandlerImplementsInterface(): void
    {
        $renderer = $this->createMock(ThemeRenderer::class);
        $handler = new PostHandler($renderer);
        $this->assertInstanceOf(FrontRequestHandler::class, $handler);
    }

    public function testHandlerHasHandleMethod(): void
    {
        $this->assertTrue(method_exists(PostHandler::class, 'handle'));
    }

    public function testHandlerAcceptsRendererInConstructor(): void
    {
        $renderer = $this->createMock(ThemeRenderer::class);
        $handler = new PostHandler($renderer);
        $this->assertNotNull($handler);
    }
}
