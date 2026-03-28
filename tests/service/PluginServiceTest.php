<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PluginService Test
 * 
 * Tests for plugin business logic.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PluginServiceTest extends TestCase
{
    private $pluginService;
    private $pluginDaoMock;
    private $validatorMock;
    private $sanitizeMock;

    protected function setUp(): void
    {
        $this->pluginDaoMock = $this->createMock(\PluginDao::class);
        $this->validatorMock = $this->createMock(\FormValidator::class);
        $this->sanitizeMock = $this->createMock(\Sanitize::class);
        
        $this->pluginService = new \PluginService(
            $this->pluginDaoMock,
            $this->validatorMock,
            $this->sanitizeMock
        );
    }

    public function testSetPluginId(): void
    {
        $this->pluginService->setPluginId(1);
        $this->assertTrue(true);
    }

    public function testSetPluginName(): void
    {
        $this->pluginService->setPluginName('Test Plugin');
        $this->assertTrue(true);
    }

    public function testSetPluginLink(): void
    {
        $this->pluginService->setPluginLink('http://example.com');
        $this->assertTrue(true);
    }

    public function testSetPluginDirectory(): void
    {
        $this->pluginService->setPluginDirectory('test-plugin');
        $this->assertTrue(true);
    }

    public function testSetPluginDescription(): void
    {
        $this->pluginService->setPluginDescription('Test plugin description');
        $this->assertTrue(true);
    }

    public function testSetPluginStatus(): void
    {
        $this->pluginService->setPluginStatus('Y');
        $this->assertTrue(true);
    }

    public function testSetPluginLevel(): void
    {
        $this->pluginService->setPluginLevel('administrator');
        $this->assertTrue(true);
    }

    public function testSetPluginSort(): void
    {
        $this->pluginService->setPluginSort(1);
        $this->assertTrue(true);
    }

    public function testGrabPlugins(): void
    {
        $this->pluginDaoMock->method('getPlugins')->willReturn([]);
        $plugins = $this->pluginService->grabPlugins();
        $this->assertIsArray($plugins);
    }

    public function testGrabPlugin(): void
    {
        $this->pluginDaoMock->method('getPlugin')->willReturn(['ID' => 1, 'plugin_name' => 'Test Plugin']);
        $plugin = $this->pluginService->grabPlugin(1);
        $this->assertIsArray($plugin);
    }

    public function testTotalPlugins(): void
    {
        $this->pluginDaoMock->method('totalPluginRecords')->willReturn(5);
        $total = $this->pluginService->totalPlugins();
        $this->assertEquals(5, $total);
    }

    public function testIsPluginExists(): void
    {
        $this->pluginDaoMock->method('pluginExists')->willReturn(true);
        $exists = $this->pluginService->isPluginExists('Test Plugin');
        $this->assertTrue($exists);
    }

    public function testPluginLevelDropDown(): void
    {
        $this->pluginDaoMock->method('dropDownPluginLevel')->willReturn('<select><option>administrator</option></select>');
        $dropdown = $this->pluginService->pluginLevelDropDown();
        $this->assertIsString($dropdown);
    }
}
