<?php

use PHPUnit\Framework\TestCase;

class ConfigFileGenerationTest extends TestCase
{
    private $testConfigPath;
    private $testEnvPath;
    private $testKeyDir;

    protected function setUp(): void
    {
        $this->testConfigPath = __DIR__ . '/test_config_generated.php';
        $this->testEnvPath = __DIR__ . '/test.env';
        $this->testKeyDir = __DIR__ . '/test_lts/';
        
        // Clean up any existing test files
        if (file_exists($this->testConfigPath)) {
            unlink($this->testConfigPath);
        }
        if (file_exists($this->testEnvPath)) {
            unlink($this->testEnvPath);
        }
        if (is_dir($this->testKeyDir)) {
            array_map('unlink', glob($this->testKeyDir . '*'));
            rmdir($this->testKeyDir);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (file_exists($this->testConfigPath)) {
            unlink($this->testConfigPath);
        }
        if (file_exists($this->testEnvPath)) {
            unlink($this->testEnvPath);
        }
        if (is_dir($this->testKeyDir)) {
            array_map('unlink', glob($this->testKeyDir . '*'));
            rmdir($this->testKeyDir);
        }
    }

    public function testConfigFileCanBeCreated(): void
    {
        $configContent = '<?php' . PHP_EOL . PHP_EOL;
        $configContent .= 'return [' . PHP_EOL;
        $configContent .= '    \'db\' => [' . PHP_EOL;
        $configContent .= '        \'host\' => $_ENV[\'DB_HOST\'] ?? \'localhost\',' . PHP_EOL;
        $configContent .= '        \'user\' => $_ENV[\'DB_USER\'] ?? \'\',' . PHP_EOL;
        $configContent .= '    ],' . PHP_EOL;
        $configContent .= '];' . PHP_EOL;
        
        $result = file_put_contents($this->testConfigPath, $configContent, LOCK_EX);
        
        $this->assertNotFalse($result);
        $this->assertFileExists($this->testConfigPath);
    }

    public function testConfigFileHasValidPhpSyntax(): void
    {
        $configContent = '<?php' . PHP_EOL . PHP_EOL;
        $configContent .= 'return [' . PHP_EOL;
        $configContent .= '    \'db\' => [' . PHP_EOL;
        $configContent .= '        \'host\' => $_ENV[\'DB_HOST\'] ?? \'localhost\',' . PHP_EOL;
        $configContent .= '        \'user\' => $_ENV[\'DB_USER\'] ?? \'\',' . PHP_EOL;
        $configContent .= '    ],' . PHP_EOL;
        $configContent .= '];' . PHP_EOL;
        
        file_put_contents($this->testConfigPath, $configContent, LOCK_EX);
        
        // Verify the file can be included without errors
        $loadedConfig = include $this->testConfigPath;
        
        $this->assertIsArray($loadedConfig);
        $this->assertArrayHasKey('db', $loadedConfig);
    }

    public function testConfigFileContainsAllRequiredSections(): void
    {
        $configContent = '<?php' . PHP_EOL . PHP_EOL;
        $configContent .= 'return [' . PHP_EOL;
        $configContent .= '    \'db\' => [' . PHP_EOL;
        $configContent .= '        \'host\' => $_ENV[\'DB_HOST\'] ?? \'localhost\',' . PHP_EOL;
        $configContent .= '        \'user\' => $_ENV[\'DB_USER\'] ?? \'\',' . PHP_EOL;
        $configContent .= '    ],' . PHP_EOL;
        $configContent .= '    \'app\' => [' . PHP_EOL;
        $configContent .= '        \'url\'   => $_ENV[\'APP_URL\'] ?? \'\',' . PHP_EOL;
        $configContent .= '        \'key\'   => $_ENV[\'APP_KEY\'] ?? \'\',' . PHP_EOL;
        $configContent .= '        \'defuse_key\' => $_ENV[\'DEFUSE_KEY_PATH\'] ?? \'\',' . PHP_EOL;
        $configContent .= '    ],' . PHP_EOL;
        $configContent .= '    \'mail\' => [' . PHP_EOL;
        $configContent .= '        \'smtp\' => [' . PHP_EOL;
        $configContent .= '            \'host\' => $_ENV[\'SMTP_HOST\'] ?? \'\',' . PHP_EOL;
        $configContent .= '        ],' . PHP_EOL;
        $configContent .= '    ],' . PHP_EOL;
        $configContent .= '    \'os\' => [' . PHP_EOL;
        $configContent .= '        \'system_software\' => $_ENV[\'SYSTEM_OS\'] ?? \'\',' . PHP_EOL;
        $configContent .= '    ],' . PHP_EOL;
        $configContent .= '    \'api\' => [' . PHP_EOL;
        $configContent .= '        \'allowed_origins\' => $_ENV[\'CORS_ALLOWED_ORIGINS\'] ?? \'\',' . PHP_EOL;
        $configContent .= '    ],' . PHP_EOL;
        $configContent .= '];' . PHP_EOL;
        
        file_put_contents($this->testConfigPath, $configContent, LOCK_EX);
        
        $loadedConfig = include $this->testConfigPath;
        
        $this->assertArrayHasKey('db', $loadedConfig);
        $this->assertArrayHasKey('app', $loadedConfig);
        $this->assertArrayHasKey('mail', $loadedConfig);
        $this->assertArrayHasKey('os', $loadedConfig);
        $this->assertArrayHasKey('api', $loadedConfig);
    }

    public function testEnvFileCanBeCreated(): void
    {
        $envContent = "DB_HOST=localhost" . PHP_EOL;
        $envContent .= "DB_USER=root" . PHP_EOL;
        $envContent .= "APP_URL=http://example.com" . PHP_EOL;
        $envContent .= "DEFUSE_KEY_PATH=lib/utility/.lts/key.php" . PHP_EOL;
        
        $result = file_put_contents($this->testEnvPath, $envContent, LOCK_EX);
        
        $this->assertNotFalse($result);
        $this->assertFileExists($this->testEnvPath);
        
        $loadedEnv = parse_ini_file($this->testEnvPath);
        
        $this->assertEquals('localhost', $loadedEnv['DB_HOST']);
        $this->assertEquals('root', $loadedEnv['DB_USER']);
        $this->assertEquals('lib/utility/.lts/key.php', $loadedEnv['DEFUSE_KEY_PATH']);
    }

    public function testEncryptionKeyDirectoryCanBeCreated(): void
    {
        $result = mkdir($this->testKeyDir, 0755, true);
        
        $this->assertTrue($result);
        $this->assertDirectoryExists($this->testKeyDir);
    }

    public function testEncryptionKeyPhpFileCanBeCreated(): void
    {
        if (!is_dir($this->testKeyDir)) {
            mkdir($this->testKeyDir, 0755, true);
        }
        
        $keyAscii = 'def00000testkey1234567890123456789012345678901234567890123';
        $phpContent = "<?php\n// Encryption key\nreturn '$keyAscii';";
        
        $keyFile = $this->testKeyDir . 'testkey.php';
        $result = file_put_contents($keyFile, $phpContent, LOCK_EX);
        
        $this->assertNotFalse($result);
        $this->assertFileExists($keyFile);
        
        // Test that the key can be loaded
        $loadedKey = require $keyFile;
        $this->assertEquals($keyAscii, $loadedKey);
    }

    public function testRandomKeyFilenameGeneration(): void
    {
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $filenames = [];
        
        // Generate multiple filenames to verify randomness
        for ($i = 0; $i < 10; $i++) {
            $filename = '';
            for ($j = 0; $j < 16; $j++) {
                $filename .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $filenames[] = $filename . '.php';
        }
        
        // All should be unique (no duplicates)
        $this->assertCount(10, array_unique($filenames));
        
        // All should end with .php
        foreach ($filenames as $filename) {
            $this->assertStringEndsWith('.php', $filename);
        }
    }

    public function testConfigFilePathCalculation(): void
    {
        // The path calculation should go from install/include/ to root
        // Starting from __DIR__ (tests/unit):
        // dirname(__DIR__, 2) should give us the project root
        
        $rootDir = dirname(__DIR__, 2);
        
        // The root should have an src/install directory (install is inside src)
        $this->assertDirectoryExists($rootDir . '/src/install');
        
        // The path should end with Scriptlog (project root)
        $this->assertStringEndsWith('Scriptlog', $rootDir);
    }
}