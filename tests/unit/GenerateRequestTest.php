<?php

use PHPUnit\Framework\TestCase;

class GenerateRequestTest extends TestCase
{
    private string $utilityPath;
    private string $sanitizeUrlsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->utilityPath = __DIR__ . '/../../src/lib/utility/generate-request.php';
        $this->sanitizeUrlsPath = __DIR__ . '/../../src/lib/utility/sanitize-urls.php';
    }

    public function testFunctionExists(): void
    {
        $this->assertTrue(function_exists('generate_request'));
    }

    public function testSourceContainsRequireOnceBeforeFunction(): void
    {
        $source = file_get_contents($this->utilityPath);

        $requirePos = strpos($source, 'require_once');
        $functionPos = strpos($source, 'function generate_request');

        $this->assertNotFalse($requirePos, 'Must contain require_once');
        $this->assertNotFalse($functionPos, 'Must contain function declaration');
        $this->assertLessThan($functionPos, $requirePos, 'require_once must be placed before function declaration');
    }

    public function testSourceRequiresSanitizeUrls(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString("require_once __DIR__ . '/sanitize-urls.php'", $source);
    }

    public function testFileIsValidPhpSyntax(): void
    {
        $output = [];
        $returnCode = 0;
        exec('php -l ' . escapeshellarg($this->utilityPath) . ' 2>&1', $output, $returnCode);
        $this->assertEquals(0, $returnCode, 'PHP syntax check failed: ' . implode("\n", $output));
    }

    public function testHasStrictTypes(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('declare(strict_types=1)', $source);
    }

    public function testSourceUsesSanitizeUrlsInGetType(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString("sanitize_urls(\$load)", $source);
    }

    public function testSourceHasGetAndPostCasesInSwitch(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString("case 'get'", $source);
        $this->assertStringContainsString("case 'post'", $source);
    }

    public function testSourceUsesBuildQueryFunction(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('build_query($base', $source);
    }

    public function testSanitizeUrlsIsValidPhp(): void
    {
        $output = [];
        $returnCode = 0;
        exec('php -l ' . escapeshellarg($this->sanitizeUrlsPath) . ' 2>&1', $output, $returnCode);
        $this->assertEquals(0, $returnCode, 'PHP syntax check failed for sanitize-urls.php: ' . implode("\n", $output));
    }

    public function testSanitizeUrlsUsesHtmlspecialchars(): void
    {
        $source = file_get_contents($this->sanitizeUrlsPath);
        $this->assertStringContainsString('htmlspecialchars', $source);
        $this->assertStringContainsString('ENT_QUOTES', $source);
        $this->assertStringContainsString('ENT_HTML5', $source);
    }

    public function testSanitizeUrlsHasForceLowercaseOption(): void
    {
        $source = file_get_contents($this->sanitizeUrlsPath);
        $this->assertStringContainsString('$force_lowercase', $source);
    }

    public function testSanitizeUrlsHandlesArrayInput(): void
    {
        if (!function_exists('sanitize_urls')) {
            require_once $this->sanitizeUrlsPath;
        }

        $result = sanitize_urls(['Hello', 'World']);
        $this->assertIsString($result);
    }

    public function testSanitizeUrlsRemovesSpecialChars(): void
    {
        if (!function_exists('sanitize_urls')) {
            require_once $this->sanitizeUrlsPath;
        }

        $result = sanitize_urls('Hello <World> Test!');
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    public function testSanitizeUrlsReplacesSpacesWithHyphens(): void
    {
        if (!function_exists('sanitize_urls')) {
            require_once $this->sanitizeUrlsPath;
        }

        $result = sanitize_urls('Hello World Test');
        $this->assertStringNotContainsString(' ', $result);
        $this->assertStringContainsString('-', $result);
    }

    public function testSanitizeUrlsForceLowercaseDefault(): void
    {
        if (!function_exists('sanitize_urls')) {
            require_once $this->sanitizeUrlsPath;
        }

        $result = sanitize_urls('HELLO WORLD');
        $this->assertEquals('hello-world', $result);
    }

    public function testSanitizeUrlsWithAnalMode(): void
    {
        if (!function_exists('sanitize_urls')) {
            require_once $this->sanitizeUrlsPath;
        }

        $result = sanitize_urls('hello-world-123', true, true);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9-]+$/', $result);
    }

    public function testSanitizeUrlsPreservesHyphens(): void
    {
        if (!function_exists('sanitize_urls')) {
            require_once $this->sanitizeUrlsPath;
        }

        $result = sanitize_urls('hello-world-test');
        $this->assertEquals('hello-world-test', $result);
    }
}
