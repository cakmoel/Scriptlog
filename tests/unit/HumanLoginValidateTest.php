<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * Human Login Validation Test
 *
 * Tests for validate_login_context() whitelist expansion.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class HumanLoginValidateTest extends TestCase
{
    private $source;

    protected function setUp(): void
    {
        $path = __DIR__ . '/../../src/lib/utility/human-login.php';
        if (file_exists($path)) {
            $this->source = file_get_contents($path);
        }
    }

    public function testValidateLoginContextWhitelistExists(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('human-login.php not found');
        }
        $this->assertStringContainsString("validate_login_context", $this->source);
    }

    public function testValidateLoginContextHasExpandedWhitelist(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('human-login.php not found');
        }
        $this->assertStringContainsString("'login', 'user_pass', 'csrf', 'LogIn'", $this->source);
    }

    public function testValidateLoginContextIncludesCaptchaLogin(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('human-login.php not found');
        }
        $this->assertStringContainsString("'captcha_login'", $this->source);
    }

    public function testValidateLoginContextIncludesScriptpot(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('human-login.php not found');
        }
        $this->assertStringContainsString("'scriptpot_name'", $this->source);
        $this->assertStringContainsString("'scriptpot_email'", $this->source);
    }

    public function testValidateLoginContextIncludesRemember(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('human-login.php not found');
        }
        $this->assertStringContainsString("'remember'", $this->source);
    }

    public function testValidateLoginContextCallsCheckFormRequest(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('human-login.php not found');
        }
        $this->assertStringContainsString('check_form_request', $this->source);
    }

    public function testCheckingLoginRequestHasOriginalWhitelist(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('human-login.php not found');
        }
        $this->assertStringContainsString("'login', 'user_pass', 'scriptpot_name', 'scriptpot_email', 'captcha_login', 'remember', 'csrf', 'LogIn'", $this->source);
    }

    public function testValidateLoginContextCallsVerifyFormToken(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('human-login.php not found');
        }
        $this->assertStringContainsString("verify_form_token", $this->source);
    }
}
