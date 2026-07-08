<?php

use PHPUnit\Framework\TestCase;

class WebServerDetectionTest extends TestCase
{
    private string $utilityPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->utilityPath = __DIR__ . '/../../src/lib/utility/detect-web-server.php';

        if (!function_exists('detect_web_server')) {
            require_once $this->utilityPath;
        }
    }

    public function testDetectWebServerFunctionExists(): void
    {
        $this->assertTrue(function_exists('detect_web_server'));
    }

    public function testSourceFileIsValidPhpSyntax(): void
    {
        $output = [];
        $returnCode = 0;
        exec('php -l ' . escapeshellarg($this->utilityPath) . ' 2>&1', $output, $returnCode);
        $this->assertEquals(0, $returnCode, 'PHP syntax check failed: ' . implode("\n", $output));
    }

    public function testDetectNginxFromServerSoftware(): void
    {
        $_SERVER['SERVER_SOFTWARE'] = 'nginx/1.24.0';
        $this->assertEquals('Nginx', detect_web_server());
    }

    public function testDetectApacheFromServerSoftware(): void
    {
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.57 (Unix)';
        $this->assertEquals('Apache', detect_web_server());
    }

    public function testDetectLiteSpeedFromServerSoftware(): void
    {
        $_SERVER['SERVER_SOFTWARE'] = 'LiteSpeed';
        $this->assertEquals('LiteSpeed', detect_web_server());
    }

    public function testDetectMicrosoftIisFromServerSoftware(): void
    {
        $_SERVER['SERVER_SOFTWARE'] = 'Microsoft-IIS/10.0';
        $this->assertEquals('Microsoft-IIS', detect_web_server());
    }

    public function testDetectUnknownServerReturnsUcfirstName(): void
    {
        $_SERVER['SERVER_SOFTWARE'] = 'Caddy/2.7.5';
        $this->assertEquals('Caddy', detect_web_server());
    }

    public function testDetectNginxFromCgiVariable(): void
    {
        unset($_SERVER['SERVER_SOFTWARE']);
        $_SERVER['NGINX'] = '1';
        $this->assertEquals('Nginx', detect_web_server());
    }

    public function testDefaultFallbackIsApache(): void
    {
        unset($_SERVER['SERVER_SOFTWARE']);
        unset($_SERVER['NGINX']);
        unset($_SERVER['nginx']);
        $this->assertEquals('Apache', detect_web_server());
    }

    public function testDetectReturnsString(): void
    {
        $_SERVER['SERVER_SOFTWARE'] = 'Apache/2.4.57 (Unix)';
        $this->assertIsString(detect_web_server());
    }

    public function testCaseInsensitiveDetection(): void
    {
        $_SERVER['SERVER_SOFTWARE'] = 'NGINX/1.24.0';
        $this->assertEquals('Nginx', detect_web_server());

        $_SERVER['SERVER_SOFTWARE'] = 'APACHE/2.4.57';
        $this->assertEquals('Apache', detect_web_server());

        $_SERVER['SERVER_SOFTWARE'] = 'LITESPEED';
        $this->assertEquals('LiteSpeed', detect_web_server());

        $_SERVER['SERVER_SOFTWARE'] = 'MICROSOFT-IIS/10.0';
        $this->assertEquals('Microsoft-IIS', detect_web_server());
    }

    public function testFunctionHasDocBlock(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('/**', $source);
        $this->assertStringContainsString('@return string', $source);
    }
}
