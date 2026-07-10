<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * Form Security Functions Test
 *
 * Tests for check_form_request() with new $alwaysAllowed logic,
 * generate_form_token(), verify_form_token(), and scriptpot_validate().
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class FormSecurityFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('check_form_request')) {
            require_once __DIR__ . '/../../src/lib/utility/form-security.php';
        }
    }

    public function testCheckFormRequestWithValidData(): void
    {
        $data = ['login' => 'user', 'user_pass' => 'secret'];
        $whitelist = ['login', 'user_pass'];
        $this->assertTrue(check_form_request($data, $whitelist));
    }

    public function testCheckFormRequestWithInvalidKey(): void
    {
        $data = ['login' => 'user', 'malicious_field' => 'inject'];
        $whitelist = ['login', 'user_pass'];
        $this->assertFalse(check_form_request($data, $whitelist));
    }

    public function testCheckFormRequestAlwaysAllowedFields(): void
    {
        $data = ['login' => 'user', 'csrfToken' => 'abc123', 'postFormSubmit' => '1', 'MAX_FILE_SIZE' => '99999'];
        $whitelist = ['login'];
        $this->assertTrue(check_form_request($data, $whitelist));
    }

    public function testCheckFormRequestEmptyData(): void
    {
        $data = [];
        $whitelist = ['login'];
        $this->assertTrue(check_form_request($data, $whitelist));
    }

    public function testCheckFormRequestAllWhitelistedWithAlwaysAllowed(): void
    {
        $data = ['login' => 'user', 'csrfToken' => 'token', 'MAX_FILE_SIZE' => '1000'];
        $whitelist = ['login'];
        $this->assertTrue(check_form_request($data, $whitelist));
    }

    public function testCheckFormRequestOnlyAlwaysAllowed(): void
    {
        $data = ['csrfToken' => 'token', 'postFormSubmit' => '1'];
        $whitelist = [];
        $this->assertTrue(check_form_request($data, $whitelist));
    }

    public function testGenerateFormTokenCreatesToken(): void
    {
        if (!session_id()) {
            session_start();
        }
        $token = generate_form_token('test_form', 32);
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        session_destroy();
        $_SESSION = [];
    }

    public function testVerifyFormTokenValid(): void
    {
        if (!session_id()) {
            session_start();
        }
        $token = generate_form_token('verify_form', 32);
        $this->assertTrue(verify_form_token('verify_form', $token));
        session_destroy();
        $_SESSION = [];
    }

    public function testVerifyFormTokenInvalid(): void
    {
        $this->assertFalse(verify_form_token('nonexistent', 'faketoken'));
    }

    public function testScriptpotValidateClean(): void
    {
        $req = ['login' => 'user', 'user_pass' => 'pass'];
        $this->assertTrue(scriptpot_validate($req));
    }

    public function testScriptpotValidateWithHoneypotFilled(): void
    {
        $req = ['scriptpot_name' => 'bot', 'scriptpot_email' => 'bot@spam.com'];
        $this->assertFalse(scriptpot_validate($req));
    }

    public function testScriptpotValidateEmptyArray(): void
    {
        $this->assertTrue(scriptpot_validate([]));
    }

    public function testCheckFormRequestWhitelistOrderIrrelevant(): void
    {
        $data = ['user_pass' => 'pass', 'login' => 'user'];
        $whitelist = ['login', 'user_pass'];
        $this->assertTrue(check_form_request($data, $whitelist));
    }
}
