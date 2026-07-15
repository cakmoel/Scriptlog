<?php

use PHPUnit\Framework\TestCase;

class ThemeRendererTest extends TestCase
{
    private string $themeDir;
    private string $themesRoot;
    private string $testThemeDir;

    protected function setUp(): void
    {
        $this->themesRoot = __DIR__ . '/../../src/public/themes/';
        $this->themeDir = $this->themesRoot . 'blog/';
        $this->testThemeDir = '/tmp/test-theme/';
        require_once __DIR__ . '/../../src/lib/core/ThemeRenderer.php';
        require_once __DIR__ . '/../../src/lib/core/ThemeResolutionException.php';

        if (!is_dir($this->testThemeDir)) {
            mkdir($this->testThemeDir, 0755, true);
        }
        file_put_contents($this->testThemeDir . 'header.php', '<?php echo "TestHeader";');
        file_put_contents($this->testThemeDir . 'footer.php', '<?php echo "TestFooter";');
        file_put_contents($this->testThemeDir . '404.php', '<?php echo "Test404Content";');
        file_put_contents($this->testThemeDir . 'home.php', '<?php echo "TestHomeContent";');
    }

    protected function tearDown(): void
    {
        http_response_code(200);
        if (is_dir($this->testThemeDir)) {
            foreach (glob($this->testThemeDir . '*.php') as $file) {
                @unlink($file);
            }
            @rmdir($this->testThemeDir);
        }
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('ThemeRenderer'));
    }

    public function testClassImplementsThemeRendererInterface(): void
    {
        $reflection = new ReflectionClass('ThemeRenderer');
        $this->assertTrue($reflection->implementsInterface('ThemeRendererInterface'));
    }

    public function testConstructorSetsThemeDir(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        $themeDir = $renderer->getThemeDir();
        $this->assertStringEndsWith('blog/', $themeDir);
        $this->assertStringStartsWith($this->themesRoot, $themeDir);
    }

    public function testConstructorFallsBackToDefaultTheme(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'nonexistent-theme');
        $themeDir = $renderer->getThemeDir();
        $this->assertStringEndsWith('blog/', $themeDir);
    }

    public function testConstructorFallsBackForEmptyName(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, '');
        $themeDir = $renderer->getThemeDir();
        $this->assertStringEndsWith('blog/', $themeDir);
    }

    public function testConstructorSanitizesThemeName(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, '../../etc/passwd');
        $themeDir = $renderer->getThemeDir();
        $this->assertStringEndsWith('blog/', $themeDir);
    }

    public function testGetThemeDirReturnsString(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        $this->assertIsString($renderer->getThemeDir());
    }

    public function testGetThemeDirEndsWithSeparator(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        $this->assertStringEndsWith(DIRECTORY_SEPARATOR, $renderer->getThemeDir());
    }

    public function testRenderContentWithInvalidTemplate(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        ob_start();
        $renderer->renderContent('nonexistent_template_xyz');
        $output = ob_get_clean();
        $this->assertEmpty($output);
    }

    public function testRenderContentWithEmptyTemplateName(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'blog');
        ob_start();
        $renderer->renderContent('');
        $output = ob_get_clean();
        $this->assertEmpty($output);
    }

    public function testRender404SetsStatusCode(): void
    {
        $renderer = new ThemeRenderer('/tmp/', 'test-theme');
        ob_start();
        http_response_code(200);
        $renderer->render404();
        $code = http_response_code();
        ob_end_clean();
        $this->assertTrue($code === 404 || $code === false,
            'Expected 404 or false, got ' . var_export($code, true));
    }

    public function testRenderSetsStatusCode(): void
    {
        $renderer = new ThemeRenderer('/tmp/', 'test-theme');
        ob_start();
        http_response_code(200);
        $renderer->render('home', 200);
        $code = http_response_code();
        ob_end_clean();
        $this->assertTrue($code === 200 || $code === false,
            'Expected 200 or false, got ' . var_export($code, true));
    }

    public function testRenderHeaderWithTestTheme(): void
    {
        $renderer = new ThemeRenderer('/tmp/', 'test-theme');
        ob_start();
        $renderer->renderHeader();
        $output = ob_get_clean();
        $this->assertStringContainsString('TestHeader', $output);
    }

    public function testRenderContentWithTestTheme(): void
    {
        $renderer = new ThemeRenderer('/tmp/', 'test-theme');
        ob_start();
        $renderer->renderContent('home');
        $output = ob_get_clean();
        $this->assertStringContainsString('TestHomeContent', $output);
    }

    public function testRenderFooterWithTestTheme(): void
    {
        $renderer = new ThemeRenderer('/tmp/', 'test-theme');
        ob_start();
        $renderer->renderFooter();
        $output = ob_get_clean();
        $this->assertStringContainsString('TestFooter', $output);
    }

    public function testRender404WithTestTheme(): void
    {
        $renderer = new ThemeRenderer('/tmp/', 'test-theme');
        ob_start();
        $renderer->render404();
        $output = ob_get_clean();
        $this->assertStringContainsString('TestHeader', $output);
        $this->assertStringContainsString('TestFooter', $output);
    }

    public function testClassHasExpectedMethods(): void
    {
        $reflection = new ReflectionClass('ThemeRenderer');
        $methods = array_map(function ($m) { return $m->getName(); }, $reflection->getMethods());
        $this->assertContains('render', $methods);
        $this->assertContains('renderHeader', $methods);
        $this->assertContains('renderContent', $methods);
        $this->assertContains('renderFooter', $methods);
        $this->assertContains('render404', $methods);
        $this->assertContains('getThemeDir', $methods);
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

    public function testRenderWithCustomFallback(): void
    {
        $renderer = new ThemeRenderer($this->themesRoot, 'nonexistent', null, 'blog');
        $this->assertStringEndsWith('blog/', $renderer->getThemeDir());
    }

    public function testErrorLoggerIsCalledOnMissingTemplate(): void
    {
        $logMessages = [];
        $logger = function (string $msg) use (&$logMessages) {
            $logMessages[] = $msg;
        };
        $renderer = new ThemeRenderer($this->themesRoot, 'blog', $logger);
        $renderer->renderContent('nonexistent_template_xyz');
        $this->assertNotEmpty($logMessages);
    }
}
