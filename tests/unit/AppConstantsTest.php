<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class AppConstantsTest extends TestCase
{
    public function testAppTitleConstant(): void
    {
        $this->assertEquals('Scriptlog', APP_TITLE);
    }

    public function testAppVersionConstant(): void
    {
        $this->assertEquals('1.5.1', APP_VERSION);
    }

    public function testApiVersionConstant(): void
    {
        $this->assertEquals('v1', API_VERSION);
    }

    public function testAppCodenameConstant(): void
    {
        $this->assertEquals('Maleo Senkawor', APP_CODENAME);
    }

    public function testDsConstant(): void
    {
        $this->assertEquals(DIRECTORY_SEPARATOR, DS);
    }

    public function testAppRootConstant(): void
    {
        $this->assertStringEndsWith('src' . DS, APP_ROOT);
    }

    public function testAppAdminConstant(): void
    {
        $this->assertEquals('admin', APP_ADMIN);
    }

    public function testAppPublicConstant(): void
    {
        $this->assertEquals('public', APP_PUBLIC);
    }

    public function testAppLibraryConstant(): void
    {
        $this->assertEquals('lib', APP_LIBRARY);
    }

    public function testAppDevelopmentConstant(): void
    {
        $this->assertTrue(APP_DEVELOPMENT);
    }

    public function testScriptlogConstantIsDefined(): void
    {
        $this->assertTrue(defined('SCRIPTLOG'));
    }

    public function testScriptlogConstantIsString(): void
    {
        $this->assertIsString(SCRIPTLOG);
    }
}
