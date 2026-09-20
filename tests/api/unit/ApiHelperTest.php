<?php
/**
 * ApiHelper Test
 *
 * Unit tests for Scriptlog\Core\ApiHelper::getAppUrl().
 *
 * The helper resolves the application base URL from config.php exactly once
 * and caches the result in the Registry under the 'app_url' key. These
 * tests cover both the cache-hit path and the read-then-cache path without
 * depending on the contents of the (gitignored) config.php.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';

class ApiHelperTest extends ApiTestCase
{
    /**
     * A URL pre-registered in the Registry is returned as-is.
     *
     * @return void
     */
    public function testGetAppUrlReturnsCachedRegistryValue()
    {
        \Scriptlog\Core\Registry::set('app_url', 'https://cached.example.com');

        $this->assertSame('https://cached.example.com', \Scriptlog\Core\ApiHelper::getAppUrl());
    }

    /**
     * On the first call the helper reads config.php, caches the result, and
     * subsequent calls return the identical cached value.
     *
     * @return void
     */
    public function testGetAppUrlReadsConfigAndCachesOnFirstCall()
    {
        \Scriptlog\Core\Registry::setAll([]);

        $first = \Scriptlog\Core\ApiHelper::getAppUrl();

        $this->assertIsString($first);
        $this->assertNotSame('', $first);
        $this->assertTrue(\Scriptlog\Core\Registry::isKeySet('app_url'));

        $second = \Scriptlog\Core\ApiHelper::getAppUrl();

        $this->assertSame($first, $second);
    }

    /**
     * The resolved URL matches the config.php app.url contract (defaulting
     * to http://localhost when the key is absent).
     *
     * @return void
     */
    public function testGetAppUrlMatchesConfigContract()
    {
        \Scriptlog\Core\Registry::setAll([]);

        $config = [];
        $configPath = dirname(__DIR__, 3) . '/config.php';
        if (file_exists($configPath)) {
            $config = require $configPath;
        }

        $expected = $config['app']['url'] ?? 'http://localhost';

        $this->assertSame($expected, \Scriptlog\Core\ApiHelper::getAppUrl());
    }
}
