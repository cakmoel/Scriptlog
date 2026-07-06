<?php

use PHPUnit\Framework\TestCase;

class ThemeRendererInterfaceTest extends TestCase
{
    private string $interfacePath;

    protected function setUp(): void
    {
        $this->interfacePath = __DIR__ . '/../../src/lib/core/ThemeRendererInterface.php';
    }

    public function testInterfaceExists(): void
    {
        require_once $this->interfacePath;
        $this->assertTrue(interface_exists('ThemeRendererInterface'));
    }

    public function testInterfaceHasExpectedMethods(): void
    {
        require_once $this->interfacePath;
        $reflection = new ReflectionClass('ThemeRendererInterface');
        $methods = array_map(function ($m) { return $m->getName(); }, $reflection->getMethods());
        $this->assertContains('render', $methods);
        $this->assertContains('renderHeader', $methods);
        $this->assertContains('renderContent', $methods);
        $this->assertContains('renderFooter', $methods);
        $this->assertContains('render404', $methods);
        $this->assertContains('getThemeDir', $methods);
    }

    public function testRenderSignature(): void
    {
        require_once $this->interfacePath;
        $method = new ReflectionMethod('ThemeRendererInterface', 'render');
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('template', $params[0]->getName());
        $this->assertEquals('statusCode', $params[1]->getName());
    }

    public function testGetThemeDirReturnType(): void
    {
        require_once $this->interfacePath;
        $method = new ReflectionMethod('ThemeRendererInterface', 'getThemeDir');
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('string', $returnType->getName());
    }

    public function testThemeRendererImplementsInterface(): void
    {
        require_once $this->interfacePath;
        require_once __DIR__ . '/../../src/lib/core/ThemeRenderer.php';
        $reflection = new ReflectionClass('ThemeRenderer');
        $this->assertTrue($reflection->implementsInterface('ThemeRendererInterface'));
    }
}
