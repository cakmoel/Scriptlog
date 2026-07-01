<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/lib/utility/secure-http-headers.php';

class SecureHttpHeadersTest extends TestCase
{
    private $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
        $_SERVER['HTTPS'] = 'off';
        if (!function_exists('app_url')) {
            eval('function app_url() { return "http://example.com"; }');
        }
        if (!function_exists('is_ssl')) {
            eval('function is_ssl() { return ($_SERVER["HTTPS"] ?? "off") === "on"; }');
        }
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
    }

    public function testContentSecurityPolicyHeaderSent(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent');
        }
        content_security_policy('http://example.com');
        $headers = headers_list();
        $this->assertNotEmpty($headers);
    }

    public function testContentSecurityPolicyContainsDefaultSrc(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent');
        }
        headers_remove();
        content_security_policy('http://example.com');
        $headers = implode("\n", headers_list());
        $this->assertStringContainsString("default-src 'self'", $headers);
    }

    public function testContentSecurityPolicyWithoutUnsafeEval(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent');
        }
        headers_remove();
        content_security_policy('http://example.com');
        $headers = implode("\n", headers_list());
        $this->assertStringNotContainsString('unsafe-eval', $headers);
    }

    public function testContentSecurityPolicyWithUnsafeInline(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent');
        }
        headers_remove();
        content_security_policy('http://example.com');
        $headers = implode("\n", headers_list());
        $this->assertStringContainsString("'unsafe-inline'", $headers);
    }

    public function testCspHasReportOnlyHeader(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent');
        }
        headers_remove();
        content_security_policy('http://example.com');
        $headers = implode("\n", headers_list());
        $this->assertStringContainsString('Content-Security-Policy-Report-Only', $headers);
    }

    public function testCspReportOnlyWithoutUnsafeInline(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent');
        }
        headers_remove();
        content_security_policy('http://example.com');
        $headers = implode("\n", headers_list());
        $reportOnlyStart = strpos($headers, 'Content-Security-Policy-Report-Only');
        if ($reportOnlyStart !== false) {
            $reportOnly = substr($headers, $reportOnlyStart);
            $this->assertStringNotContainsString("'unsafe-inline'", $reportOnly);
        }
    }

    public function testCspWithSslAddsUpgradeInsecureRequests(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent');
        }
        headers_remove();
        $_SERVER['HTTPS'] = 'on';
        content_security_policy('https://example.com');
        $headers = implode("\n", headers_list());
        $this->assertStringNotContainsString('upgrade-insecure-requests', $headers);
    }

    public function testCspWithoutSslAddsUpgradeInsecureRequests(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent');
        }
        headers_remove();
        $_SERVER['HTTPS'] = 'off';
        content_security_policy('http://example.com');
        $headers = implode("\n", headers_list());
        $this->assertStringContainsString('upgrade-insecure-requests', $headers);
    }

    public function testXFrameOption(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent');
        }
        headers_remove();
        x_frame_option('DENY');
        $this->assertStringContainsString('X-Frame-Options: DENY', implode("\n", headers_list()));
    }

    public function testXXssProtection(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent');
        }
        headers_remove();
        x_xss_protection();
        $this->assertStringContainsString('X-XSS-Protection: 1; mode=block', implode("\n", headers_list()));
    }

    public function testXContentTypeOptions(): void
    {
        if (headers_sent()) {
            $this->markTestSkipped('Headers already sent');
        }
        headers_remove();
        x_content_type_options('nosniff');
        $this->assertStringContainsString('X-Content-Type-Options: nosniff', implode("\n", headers_list()));
    }

    public function testRemoveXPoweredBy(): void
    {
        header('X-Powered-By: PHP/8.0');
        remove_x_powered_by();
        $this->assertStringNotContainsString('X-Powered-By', implode("\n", headers_list()));
    }

    public function testFunctionExistence(): void
    {
        $this->assertTrue(function_exists('content_security_policy'));
        $this->assertTrue(function_exists('x_frame_option'));
        $this->assertTrue(function_exists('x_xss_protection'));
        $this->assertTrue(function_exists('x_content_type_options'));
        $this->assertTrue(function_exists('strict_transport_security'));
        $this->assertTrue(function_exists('remove_x_powered_by'));
        $this->assertTrue(function_exists('set_cors_headers'));
        $this->assertTrue(function_exists('handle_preflight_request'));
    }
}
