<?php

use PHPUnit\Framework\TestCase;

class ThemeRendererTest extends TestCase
{
    private string $themesRoot;

    private string $fallbackDir;

    private string $tmpDir;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../../lib/core/ThemeRendererInterface.php';
        require_once __DIR__ . '/../../lib/core/ThemeResolutionException.php';
        require_once __DIR__ . '/../../lib/core/ThemeRenderer.php';

        $this->themesRoot = APP_ROOT . APP_THEME;
        $this->fallbackDir = $this->themesRoot . 'blog' . DIRECTORY_SEPARATOR;

        $this->tmpDir = sys_get_temp_dir() . '/theme-test-' . uniqid() . '/';
        mkdir($this->tmpDir, 0777, true);
        file_put_contents($this->tmpDir . 'header.php', '<header></header>');
        file_put_contents($this->tmpDir . 'footer.php', '<footer></footer>');
        file_put_contents($this->tmpDir . '404.php', '<h1>404</h1>');
        file_put_contents($this->tmpDir . 'home.php', '<div>home</div>');
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '*'));
        rmdir($this->tmpDir);
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('ThemeRenderer'));
    }

    public function testImplementsInterface(): void
    {
        $reflection = new ReflectionClass('ThemeRenderer');
        $this->assertTrue($reflection->implementsInterface('ThemeRendererInterface'));
    }

    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists('ThemeRendererInterface'));
    }

    public function testInterfaceDeclaresExpectedMethods(): void
    {
        $methods = (new ReflectionClass('ThemeRendererInterface'))->getMethods();
        $methodNames = array_map(function ($m) { return $m->getName(); }, $methods);
        $this->assertContains('render', $methodNames);
        $this->assertContains('renderHeader', $methodNames);
        $this->assertContains('renderContent', $methodNames);
        $this->assertContains('renderFooter', $methodNames);
        $this->assertContains('render404', $methodNames);
        $this->assertContains('getThemeDir', $methodNames);
    }

    public function testExceptionClassExists(): void
    {
        $this->assertTrue(class_exists('ThemeResolutionException'));
    }

    public function testExceptionExtendsRuntimeException(): void
    {
        $reflection = new ReflectionClass('ThemeResolutionException');
        $this->assertEquals(RuntimeException::class, $reflection->getParentClass()->getName());
    }

    public function testConstructorAcceptsDependencies(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        $this->assertNotNull($renderer);
    }

    public function testConstructorFallsBackOnMissingTheme(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'nonexistent-theme');
        $this->assertNotNull($renderer);
    }

    public function testRenderMethodAcceptsTwoParameters(): void
    {
        $method = new ReflectionMethod('ThemeRenderer', 'render');
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertEquals('template', $params[0]->getName());
        $this->assertEquals('statusCode', $params[1]->getName());
    }

    public function testRenderContentUsesBasename(): void
    {
        $method = new ReflectionMethod('ThemeRenderer', 'renderContent');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('template', $params[0]->getName());
    }

    public function testClassHasStringThemeDirProperty(): void
    {
        $property = new ReflectionProperty('ThemeRenderer', 'themeDir');
        $this->assertTrue($property->isPrivate());
    }

    public function testBlogFallbackThemeHasHeaderFile(): void
    {
        $this->assertFileExists(
            $this->fallbackDir . 'header.php',
            'Blog theme header must exist for ThemeRenderer fallback'
        );
    }

    public function testBlogFallbackThemeHasRequiredTemplates(): void
    {
        $required = ['header.php', 'footer.php', '404.php'];
        foreach ($required as $file) {
            $this->assertFileExists(
                $this->fallbackDir . $file,
                "Missing fallback template: $file"
            );
        }
    }

    public function testAcceptInvalidThemeNameUsesFallback(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, '../../etc/passwd');
        $property = new ReflectionProperty('ThemeRenderer', 'themeDir');
        $property->setAccessible(true);
        $this->assertStringContainsString('blog', $property->getValue($renderer));
    }

    public function testConstructorSanitizesThemeName(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog/../../etc');
        $property = new ReflectionProperty('ThemeRenderer', 'themeDir');
        $property->setAccessible(true);
        $value = $property->getValue($renderer);
        $this->assertStringNotContainsString('etc', $value);
    }

    public function testErrorLoggerIsCalledOnMissingTemplate(): void
    {
        $logged = '';
        $logger = function ($msg) use (&$logged) {
            $logged = $msg;
        };
        $renderer = new ThemeRenderer($this->themesRoot, 'blog', $logger);
        $property = new ReflectionProperty('ThemeRenderer', 'themeDir');
        $property->setAccessible(true);
        $property->setValue($renderer, '/tmp/nonexistent/');
        ob_start();
        $renderer->renderContent('test');
        ob_end_clean();
        $this->assertStringContainsString('not found', $logged);
    }

    public function testDefaultErrorLoggerIsNull(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        $reflection = new ReflectionProperty('ThemeRenderer', 'errorLogger');
        $reflection->setAccessible(true);
        $this->assertNull($reflection->getValue($renderer));
    }

    public function testRenderHeaderUsesThemeDir(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        $property = new ReflectionProperty('ThemeRenderer', 'themeDir');
        $property->setAccessible(true);
        $property->setValue($renderer, $this->tmpDir);
        ob_start();
        $renderer->renderHeader();
        $output = ob_get_clean();
        $this->assertStringContainsString('<header>', $output);
    }

    public function testRenderFooterUsesThemeDir(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        $property = new ReflectionProperty('ThemeRenderer', 'themeDir');
        $property->setAccessible(true);
        $property->setValue($renderer, $this->tmpDir);
        ob_start();
        $renderer->renderFooter();
        $output = ob_get_clean();
        $this->assertStringContainsString('<footer>', $output);
    }

    public function testRender404UsesThemeDir(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        $property = new ReflectionProperty('ThemeRenderer', 'themeDir');
        $property->setAccessible(true);
        $property->setValue($renderer, $this->tmpDir);
        ob_start();
        @$renderer->render404();
        $output = ob_get_clean();
        $this->assertStringContainsString('404', $output);
    }

    public function testRenderCallsHeaderContentFooter(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        $property = new ReflectionProperty('ThemeRenderer', 'themeDir');
        $property->setAccessible(true);
        $property->setValue($renderer, $this->tmpDir);
        ob_start();
        @$renderer->render('home');
        $output = ob_get_clean();
        $this->assertStringContainsString('<header>', $output);
        $this->assertStringContainsString('<div>home</div>', $output);
        $this->assertStringContainsString('<footer>', $output);
    }

    public function testRenderWithCustomStatusCodeDoesNotError(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        $property = new ReflectionProperty('ThemeRenderer', 'themeDir');
        $property->setAccessible(true);
        $property->setValue($renderer, $this->tmpDir);
        ob_start();
        @$renderer->render('home', 404);
        $output = ob_get_clean();
        $this->assertStringContainsString('<div>home</div>', $output);
    }

    public function testGetThemeDirReturnsStringEndingWithSeparator(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        $dir = $renderer->getThemeDir();
        $this->assertStringEndsWith(DIRECTORY_SEPARATOR, $dir);
    }

    public function testGetThemeDirResolvesFallbackForInvalidTheme(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'nonexistent-theme');
        $dir = $renderer->getThemeDir();
        $this->assertStringEndsWith('blog' . DIRECTORY_SEPARATOR, $dir);
    }

    public function testGetThemeDirExistsOnFilesystem(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        $dir = $renderer->getThemeDir();
        $this->assertDirectoryExists($dir);
    }

    public function testGetThemeDirReturnsCorrectPathForTmpTheme(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        $property = new ReflectionProperty('ThemeRenderer', 'themeDir');
        $property->setAccessible(true);
        $property->setValue($renderer, $this->tmpDir);
        $this->assertEquals($this->tmpDir, $renderer->getThemeDir());
    }

    public function testExceptionNotFoundFactory(): void
    {
        $e = ThemeResolutionException::notFound('missing-theme', 'blog');
        $this->assertInstanceOf(ThemeResolutionException::class, $e);
        $this->assertStringContainsString('missing-theme', $e->getMessage());
        $this->assertStringContainsString('blog', $e->getMessage());
    }

    public function testExceptionMissingTemplateFactory(): void
    {
        $e = ThemeResolutionException::missingTemplate('header.php', '/themes/blog/');
        $this->assertInstanceOf(ThemeResolutionException::class, $e);
        $this->assertStringContainsString('header.php', $e->getMessage());
    }

    public function testExceptionInvalidTemplateNameFactory(): void
    {
        $e = ThemeResolutionException::invalidTemplateName('../../etc/passwd');
        $this->assertInstanceOf(ThemeResolutionException::class, $e);
        $this->assertSame(102, $e->getCode());
    }
}
