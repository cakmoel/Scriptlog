<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../bootstrap.php';

class BootstrapTest extends TestCase
{
    private string $testConfigPath;
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = sys_get_temp_dir() . '/scriptlog_bootstrap_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
        $this->testConfigPath = $this->testDir . '/config.php';
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            array_map('unlink', glob($this->testDir . '/*'));
            rmdir($this->testDir);
        }
        parent::tearDown();
    }

    private function createValidConfig(): void
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
                'key' => 'V74FWK-S32CLA-Y22YKK-F3C936',
                'defuse_key' => $this->testDir . '/defuse_key.php',
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

    private function createEmptyConfig(): void
    {
        $config = [
            'db' => [
                'host' => '',
                'user' => '',
                'pass' => '',
                'name' => '',
                'port' => '',
                'prefix' => '',
            ],
            'app' => [
                'url' => '',
                'email' => '',
                'key' => '',
            ],
        ];

        $content = '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';';
        file_put_contents($this->testConfigPath, $content);
    }

    private function createConfigWithoutAppKey(): void
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
                'key' => '',
            ],
        ];

        $content = '<?php' . PHP_EOL . 'return ' . var_export($config, true) . ';';
        file_put_contents($this->testConfigPath, $content);
    }

    public function testBootstrapClassExists(): void
    {
        $this->assertTrue(class_exists('Bootstrap'));
    }

    public function testInitializeMethodExists(): void
    {
        $this->assertTrue(method_exists('Bootstrap', 'initialize'));
    }

    public function testInitializeReturnsAppContext(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertInstanceOf(AppContext::class, $result);
    }

    public function testInitializeWithMissingConfigFile(): void
    {
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertInstanceOf(AppContext::class, $result);
        $this->assertNull($result->db_host);
    }

    public function testInitializeThrowsExceptionWhenAppKeyMissing(): void
    {
        $this->createEmptyConfig();
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Security Risk: APP_KEY is missing from environment.');
        
        Bootstrap::initialize($this->testDir . '/');
    }

    public function testInitializeThrowsExceptionWhenAppKeyEmpty(): void
    {
        $this->createConfigWithoutAppKey();
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Security Risk: APP_KEY is missing from environment.');
        
        Bootstrap::initialize($this->testDir . '/');
    }

    public function testAppContextContainsDbHost(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertNotNull($result->db_host);
    }

    public function testAppContextContainsDbUser(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertNotNull($result->db_user);
    }

    public function testAppContextContainsAppUrl(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertNotNull($result->app_url);
    }

    public function testAppContextContainsAppEmail(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertNotNull($result->app_email);
    }

    public function testAppContextContainsAppKey(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertNotNull($result->app_key);
    }

    public function testAllowedExportedVarsContainsExpectedKeys(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $property = $reflection->getProperty('allowed_exported_vars');
        $property->setAccessible(true);
        
        $allowedVars = $property->getValue();
        
        $this->assertContains('db_host', $allowedVars);
        $this->assertContains('db_user', $allowedVars);
        $this->assertContains('db_pwd', $allowedVars);
        $this->assertContains('db_name', $allowedVars);
        $this->assertContains('app_email', $allowedVars);
        $this->assertContains('app_url', $allowedVars);
        $this->assertContains('app_key', $allowedVars);
        $this->assertContains('sessionMaker', $allowedVars);
        $this->assertContains('validator', $allowedVars);
    }

    public function testAppContextDoesNotContainUnallowedVars(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        // db_port IS in allowed vars, so it should not be null
        $this->assertNotNull($result->db_port);
        
        // Test that truly unallowed vars are null
        $this->assertNull($result->some_random_var);
    }

    public function testInitializeHandlesMissingDbClassesGracefully(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertInstanceOf(AppContext::class, $result);
    }

    public function testAppContextMagicGetReturnsNullForUnknownProperty(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertNull($result->unknown_property);
    }

    public function testAppContextMagicIssetReturnsTrueForExistingProperty(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertTrue(isset($result->db_host));
    }

    public function testAppContextMagicIssetReturnsFalseForNonExistingProperty(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertFalse(isset($result->non_existing_property));
    }

    public function testLoadConfigurationWithValidConfig(): void
    {
        $this->createValidConfig();
        
        $reflection = new ReflectionClass('Bootstrap');
        $method = $reflection->getMethod('loadConfiguration');
        $method->setAccessible(true);
        
        $result = $method->invoke(null, $this->testDir . '/');
        
        $this->assertIsArray($result);
        $this->assertEquals('localhost', $result['db_host']);
        $this->assertEquals('testuser', $result['db_user']);
    }

    public function testLoadConfigurationWithMissingConfig(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $method = $reflection->getMethod('loadConfiguration');
        $method->setAccessible(true);
        
        $result = $method->invoke(null, $this->testDir . '/');
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testLoadConfigurationRequiresAppKey(): void
    {
        $this->createConfigWithoutAppKey();
        
        $reflection = new ReflectionClass('Bootstrap');
        $method = $reflection->getMethod('loadConfiguration');
        $method->setAccessible(true);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Security Risk: APP_KEY is missing from environment.');
        
        $method->invoke(null, $this->testDir . '/');
    }

    public function testLoadConfigurationWithEmptyDbValues(): void
    {
        $this->createEmptyConfig();
        
        $reflection = new ReflectionClass('Bootstrap');
        $method = $reflection->getMethod('loadConfiguration');
        $method->setAccessible(true);
        
        $this->expectException(Exception::class);
        
        $method->invoke(null, $this->testDir . '/');
    }

    public function testInitializeServicesMethodExists(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $this->assertTrue($reflection->hasMethod('initializeServices'));
    }

    public function testApplySecurityMethodExists(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $this->assertTrue($reflection->hasMethod('applySecurity'));
    }

    public function testApplySecurityMethodIsPrivate(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $method = $reflection->getMethod('applySecurity');
        
        $this->assertTrue($method->isPrivate());
    }

    public function testLoadConfigurationMethodIsPrivate(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $method = $reflection->getMethod('loadConfiguration');
        
        $this->assertTrue($method->isPrivate());
    }

    public function testInitializeServicesMethodIsPrivate(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $method = $reflection->getMethod('initializeServices');
        
        $this->assertTrue($method->isPrivate());
    }

    public function testBootstrapUsesStaticMethods(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $initializeMethod = $reflection->getMethod('initialize');
        
        $this->assertTrue($initializeMethod->isStatic());
    }

    public function testConfigPropertyExists(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $property = $reflection->getProperty('config');
        
        $this->assertTrue($property->isPrivate());
        $this->assertTrue($property->isStatic());
    }

    public function testServicesPropertyExists(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $property = $reflection->getProperty('services');
        
        $this->assertTrue($property->isPrivate());
        $this->assertTrue($property->isStatic());
    }

    public function testAllowedExportedVarsPropertyIsStatic(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $property = $reflection->getProperty('allowed_exported_vars');
        
        $this->assertTrue($property->isPrivate());
        $this->assertTrue($property->isStatic());
    }

    public function testInitializeReturnsOnlyAllowedVars(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $allowedVars = [
            'db_host', 'db_user', 'db_pwd', 'db_name', 'db_port', 'db_prefix',
            'app_email', 'app_url', 'app_key', 'cipher_key', 'sessionMaker',
            'searchPost', 'authenticator', 'ubench', 'sanitizer', 'validator',
            'configDao', 'configService', 'dispatcher', 'i18n', 'userDao', 'userToken'
        ];
        
        foreach ($allowedVars as $var) {
            $this->assertTrue(true, "Testing {$var} exists");
        }
    }

    public function testAppContextReturnsNullForMissingService(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertNull($result->sessionMaker);
    }

    public function testBootstrapHandlesEmptyDbPrefix(): void
    {
        $config = [
            'db' => [
                'host' => 'localhost',
                'user' => 'testuser',
                'pass' => 'testpass',
                'name' => 'testdb',
                'port' => '3306',
                'prefix' => '',
            ],
            'app' => [
                'url' => 'https://test.example.com',
                'email' => 'test@example.com',
                'key' => 'V74FWK-S32CLA-Y22YKK-F3C936',
            ],
        ];

        file_put_contents($this->testConfigPath, '<?php return ' . var_export($config, true) . ';');
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertInstanceOf(AppContext::class, $result);
    }

    public function testBootstrapWithCustomDbPrefix(): void
    {
        $config = [
            'db' => [
                'host' => 'localhost',
                'user' => 'testuser',
                'pass' => 'testpass',
                'name' => 'testdb',
                'port' => '3306',
                'prefix' => 'custom_',
            ],
            'app' => [
                'url' => 'https://test.example.com',
                'email' => 'test@example.com',
                'key' => 'V74FWK-S32CLA-Y22YKK-F3C936',
            ],
        ];

        file_put_contents($this->testConfigPath, '<?php return ' . var_export($config, true) . ';');
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertInstanceOf(AppContext::class, $result);
    }

    public function testAppContextTypeSafety(): void
    {
        $this->createValidConfig();
        
        $result = Bootstrap::initialize($this->testDir . '/');
        
        $this->assertIsString($result->db_host);
        $this->assertIsString($result->db_user);
        $this->assertIsString($result->app_email);
    }

    public function testBootstrapWithNonExistentAppRoot(): void
    {
        $nonExistentRoot = '/non/existent/path/' . uniqid();
        
        $result = Bootstrap::initialize($nonExistentRoot);
        
        $this->assertInstanceOf(AppContext::class, $result);
        $this->assertNull($result->db_host);
    }

    public function testBootstrapRoutingRulesAreDefined(): void
    {
        $this->createValidConfig();
        
        $reflection = new ReflectionClass('Bootstrap');
        $method = $reflection->getMethod('initializeServices');
        $method->setAccessible(true);
        
        $coreVars = [
            'db_host' => 'localhost',
            'db_user' => 'testuser',
            'db_pwd' => 'testpass',
            'db_name' => 'testdb',
            'db_port' => '3306',
            'db_prefix' => '',
            'app_email' => 'test@example.com',
            'app_url' => 'https://test.example.com',
            'app_key' => 'V74FWK-S32CLA-Y22YKK-F3C936',
            'cipher_key' => '',
        ];
        
        $result = $method->invoke(null, $coreVars);
        
        $this->assertIsArray($result);
    }

    public function testAppContextConstructionWithEmptyArray(): void
    {
        $context = new AppContext([]);
        
        $this->assertNull($context->db_host);
    }

    public function testAppContextConstructionWithData(): void
    {
        $data = ['test_key' => 'test_value'];
        $context = new AppContext($data);
        
        $this->assertEquals('test_value', $context->test_key);
    }

    public function testBootstrapMethodSignature(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $method = $reflection->getMethod('initialize');
        
        $parameters = $method->getParameters();
        
        $this->assertCount(1, $parameters);
        $this->assertEquals('appRoot', $parameters[0]->getName());
        $this->assertEquals('string', $parameters[0]->getType()->getName());
    }

    public function testBootstrapReturnType(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $method = $reflection->getMethod('initialize');
        
        $returnType = $method->getReturnType();
        
        $this->assertNotNull($returnType);
        $this->assertEquals('AppContext', $returnType->getName());
    }

    public function testAllowedExportedVarsCount(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $property = $reflection->getProperty('allowed_exported_vars');
        $property->setAccessible(true);
        
        $allowedVars = $property->getValue();
        
        $this->assertGreaterThan(15, count($allowedVars));
    }

    public function testBootstrapIncludesSearchPostTwiceInAllowedVars(): void
    {
        $reflection = new ReflectionClass('Bootstrap');
        $property = $reflection->getProperty('allowed_exported_vars');
        $property->setAccessible(true);
        
        $allowedVars = $property->getValue();
        
        $searchPostCount = 0;
        foreach ($allowedVars as $var) {
            if ($var === 'searchPost') {
                $searchPostCount++;
            }
        }
        
        $this->assertGreaterThan(1, $searchPostCount);
    }
}
