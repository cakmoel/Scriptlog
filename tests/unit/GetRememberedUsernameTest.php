<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class GetRememberedUsernameTest extends TestCase
{
    private $originalCookie;
    private $originalGlobals;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalCookie = $_COOKIE ?? [];
        $this->originalGlobals = $GLOBALS['app'] ?? null;
    }

    protected function tearDown(): void
    {
        $_COOKIE = $this->originalCookie;
        $GLOBALS['app'] = $this->originalGlobals;
        parent::tearDown();
    }

    public function testReturnsEmptyStringWhenCookieNotSet(): void
    {
        $_COOKIE = [];
        $result = get_remembered_username();
        $this->assertEquals('', $result);
    }

    public function testReturnsEmptyStringWhenScriptlogCryptonizeMissing(): void
    {
        $_COOKIE['scriptlog_auth'] = 'somevalue';
        $result = get_remembered_username();
        $this->assertEquals('', $result);
    }

    public function testReturnsEmptyStringWhenCipherKeyNotSet(): void
    {
        $_COOKIE['scriptlog_auth'] = 'somevalue';
        $GLOBALS['app'] = new stdClass();
        // cipher_key not set
        $result = get_remembered_username();
        $this->assertEquals('', $result);
    }
}
