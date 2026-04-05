<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * ConfigurationService Test
 * 
 * Tests for configuration/settings business logic.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class ConfigurationServiceTest extends TestCase
{
    private $configService;
    private $configDaoMock;
    private $validatorMock;
    private $sanitizeMock;

    protected function setUp(): void
    {
        $this->configDaoMock = $this->createMock(\ConfigurationDao::class);
        $this->validatorMock = $this->createMock(\FormValidator::class);
        $this->sanitizeMock = $this->createMock(\Sanitize::class);
        
        $this->configService = new \ConfigurationService(
            $this->configDaoMock,
            $this->validatorMock,
            $this->sanitizeMock
        );
    }

    public function testSetConfigId(): void
    {
        $this->configService->setConfigId(1);
        $this->assertTrue(true);
    }

    public function testSetConfigName(): void
    {
        $this->configService->setConfigName('site_title');
        $this->assertTrue(true);
    }

    public function testSetConfigValue(): void
    {
        $this->configService->setConfigValue('My Blog');
        $this->assertTrue(true);
    }

    public function testGrabSettings(): void
    {
        $this->configDaoMock->method('findConfigs')->willReturn([]);
        $settings = $this->configService->grabSettings();
        $this->assertIsArray($settings);
    }

    public function testGrabGeneralSettings(): void
    {
        $this->configDaoMock->method('findGeneralConfigs')->willReturn([]);
        $settings = $this->configService->grabGeneralSettings();
        $this->assertIsArray($settings);
    }

    public function testGrabReadingSettings(): void
    {
        $this->configDaoMock->method('findReadingConfigs')->willReturn([]);
        $settings = $this->configService->grabReadingSettings();
        $this->assertIsArray($settings);
    }

    public function testGrabSettingByName(): void
    {
        $this->configDaoMock->method('findConfigByName')->willReturn(['setting_name' => 'site_title', 'setting_value' => 'My Blog']);
        $setting = $this->configService->grabSettingByName('site_title');
        $this->assertIsArray($setting);
    }

    public function testGrabSetting(): void
    {
        $this->configDaoMock->method('findConfig')->willReturn(['ID' => 1, 'setting_name' => 'site_title']);
        $setting = $this->configService->grabSetting(1);
        $this->assertIsArray($setting);
    }

    public function testTotalSettings(): void
    {
        $this->configDaoMock->method('totalConfigRecords')->willReturn(10);
        $total = $this->configService->totalSettings();
        $this->assertEquals(10, $total);
    }

    public function testTimezoneIdentifierDropDown(): void
    {
        $this->configDaoMock->method('dropDownTimezone')->willReturn('<select><option>UTC</option></select>');
        $dropdown = $this->configService->timezoneIdentifierDropDown();
        $this->assertIsString($dropdown);
    }
}
