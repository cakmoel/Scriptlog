<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * DsarThrottle Test
 *
 * Covers dsar_request_allowed(), the per-IP throttle for data subject
 * access/erasure request creation added in v1.6.0. Verifies it returns a
 * boolean and fails open (allows the request) when the rate limiter is
 * unavailable.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class DsarThrottleTest extends TestCase
{
    private $originalRemoteAddr;

    protected function setUp(): void
    {
        if (!function_exists('dsar_request_allowed')) {
            require_once __DIR__ . '/../../src/lib/utility/cookie-consent.php';
        }

        $this->originalRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        // Unique source IP per test so disk-backed rate-limit state never
        // leaks between runs.
        $_SERVER['REMOTE_ADDR'] = '198.51.100.' . random_int(2, 254);
    }

    protected function tearDown(): void
    {
        if ($this->originalRemoteAddr === null) {
            unset($_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = $this->originalRemoteAddr;
        }
    }

    public function testDsarRequestAllowedReturnsBoolean(): void
    {
        $this->assertIsBool(dsar_request_allowed());
    }

    public function testDsarRequestAllowedPermitsFirstRequest(): void
    {
        $this->assertTrue(dsar_request_allowed());
    }

    public function testDsarRequestAllowedFailsOpenWhenRateLimiterUnavailable(): void
    {
        $cookieConsent = __DIR__ . '/../../src/lib/utility/cookie-consent.php';
        $script = <<<PHP
<?php
define('SCRIPTLOG', true);
if (!class_exists('ConsentDao')) { class ConsentDao {} }
if (!class_exists('ConsentService')) { class ConsentService {} }
require_once {$this->varExport($cookieConsent)};
var_export(dsar_request_allowed());
PHP;

        $scriptFile = sys_get_temp_dir() . '/dsar_throttle_probe_' . getmypid() . '.php';
        file_put_contents($scriptFile, $script);

        try {
            $output = shell_exec(PHP_BINARY . ' ' . escapeshellarg($scriptFile) . ' 2>/dev/null');
            $this->assertSame('true', trim((string) $output));
        } finally {
            @unlink($scriptFile);
        }
    }

    private function varExport(string $value): string
    {
        return var_export($value, true);
    }
}
