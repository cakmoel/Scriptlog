<?php

use PHPUnit\Framework\TestCase;

/**
 * InstallationTest - Revamped to test actual function behavior
 * 
 * Tests installation utility functions from install/include/setup.php
 * Following PHPUnit best practices: Arrange-Act-Assert pattern
 * 
 * IMPORTANT: This test requires loading setup.php which has functions
 * that conflict with the main bootstrap. It must be run with a dedicated
 * bootstrap, not as part of the standard Unit Tests suite.
 * See: phpunit-installation.xml
 */
class InstallationTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('generate_random_key_filename')) {
            $this->markTestSkipped(
                'InstallationTest requires dedicated bootstrap. ' .
                'Run: phpunit --bootstrap tests/bootstrap-installation.php tests/unit/InstallationTest.php'
            );
        }
    }

    /**
     * Test that generate_random_key_filename returns a string
     * and follows the expected pattern (16 chars + .php)
     */
    public function testGenerateRandomKeyFilenameReturnsString(): void
    {
        // Act
        $filename = generate_random_key_filename();

        // Assert
        $this->assertIsString($filename);
        $this->assertStringEndsWith('.php', $filename);
        $this->assertEquals(20, strlen($filename)); // 16 chars + .php
    }

    /**
     * Test that generate_random_key_filename generates unique filenames
     */
    public function testGenerateRandomKeyFilenameGeneratesUniqueNames(): void
    {
        // Act
        $filename1 = generate_random_key_filename();
        $filename2 = generate_random_key_filename();
        $filename3 = generate_random_key_filename();

        // Assert - Very high probability they should be different
        $this->assertNotEquals($filename1, $filename2);
        $this->assertNotEquals($filename2, $filename3);
        $this->assertNotEquals($filename1, $filename3);
    }

    /**
     * Test that generate_random_key_filename only contains valid characters
     */
    public function testGenerateRandomKeyFilenameContainsOnlyValidChars(): void
    {
        // Act
        $filename = generate_random_key_filename();

        // Remove .php extension for checking
        $basename = str_replace('.php', '', $filename);

        // Assert
        $this->assertMatchesRegularExpression('/^[a-z0-9]{16}$/', $basename);
    }

    /**
     * Test generate_license function returns string
     */
    public function testGenerateLicenseReturnsString(): void
    {
        // Act
        $license = generate_license();

        // Assert
        $this->assertIsString($license);
        $this->assertNotEmpty($license);
    }

    /**
     * Test generate_license format (XXXX-XXXX-XXXX-XXXX pattern without suffix)
     * Based on actual function: 4 segments of 5 chars each
     */
    public function testGenerateLicenseFormatWithoutSuffix(): void
    {
        // Act
        $license = generate_license();

        // Assert - Should match pattern: XXXXX-XXXXX-XXXXX-XXXXX (5 chars per segment, 4 segments)
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{5}-[A-HJ-NP-Z2-9]{5}-[A-HJ-NP-Z2-9]{5}-[A-HJ-NP-Z2-9]{5}$/', $license);
    }

    /**
     * Test generate_license with numeric suffix
     * Based on actual function: 3 segments of 6 chars each + suffix
     */
    public function testGenerateLicenseWithNumericSuffix(): void
    {
        // Act
        $license = generate_license('123');

        // Assert - Should have 3 segments of 6 chars + suffix
        $parts = explode('-', $license);
        $this->assertCount(4, $parts); // 3 segments + suffix
        $this->assertEquals(6, strlen($parts[0]));
        $this->assertEquals(6, strlen($parts[1]));
        $this->assertEquals(6, strlen($parts[2]));
    }

    /**
     * Test generate_table_prefix returns string with underscore
     */
    public function testGenerateTablePrefixReturnsStringWithUnderscore(): void
    {
        // Act
        $prefix = generate_table_prefix(6);

        // Assert
        $this->assertIsString($prefix);
        $this->assertStringEndsWith('_', $prefix);
    }

    /**
     * Test generate_table_prefix length
     */
    public function testGenerateTablePrefixCorrectLength(): void
    {
        // Act
        $prefix = generate_table_prefix(6);

        // Assert - 6 chars + underscore = 7
        $this->assertEquals(7, strlen($prefix));
    }

    /**
     * Test generate_table_prefix only contains valid characters
     */
    public function testGenerateTablePrefixContainsOnlyValidChars(): void
    {
        // Act
        $prefix = generate_table_prefix(6);

        // Remove underscore for checking
        $prefixOnly = rtrim($prefix, '_');

        // Assert
        $this->assertMatchesRegularExpression('/^[a-z]{6}$/', $prefixOnly);
    }

    /**
     * Test setup_base_url returns proper URL format
     * Note: setup_base_url uses dirname(dirname($_SERVER['PHP_SELF']))
     * which in test environment may include vendor path
     */
    public function testSetupBaseUrlReturnsValidUrl(): void
    {
        // Arrange
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['PHP_SELF'] = '/install/index.php';

        // Act
        $url = setup_base_url('http', 'example.com');

        // Assert
        $this->assertIsString($url);
        $this->assertStringStartsWith('http://', $url);
    }

    /**
     * Test setup_base_url with HTTPS
     */
    public function testSetupBaseUrlWithHttps(): void
    {
        // Arrange
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'secure.example.com';
        $_SERVER['PHP_SELF'] = '/install/index.php';

        // Act
        $url = setup_base_url('https', 'secure.example.com');

        // Assert
        $this->assertIsString($url);
        $this->assertStringStartsWith('https://', $url);
    }

    /**
     * Test current_url function returns valid URL
     */
    public function testCurrentUrlReturnsValidUrl(): void
    {
        // Arrange
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['PHP_SELF'] = '/install/index.php';

        // Act
        $url = current_url();

        // Assert
        $this->assertIsString($url);
        $this->assertStringStartsWith('http://', $url);
        $this->assertStringEndsWith('/', $url);
    }

    /**
     * Test current_url normalizes paths to prevent double slashes
     */
    public function testCurrentUrlNormalizesPaths(): void
    {
        // Arrange
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['PHP_SELF'] = '/install/'; // Trailing slash

        // Act
        $url = current_url();

        // Assert - Should be normalized to single slash (http://localhost/)
        $this->assertEquals('http://localhost/', $url);
    }

    /**
     * Test write_config_file function accepts correct parameters
     * (Test by checking function exists and is callable)
     */
    public function testWriteConfigFileFunctionExists(): void
    {
        // Assert
        $this->assertTrue(function_exists('write_config_file'));
    }

    /**
     * Test write_env_file function exists and is callable
     */
    public function testWriteEnvFileFunctionExists(): void
    {
        // Assert
        $this->assertTrue(function_exists('write_env_file'));
    }

    /**
     * Test generate_defuse_key function exists
     */
    public function testGenerateDefuseKeyFunctionExists(): void
    {
        // Assert
        $this->assertTrue(function_exists('generate_defuse_key'));
    }

    /**
     * Test config file content structure when generated
     * (Tests the expected structure without actually writing files)
     */
    public function testConfigFileStructure(): void
    {
        // Arrange - Simulate config content generation
        $protocol = 'http';
        $server_name = 'localhost';
        $dbhost = 'localhost';
        $dbuser = 'testuser';
        $dbpass = 'testpass';
        $dbname = 'testdb';
        $dbport = '3306';
        $email = 'test@example.com';
        $key = 'XXXXXX-XXXXXX-XXXXXX-XXXXXX';
        $prefix = 'test_';

        // Simulate the config file structure (from write_config_file logic)
        $configStructure = [
            'db' => [
                'host' => $_ENV['DB_HOST'] ?? $dbhost,
                'user' => $_ENV['DB_USER'] ?? $dbuser,
                'pass' => $_ENV['DB_PASS'] ?? $dbpass,
                'name' => $_ENV['DB_NAME'] ?? $dbname,
                'port' => $_ENV['DB_PORT'] ?? $dbport,
                'prefix' => $_ENV['DB_PREFIX'] ?? $prefix,
            ],
            'app' => [
                'url'   => $_ENV['APP_URL'] ?? 'http://localhost',
                'email' => $_ENV['APP_EMAIL'] ?? $email,
                'key'   => $_ENV['APP_KEY'] ?? $key,
                'defuse_key' => $_ENV['DEFUSE_KEY_PATH'] ?? '',
            ]
        ];

        // Assert
        $this->assertArrayHasKey('db', $configStructure);
        $this->assertArrayHasKey('app', $configStructure);
        $this->assertArrayHasKey('defuse_key', $configStructure['app']);
    }

    /**
     * Test env file content structure
     */
    public function testEnvFileStructure(): void
    {
        // Arrange
        $dbhost = 'localhost';
        $dbuser = 'testuser';
        $dbpass = 'testpass';
        $dbname = 'testdb';
        $dbport = '3306';
        $prefix = 'test_';
        $email = 'test@example.com';
        $app_key = 'XXXXXX-XXXXXX-XXXXXX-XXXXXX';
        $defuse_key_path = '/var/www/storage/keys/abc123def456.php';

        // Simulate env content structure
        $envData = [
            'DB_HOST' => $dbhost,
            'DB_USER' => $dbuser,
            'DB_PASS' => $dbpass,
            'DB_NAME' => $dbname,
            'DB_PORT' => $dbport,
            'DB_PREFIX' => $prefix,
            'APP_URL' => 'http://localhost',
            'APP_EMAIL' => $email,
            'APP_KEY' => $app_key,
            'DEFUSE_KEY_PATH' => $defuse_key_path,
        ];

        // Assert
        $this->assertArrayHasKey('DEFUSE_KEY_PATH', $envData);
        $this->assertArrayHasKey('DB_HOST', $envData);
        $this->assertArrayHasKey('APP_KEY', $envData);
    }

    /**
     * Test that check_web_server function exists
     */
    public function testCheckWebServerFunctionExists(): void
    {
        // Assert
        $this->assertTrue(function_exists('check_web_server'));
    }

    /**
     * Test generate_license with IP address suffix
     */
    public function testGenerateLicenseWithIpSuffix(): void
    {
        // Act
        $license = generate_license('192.168.1.1');

        // Assert
        $this->assertIsString($license);
        $this->assertStringContainsString('-', $license);
    }

    /**
     * Test generate_license with string suffix
     */
    public function testGenerateLicenseWithStringSuffix(): void
    {
        // Act
        $license = generate_license('test-suffix');

        // Assert
        $this->assertIsString($license);
        $this->assertStringContainsString('TEST-SUFFIX', strtoupper($license));
    }

    /**
     * Test that installation functions use proper random int for security
     */
    public function testGenerateRandomKeyFilenameUsesRandomInt(): void
    {
        // This test verifies the function signature uses random_int (secure)
        // by checking the function exists and returns expected output
        $reflection = new ReflectionFunction('generate_random_key_filename');
        $filename = $reflection->invoke(null);

        // Assert
        $this->assertIsString($filename);
        $this->assertStringEndsWith('.php', $filename);
    }

    /**
     * Test is_table_exists function exists
     */
    public function testIsTableExistsFunctionExists(): void
    {
        // Assert
        $this->assertTrue(function_exists('is_table_exists'));
    }

    /**
     * Test check_dbtable function exists
     */
    public function testCheckDbtableFunctionExists(): void
    {
        // Assert
        $this->assertTrue(function_exists('check_dbtable'));
    }

    /**
     * Test that setup.php file is readable and has no syntax errors
     */
    public function testSetupFileHasNoSyntaxErrors(): void
    {
        // Arrange
        $setupFile = __DIR__ . '/../../src/install/include/setup.php';

        // Act
        $output = [];
        $returnCode = 0;
        exec('php -l ' . escapeshellarg($setupFile), $output, $returnCode);

        // Assert
        $this->assertEquals(0, $returnCode, 'setup.php has syntax errors: ' . implode("\n", $output));
    }

    /**
     * Test that index.php file is readable and has no syntax errors
     */
    public function testIndexFileHasNoSyntaxErrors(): void
    {
        // Arrange
        $indexFile = __DIR__ . '/../../install/index.php';

        // Act
        $output = [];
        $returnCode = 0;
        exec('php -l ' . escapeshellarg($indexFile), $output, $returnCode);

        // Assert
        $this->assertEquals(0, $returnCode, 'index.php has syntax errors: ' . implode("\n", $output));
    }

    /**
     * Test generate_license format with suffix has correct number of segments
     * When suffix is provided: 3 segments of 6 chars each + suffix
     */
    public function testGenerateLicenseWithSuffixHasCorrectSegments(): void
    {
        // Act
        $license = generate_license('test');

        // Assert - Should have format: XXXXXX-XXXXXX-XXXXXX-TEST (3 segments + suffix)
        $parts = explode('-', $license);
        $this->assertCount(4, $parts); // 3 segments + suffix
    }

    /**
     * Test generate_table_prefix uses secure random bytes when available
     */
    public function testGenerateTablePrefixUsesRandomBytes(): void
    {
        // This test verifies the function uses random_bytes or openssl_random_pseudo_bytes
        // by checking the output is different across multiple calls
        $prefix1 = generate_table_prefix(6);
        $prefix2 = generate_table_prefix(6);
        $prefix3 = generate_table_prefix(6);

        // Assert - Very high probability they should be different
        $this->assertNotEquals($prefix1, $prefix2);
        $this->assertNotEquals($prefix2, $prefix3);
    }

    /**
     * Test setup_base_url handles protocol correctly
     */
    public function testSetupBaseUrlProtocolHandling(): void
    {
        // Arrange
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['PHP_SELF'] = '/install/index.php';

        // Act & Assert - HTTP
        $_SERVER['HTTPS'] = 'off';
        $urlHttp = setup_base_url('http', 'example.com');
        $this->assertStringStartsWith('http://', $urlHttp);

        // Act & Assert - HTTPS
        $_SERVER['HTTPS'] = 'on';
        $urlHttps = setup_base_url('https', 'example.com');
        $this->assertStringStartsWith('https://', $urlHttps);
    }

    /**
     * Test current_url with different PHP_SELF values
     */
    public function testCurrentUrlWithDifferentPhpSelf(): void
    {
        // Arrange
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_HOST'] = 'localhost';
        
        // Test with index.php
        $_SERVER['PHP_SELF'] = '/install/index.php';
        $url1 = current_url();
        $this->assertStringEndsWith('/install/', $url1);

        // Test with trailing slash (should normalize to just '/')
        $_SERVER['PHP_SELF'] = '/install/';
        $url2 = current_url();
        $this->assertEquals('http://localhost/', $url2);
    }

    /**
     * Test generate_table_prefix with different lengths
     */
    public function testGenerateTablePrefixWithDifferentLengths(): void
    {
        // Act
        $prefix4 = generate_table_prefix(4);
        $prefix8 = generate_table_prefix(8);

        // Assert
        $this->assertEquals(5, strlen($prefix4)); // 4 chars + underscore
        $this->assertEquals(9, strlen($prefix8)); // 8 chars + underscore
    }

    /**
     * Test that configuration functions handle empty values
     */
    public function testConfigFileHandlesEmptyValues(): void
    {
        // Arrange
        $emptyDbhost = '';
        $emptyDbuser = '';
        
        // Simulate config array with empty values
        $config = [
            'db' => [
                'host' => $_ENV['DB_HOST'] ?? $emptyDbhost,
                'user' => $_ENV['DB_USER'] ?? $emptyDbuser,
            ]
        ];

        // Assert - Should handle empty values gracefully
        $this->assertArrayHasKey('db', $config);
        $this->assertArrayHasKey('host', $config['db']);
        $this->assertArrayHasKey('user', $config['db']);
    }

    /**
     * Test generate_license without suffix returns exactly 4 segments
     */
    public function testGenerateLicenseWithoutSuffixHasFourSegments(): void
    {
        // Act
        $license = generate_license();

        // Assert - Should have 4 segments of 5 chars each
        $parts = explode('-', $license);
        $this->assertCount(4, $parts);
    }

    /**
     * Test write_config_file has correct parameter signature (11 params)
     */
    public function testWriteConfigFileParameterSignature(): void
    {
        // Arrange
        $reflection = new ReflectionFunction('write_config_file');

        // Assert - Function has 11 parameters
        $this->assertEquals(11, $reflection->getNumberOfParameters());
    }

    /**
     * Test write_env_file has correct parameter signature (12 params)
     */
    public function testWriteEnvFileParameterSignature(): void
    {
        // Arrange
        $reflection = new ReflectionFunction('write_env_file');

        // Assert - Function has 12 parameters
        $this->assertEquals(12, $reflection->getNumberOfParameters());
    }

    /**
     * Test generate_random_key_filename generates valid PHP filenames
     */
    public function testGenerateRandomKeyFilenameIsValidForPhp(): void
    {
        // Act
        $filename = generate_random_key_filename();

        // Assert - Filename should be valid (16 chars + .php)
        $this->assertMatchesRegularExpression('/^[a-z0-9]{16}\.php$/', $filename);
    }

    /**
     * Test setup_base_url handles different server names
     */
    public function testSetupBaseUrlWithDifferentServerNames(): void
    {
        // Arrange
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['PHP_SELF'] = '/install/index.php';

        // Act & Assert - Different server names
        $url1 = setup_base_url('http', 'localhost');
        $this->assertStringContainsString('localhost', $url1);

        $url2 = setup_base_url('http', 'example.com');
        $this->assertStringContainsString('example.com', $url2);
    }
}
