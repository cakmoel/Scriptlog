<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

/**
 * Tests for secure HTTP header functions.
 *
 * Headers are verified via a sub-process to ensure no prior output
 * has been emitted (which would cause header() to fail).
 */
class SecureHttpHeadersTest extends TestCase
{
    private $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
        $_SERVER['HTTPS'] = 'off';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
    }

    public function testCspHeadersViaSubprocess(): void
    {
        $result = $this->runSubprocess(
            'content_security_policy("http://example.com");',
            false
        );
        $headers = json_decode($result, true);
        $this->assertIsArray($headers);

        $cspEnforced = '';
        $cspReport = '';
        foreach ($headers as $h) {
            if (strpos($h, 'Content-Security-Policy-Report-Only') === 0) {
                $cspReport = $h;
            } elseif (strpos($h, 'Content-Security-Policy:') === 0) {
                $cspEnforced = $h;
            }
        }

        $this->assertStringContainsString('Content-Security-Policy:', $cspEnforced);
        $this->assertStringNotContainsString('unsafe-eval', $cspEnforced);
        $this->assertStringContainsString("'unsafe-inline'", $cspEnforced);
        $this->assertStringContainsString("default-src 'self'", $cspEnforced);
        $this->assertStringContainsString("form-action 'self' http://example.com", $cspEnforced);
        $this->assertStringContainsString('upgrade-insecure-requests', $cspEnforced);
        $this->assertStringContainsString('Content-Security-Policy-Report-Only', $cspReport);
        $this->assertStringNotContainsString("'unsafe-inline'", $cspReport);
    }

    public function testCspWithSslViaSubprocess(): void
    {
        $result = $this->runSubprocess(
            '$_SERVER["HTTPS"] = "on"; content_security_policy("https://example.com");',
            true
        );
        $headers = json_decode($result, true);
        $this->assertIsArray($headers);

        $cspEnforced = '';
        foreach ($headers as $h) {
            if (strpos($h, 'Content-Security-Policy:') === 0) {
                $cspEnforced = $h;
                break;
            }
        }

        $this->assertStringContainsString('Content-Security-Policy:', $cspEnforced);
        $this->assertStringNotContainsString('upgrade-insecure-requests', $cspEnforced);
        $this->assertStringContainsString('https:', $cspEnforced);
    }

    public function testSimpleHeadersViaSubprocess(): void
    {
        $result = $this->runSubprocess(
            'x_frame_option("DENY"); x_xss_protection(); x_content_type_options("nosniff"); strict_transport_security();',
            false
        );
        $headers = json_decode($result, true);
        $this->assertIsArray($headers);
        $joined = implode("\n", $headers);

        $this->assertStringContainsString('X-Frame-Options: DENY', $joined);
        $this->assertStringContainsString('X-XSS-Protection: 1; mode=block', $joined);
        $this->assertStringContainsString('X-Content-Type-Options: nosniff', $joined);
        $this->assertStringContainsString('Strict-Transport-Security: max-age=31536000; includeSubDomains', $joined);
    }

    public function testFunctionExistence(): void
    {
        require_once __DIR__ . '/../../src/lib/utility/secure-http-headers.php';
        $this->assertTrue(function_exists('content_security_policy'));
        $this->assertTrue(function_exists('x_frame_option'));
        $this->assertTrue(function_exists('x_xss_protection'));
        $this->assertTrue(function_exists('x_content_type_options'));
        $this->assertTrue(function_exists('strict_transport_security'));
        $this->assertTrue(function_exists('remove_x_powered_by'));
    }

    private function runSubprocess(string $body, bool $ssl): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'hdr_');
        $snippet = sprintf(
            'define("SCRIPTLOG",true);'
            . 'require "/var/www/html/Scriptlog/src/lib/utility/secure-http-headers.php";'
            . 'if(!function_exists("app_url")){function app_url(){return "%s";}}'
            . 'if(!function_exists("is_ssl")){function is_ssl(){return %s;}}'
            . '%s;'
            . 'file_put_contents("%s",json_encode(xdebug_get_headers()));',
            $ssl ? 'https://example.com' : 'http://example.com',
            $ssl ? 'true' : 'false',
            $body,
            $tmpFile
        );
        shell_exec(sprintf('php -r %s 2>/dev/null', escapeshellarg($snippet)));
        $result = file_get_contents($tmpFile);
        unlink($tmpFile);
        return $result !== false ? $result : 'null';
    }
}
