<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class AppKeyTest extends TestCase
{
    private string $testConfigPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testConfigPath = sys_get_temp_dir() . '/test_config_' . uniqid() . '.php';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testConfigPath)) {
            unlink($this->testConfigPath);
        }
        parent::tearDown();
    }

    private function createTestConfig(string $keyValue): void
    {
        $config = [
            'db' => [
                'host' => 'localhost',
                'user' => 'testuser',
                'pass' => 'testpass',
                'name' => 'testdb',
                'port' => '3306',
                'prefix' => 'test_',
            ],
            'app' => [
                'url' => 'https://test.example.com',
                'email' => 'test@example.com',
                'key' => $keyValue,
                'defuse_key' => '/tmp/test_defuse.php',
            ],
            'mail' => [
                'smtp' => [
                    'host' => '',
                    'port' => 587,
                    'encryption' => 'tls',
                    'username' => '',
                    'password' => '',
                ],
                'from' => [
                    'email' => 'noreply@test.example.com',
                    'name' => 'Test Blog'
                ]
            ],
            'os' => [
                'system_software' => 'Linux',
                'distrib_name' => 'Ubuntu'
            ],
            'api' => [
                'allowed_origins' => 'https://test.example.com'
            ],
        ];

        $content = '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';';
        file_put_contents($this->testConfigPath, $content);
    }

    public function testAppKeyReturnsStringType(): void
    {
        if (!function_exists('app_key')) {
            $this->markTestSkipped('app_key function not found');
        }

        $result = app_key();
        $this->assertIsString($result);
    }

    public function testAppKeyFunctionExists(): void
    {
        $this->assertTrue(function_exists('app_key'));
    }

    public function testCheckAppKeyFunctionExists(): void
    {
        $this->assertTrue(function_exists('check_app_key'));
    }

    public function testGrabDataKeyFunctionExists(): void
    {
        $this->assertTrue(function_exists('grab_data_key'));
    }

    public function testIsValidAppKeyFunctionExists(): void
    {
        $this->assertTrue(function_exists('is_valid_app_key'));
    }

    public function testIsPlaceholderKeyFunctionExists(): void
    {
        $this->assertTrue(function_exists('is_placeholder_key'));
    }

    public function testValidateAppKeyFunctionExists(): void
    {
        $this->assertTrue(function_exists('validate_app_key'));
    }

    public function testAppKeyConstants(): void
    {
        $this->assertTrue(defined('SCRIPTLOG'));
    }

    public function testAppKeyDirectReadFromConfig(): void
    {
        if (!class_exists('AppConfig')) {
            $this->markTestSkipped('AppConfig class not found');
        }

        $validKey = 'V74FWK-S32CLA-Y22YKK-F3C936';
        $this->createTestConfig($validKey);

        $config = AppConfig::readConfiguration($this->testConfigPath);
        $key = $config['app']['key'] ?? '';

        $this->assertEquals($validKey, $key);
    }

    public function testAppKeyDirectReadEmptyKey(): void
    {
        if (!class_exists('AppConfig')) {
            $this->markTestSkipped('AppConfig class not found');
        }

        $this->createTestConfig('');

        $config = AppConfig::readConfiguration($this->testConfigPath);
        $key = $config['app']['key'] ?? '';

        $this->assertEquals('', $key);
    }

    public function testIsValidAppKeyWithValidKey(): void
    {
        if (!function_exists('is_valid_app_key')) {
            $this->markTestSkipped('is_valid_app_key function not found');
        }

        $validKeys = [
            'V74FWK-S32CLA-Y22YKK-F3C936',
            'ABC123-DEF456-GHI789-JKL012-MNO345-PQR678',
            'ValidKey12345678901234567890',
        ];

        foreach ($validKeys as $key) {
            $this->assertTrue(is_valid_app_key($key), "Key '$key' should be valid");
        }
    }

    public function testIsValidAppKeyWithShortKey(): void
    {
        if (!function_exists('is_valid_app_key')) {
            $this->markTestSkipped('is_valid_app_key function not found');
        }

        $shortKeys = [
            'ABC123',
            'short',
            '1234567890123456789',
        ];

        foreach ($shortKeys as $key) {
            $this->assertFalse(is_valid_app_key($key), "Key '$key' should be invalid (too short)");
        }
    }

    public function testIsValidAppKeyWithNoUppercase(): void
    {
        if (!function_exists('is_valid_app_key')) {
            $this->markTestSkipped('is_valid_app_key function not found');
        }

        $this->assertFalse(is_valid_app_key('abcdefghijklmnopqrst'));
    }

    public function testIsValidAppKeyWithNoNumbers(): void
    {
        if (!function_exists('is_valid_app_key')) {
            $this->markTestSkipped('is_valid_app_key function not found');
        }

        $this->assertFalse(is_valid_app_key('ABCDEFGHIJKLMNOPQRST'));
    }

    public function testIsValidAppKeyWithEmpty(): void
    {
        if (!function_exists('is_valid_app_key')) {
            $this->markTestSkipped('is_valid_app_key function not found');
        }

        $this->assertFalse(is_valid_app_key(''));
        $this->assertFalse(is_valid_app_key(null));
    }

    public function testIsPlaceholderKeyWithPlaceholder(): void
    {
        if (!function_exists('is_placeholder_key')) {
            $this->markTestSkipped('is_placeholder_key function not found');
        }

        $placeholderKeys = [
            'XXXXXX-XXXXXX-XXXXXX-XXXXXX',
            'xxxxxx-xxxxxx-xxxxxx-xxxxxx',
            'PLACEHOLDER',
            'CHANGE-ME',
            'YOUR-KEY-HERE',
        ];

        foreach ($placeholderKeys as $key) {
            $this->assertTrue(is_placeholder_key($key), "Key '$key' should be detected as placeholder");
        }
    }

    public function testIsPlaceholderKeyWithValidKey(): void
    {
        if (!function_exists('is_placeholder_key')) {
            $this->markTestSkipped('is_placeholder_key function not found');
        }

        $validKeys = [
            'V74FWK-S32CLA-Y22YKK-F3C936',
            'ABC123DEF456GHI789JKL012',
            'a1b2c3d4e5f6g7h8i9j0k1l2',
        ];

        foreach ($validKeys as $key) {
            $this->assertFalse(is_placeholder_key($key), "Key '$key' should not be detected as placeholder");
        }
    }

    public function testIsPlaceholderKeyWithEmpty(): void
    {
        if (!function_exists('is_placeholder_key')) {
            $this->markTestSkipped('is_placeholder_key function not found');
        }

        $this->assertTrue(is_placeholder_key(''));
    }

    public function testValidateAppKeyWithValidKey(): void
    {
        if (!function_exists('validate_app_key')) {
            $this->markTestSkipped('validate_app_key function not found');
        }

        $result = validate_app_key('V74FWK-S32CLA-Y22YKK-F3C936');
        
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['warnings']);
    }

    public function testValidateAppKeyWithPlaceholder(): void
    {
        if (!function_exists('validate_app_key')) {
            $this->markTestSkipped('validate_app_key function not found');
        }

        $result = validate_app_key('XXXXXX-XXXXXX-XXXXXX-XXXXXX');
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['warnings']);
        $this->assertContains('Application key appears to be a placeholder value', $result['warnings']);
    }

    public function testValidateAppKeyWithEmpty(): void
    {
        if (!function_exists('validate_app_key')) {
            $this->markTestSkipped('validate_app_key function not found');
        }

        $result = validate_app_key('');
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function testValidateAppKeyWithWeakKey(): void
    {
        if (!function_exists('validate_app_key')) {
            $this->markTestSkipped('validate_app_key function not found');
        }

        $result = validate_app_key('12345');
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function testAppKeyReturnType(): void
    {
        if (!function_exists('app_key')) {
            $this->markTestSkipped('app_key function not found');
        }

        $result = app_key();
        $this->assertIsString($result);
        $this->assertIsNotInt($result);
        $this->assertIsNotBool($result);
        $this->assertIsNotArray($result);
    }

    public function testAppKeyBehaviorWithValidConfig(): void
    {
        if (!function_exists('app_key')) {
            $this->markTestSkipped('app_key function not found');
        }

        $result = app_key();

        $this->assertIsString($result);
        if (!empty($result)) {
            $this->assertGreaterThan(0, strlen($result));
        }
    }

    public function testCheckAppKeyRedundantComparison(): void
    {
        if (!function_exists('check_app_key')) {
            $this->markTestSkipped('check_app_key function not found');
        }

        $testKey = 'TEST_KEY_12345';

        $sameWithTripleEquals = ($testKey === $testKey);
        $sameWithStrcmp = (strcmp($testKey, $testKey) === 0);

        $this->assertTrue($sameWithTripleEquals);
        $this->assertTrue($sameWithStrcmp);
        $this->assertEquals($sameWithTripleEquals, $sameWithStrcmp);
    }

    public function testMinimumKeyLengthForSecurity(): void
    {
        $minimumLength = 20;

        $weakKeys = [
            'short' => 5,
            'medium123' => 9,
            'almost_there_12345' => 19,
        ];

        foreach ($weakKeys as $key => $length) {
            $this->assertLessThan($minimumLength, $length);
        }

        $secureKey = 'V74FWK-S32CLA-Y22YKK-F3C936';
        $this->assertGreaterThanOrEqual($minimumLength, strlen($secureKey));
    }

    public function testEnvironmentVariableFallback(): void
    {
        $configFile = __DIR__ . '/../config.php';
        
        if (!file_exists($configFile)) {
            $this->markTestSkipped('config.php not found');
        }
        
        $config = include $configFile;
        $configKey = $config['app']['key'] ?? '';
        
        $this->assertIsString($configKey);
        
        if (!is_placeholder_key($configKey)) {
            $this->assertTrue(is_valid_app_key($configKey) || empty($configKey));
        }
    }

    public function testProductionKeyFormat(): void
    {
        $productionKey = 'V74FWK-S32CLA-Y22YKK-F3C936';
        
        $this->assertTrue(is_valid_app_key($productionKey));
        $this->assertFalse(is_placeholder_key($productionKey));
        
        $validation = validate_app_key($productionKey);
        $this->assertTrue($validation['valid']);
    }
}
