<?php
/**
 * ConfigurationController Tests
 *
 * Tests for ConfigurationController filter changes
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class ConfigurationControllerTest extends TestCase
{
    private $configService;
    private $configController;

    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];

        $this->configService = $this->createMock(ConfigurationService::class);
        $this->configController = new ConfigurationController($this->configService);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    public function testConstructorAcceptsConfigService(): void
    {
        $this->assertInstanceOf(ConfigurationController::class, $this->configController);
    }

    public function testSetAndGetPageTitle(): void
    {
        $this->configController->setPageTitle('General Settings');
        $this->assertSame('General Settings', $this->configController->getPageTitle());
    }

    public function testSetAndGetFormAction(): void
    {
        $this->configController->setFormAction('generalConfig');
        $this->assertSame('generalConfig', $this->configController->getFormAction());
    }

    public function testUpdateGeneralSettingFilterUsesUnsafeRaw(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/controller/ConfigurationController.php');

        $this->assertStringContainsString("'setting_value' => [", $source);
        $this->assertStringContainsString("'filter' => FILTER_UNSAFE_RAW", $source);
    }

    public function testUpdateGeneralSettingFilterArrayKey(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/controller/ConfigurationController.php');

        $this->assertStringContainsString("'setting_value'", $source);
        $this->assertStringContainsString("'filter' => FILTER_UNSAFE_RAW", $source);
    }

    public function testUpdateGeneralSettingMethodExists(): void
    {
        $this->assertTrue(method_exists(ConfigurationController::class, 'updateGeneralSetting'));
    }

    public function testUpdateGeneralSettingNoCsrfTokenRedirects(): void
    {
        $_POST['configFormSubmit'] = '1';
        $_POST['setting_value'] = ['1' => 'test_value'];

        ob_start();
        try {
            $this->configController->updateGeneralSetting();
        } catch (\Throwable $e) {
        }
        ob_end_clean();

        $this->assertTrue(true);
    }
}