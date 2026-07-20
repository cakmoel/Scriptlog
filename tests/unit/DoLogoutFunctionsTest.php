<?php

use PHPUnit\Framework\TestCase;

class DoLogoutFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('do_logout')) {
            require_once __DIR__ . '/../../src/lib/utility/do-logout.php';
        }

        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    protected function tearDown(): void
    {
        unset($_SESSION['loggingOut']);
    }

    public function testDoLogoutFunctionExists(): void
    {
        $this->assertTrue(function_exists('do_logout'));
    }

    public function testDoLogoutIdFunctionExists(): void
    {
        $this->assertTrue(function_exists('do_logout_id'));
    }

    public function testVerifyLogoutIdFunctionExists(): void
    {
        $this->assertTrue(function_exists('verify_logout_id'));
    }

    public function testDoLogoutReturnsNullForInvalidAuthenticator(): void
    {
        $result = do_logout(new stdClass());
        $this->assertNull($result);
    }

    public function testDoLogoutIdReturnsString(): void
    {
        $result = do_logout_id();
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testDoLogoutIdSetsSessionVariable(): void
    {
        $_SESSION = [];
        $id = do_logout_id();
        $this->assertArrayHasKey('loggingOut', $_SESSION);
        $this->assertArrayHasKey($id, $_SESSION['loggingOut']);
        $this->assertTrue($_SESSION['loggingOut'][$id]);
    }

    public function testVerifyLogoutIdReturnsTrueForValidId(): void
    {
        $_SESSION = [];
        $id = do_logout_id();
        $result = verify_logout_id($id);
        $this->assertTrue($result);
    }

    public function testVerifyLogoutIdReturnsFalseForInvalidId(): void
    {
        $_SESSION = [];
        $result = verify_logout_id('nonexistent-id');
        $this->assertFalse($result);
    }

    public function testVerifyLogoutIdRemovesSessionEntry(): void
    {
        $_SESSION = [];
        $id = do_logout_id();
        verify_logout_id($id);
        $this->assertArrayNotHasKey($id, $_SESSION['loggingOut']);
    }

    public function testDoLogoutSourceChecksAuthenticationInstance(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/do-logout.php');
        $this->assertStringContainsString('instanceof', $source);
        $this->assertStringContainsString('Authentication', $source);
    }
}
