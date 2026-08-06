<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class ApiHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (class_exists('\\Scriptlog\\Core\\Registry')) {
            \Scriptlog\Core\Registry::set('app_url', null);
        }
    }

    protected function tearDown(): void
    {
        if (class_exists('\\Scriptlog\\Core\\Registry')) {
            \Scriptlog\Core\Registry::set('app_url', null);
        }
        parent::tearDown();
    }

    public function testGetAppUrlReturnsDefaultWhenNoConfig(): void
    {
        $url = \Scriptlog\Core\ApiHelper::getAppUrl();
        $this->assertEquals('http://localhost', $url);
    }

    public function testGetAppUrlReturnsFromRegistryWhenSet(): void
    {
        \Scriptlog\Core\Registry::set('app_url', 'https://example.com');
        $url = \Scriptlog\Core\ApiHelper::getAppUrl();
        $this->assertEquals('https://example.com', $url);
    }

    public function testGetAppUrlReturnsString(): void
    {
        $url = \Scriptlog\Core\ApiHelper::getAppUrl();
        $this->assertIsString($url);
    }
}
