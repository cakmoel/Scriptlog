<?php

use PHPUnit\Framework\TestCase;

class ThemeResolutionExceptionTest extends TestCase
{
    private string $classPath;

    protected function setUp(): void
    {
        $this->classPath = __DIR__ . '/../../src/lib/core/ThemeResolutionException.php';
    }

    public function testClassExists(): void
    {
        require_once $this->classPath;
        $this->assertTrue(class_exists('ThemeResolutionException'));
    }

    public function testExtendsRuntimeException(): void
    {
        require_once $this->classPath;
        $reflection = new ReflectionClass('ThemeResolutionException');
        $parent = $reflection->getParentClass();
        $this->assertNotNull($parent);
        $this->assertEquals('RuntimeException', $parent->getName());
    }

    public function testNotFoundFactoryMethod(): void
    {
        require_once $this->classPath;
        $exception = ThemeResolutionException::notFound('custom-theme');
        $this->assertInstanceOf(ThemeResolutionException::class, $exception);
        $this->assertStringContainsString('custom-theme', $exception->getMessage());
        $this->assertEquals(100, $exception->getCode());
    }

    public function testNotFoundWithFallback(): void
    {
        require_once $this->classPath;
        $exception = ThemeResolutionException::notFound('custom-theme', 'blog');
        $this->assertStringContainsString('custom-theme', $exception->getMessage());
        $this->assertStringContainsString('blog', $exception->getMessage());
    }

    public function testMissingTemplateFactoryMethod(): void
    {
        require_once $this->classPath;
        $exception = ThemeResolutionException::missingTemplate('header.php');
        $this->assertInstanceOf(ThemeResolutionException::class, $exception);
        $this->assertStringContainsString('header.php', $exception->getMessage());
        $this->assertEquals(101, $exception->getCode());
    }

    public function testMissingTemplateWithThemeDir(): void
    {
        require_once $this->classPath;
        $exception = ThemeResolutionException::missingTemplate('header.php', '/themes/blog/');
        $this->assertStringContainsString('/themes/blog/', $exception->getMessage());
    }

    public function testInvalidTemplateNameFactoryMethod(): void
    {
        require_once $this->classPath;
        $exception = ThemeResolutionException::invalidTemplateName('../../etc/passwd');
        $this->assertInstanceOf(ThemeResolutionException::class, $exception);
        $this->assertStringContainsString('../../etc/passwd', $exception->getMessage());
        $this->assertEquals(102, $exception->getCode());
    }
}
