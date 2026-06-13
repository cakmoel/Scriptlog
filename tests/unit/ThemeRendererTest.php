<?php

use PHPUnit\Framework\TestCase;

class ThemeRendererTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../lib/core/ThemeRenderer.php';
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('ThemeRenderer'));
    }

    public function testClassImplementsExpectedMethods(): void
    {
        $reflection = new ReflectionClass('ThemeRenderer');
        $methods = array_map(function ($m) { return $m->getName(); }, $reflection->getMethods());
        $this->assertContains('render', $methods);
        $this->assertContains('renderHeader', $methods);
        $this->assertContains('renderContent', $methods);
        $this->assertContains('renderFooter', $methods);
        $this->assertContains('render404', $methods);
    }

    public function testRenderContentUsesBasename(): void
    {
        $reflection = new ReflectionMethod('ThemeRenderer', 'renderContent');
        $params = $reflection->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('template', $params[0]->getName());
    }

    public function testClassHasStringThemeDirProperty(): void
    {
        $property = new ReflectionProperty('ThemeRenderer', 'themeDir');
        $this->assertTrue($property->isPrivate());
    }

    public function testRenderMethodAcceptsTwoParameters(): void
    {
        $method = new ReflectionMethod('ThemeRenderer', 'render');
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('template', $params[0]->getName());
        $this->assertEquals('statusCode', $params[1]->getName());
    }
}
