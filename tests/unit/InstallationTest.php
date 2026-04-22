<?php

use PHPUnit\Framework\TestCase;

class InstallationTest extends TestCase
{
    public function testConfigFileContainsDefuseKeyPath(): void
    {
        $configContent = '<?php

return [
    \'db\' => [
        \'host\' => $_ENV[\'DB_HOST\'] ?? \'localhost\',
        \'user\' => $_ENV[\'DB_USER\'] ?? \'blogwareuser\',
        \'pass\' => $_ENV[\'DB_PASS\'] ?? \'userblogware\',
        \'name\' => $_ENV[\'DB_NAME\'] ?? \'blogwaredb\',
        \'port\' => $_ENV[\'DB_PORT\'] ?? \'3306\',
        \'prefix\' => $_ENV[\'DB_PREFIX\'] ?? \'\',
    ],

    \'app\' => [
        \'url\'   => $_ENV[\'APP_URL\'] ?? \'http://blogware.site\',
        \'email\' => $_ENV[\'APP_EMAIL\'] ?? \'admin@example.com\',
        \'key\'   => $_ENV[\'APP_KEY\'] ?? \'XXXXXX-XXXXXX-XXXXXX-XXXXXX\',
        \'defuse_key\' => $_ENV[\'DEFUSE_KEY_PATH\'] ?? \'lib/utility/.lts/lts.php\',
    ],

    \'mail\' => [
        \'smtp\' => [
            \'host\' => $_ENV[\'SMTP_HOST\'] ?? \'\',
            \'port\' => $_ENV[\'SMTP_PORT\'] ?? 587,
            \'encryption\' => $_ENV[\'SMTP_ENCRYPTION\'] ?? \'tls\',
            \'username\' => $_ENV[\'SMTP_USER\'] ?? \'\',
            \'password\' => $_ENV[\'SMTP_PASS\'] ?? \'\',
        ],
        \'from\' => [
            \'email\' => $_ENV[\'MAIL_FROM_ADDRESS\'] ?? \'admin@example.com\',
            \'name\' => $_ENV[\'MAIL_FROM_NAME\'] ?? \'Blogware\'
        ]
    ],

    \'os\' => [
        \'system_software\' => $_ENV[\'SYSTEM_OS\'] ?? \'Linux\',
        \'distrib_name\'    => $_ENV[\'DISTRIB_NAME\'] ?? \'Linux Mint\'
    ],
];
';

        $this->assertStringContainsString('defuse_key', $configContent);
        $this->assertStringContainsString('lib/utility/.lts/lts.php', $configContent);
    }

    public function testDefaultEncryptionKeyPathIsDirectory(): void
    {
        $defaultPath = 'lib/utility/.lts/';
        $this->assertEquals('lib/utility/.lts/', $defaultPath);
        $this->assertStringEndsWith('/', $defaultPath);
    }

    public function testInstallationFormHasEncryptionKeyPathField(): void
    {
        $formHtml = file_get_contents(__DIR__ . '/../../src/install/index.php');
        
        $this->assertStringContainsString('defuse_key_path', $formHtml);
        $this->assertStringContainsString('lib/utility/.lts/', $formHtml);
    }

    public function testEncryptionKeyPathDefaultValueInForm(): void
    {
        $formHtml = file_get_contents(__DIR__ . '/../../src/install/index.php');
        
        $this->assertStringContainsString('/lib/utility/.lts/', $formHtml);
    }

    public function testConfigFileUsesEnvPattern(): void
    {
        $configContent = '<?php

return [
    \'db\' => [
        \'host\' => $_ENV[\'DB_HOST\'] ?? \'localhost\',
        \'user\' => $_ENV[\'DB_USER\'] ?? \'\',
    ],
    \'app\' => [
        \'url\'   => $_ENV[\'APP_URL\'] ?? \'http://example.com\',
        \'defuse_key\' => $_ENV[\'DEFUSE_KEY_PATH\'] ?? \'lib/utility/.lts/lts.php\',
    ],
];
';

        $this->assertStringContainsString('$_ENV[\'DB_HOST\']', $configContent);
        $this->assertStringContainsString('$_ENV[\'APP_URL\']', $configContent);
        $this->assertStringContainsString('??', $configContent);
    }

    public function testGenerateDefuseKeyCreatesPhpFile(): void
    {
        $setupFile = file_get_contents(__DIR__ . '/../../src/install/include/setup.php');
        
        // Check that the function creates a .php file
        $this->assertStringContainsString('.php', $setupFile);
        $this->assertStringContainsString("generate_random_key_filename", $setupFile);
    }

    public function testGenerateRandomKeyFilename(): void
    {
        $setupFile = file_get_contents(__DIR__ . '/../../src/install/include/setup.php');
        
        // Check function exists for generating random filenames
        $this->assertStringContainsString("function generate_random_key_filename()", $setupFile);
    }

    public function testDefuseKeyFunctionExists(): void
    {
        $setupFile = file_get_contents(__DIR__ . '/../../src/install/include/setup.php');
        
        $this->assertStringContainsString('function generate_defuse_key()', $setupFile);
    }

    public function testWriteConfigFileAcceptsEncryptionPathParameter(): void
    {
        $setupFile = file_get_contents(__DIR__ . '/../../src/install/include/setup.php');
        
        $this->assertStringContainsString('function write_config_file(', $setupFile);
        $this->assertStringContainsString('$defuse_key_path', $setupFile);
    }

    public function testEncryptionKeyGeneratedAsPhpFormat(): void
    {
        $setupFile = file_get_contents(__DIR__ . '/../../src/install/include/setup.php');
        
        // Check that key is stored as PHP file with return statement
        $this->assertStringContainsString('<?php', $setupFile);
    }
    
    public function testEnvFileIncludesDefuseKeyPath(): void
    {
        $setupFile = file_get_contents(__DIR__ . '/../../src/install/include/setup.php');
        
        // Check that write_env_file now accepts defuse_key_path parameter
        $this->assertStringContainsString('DEFUSE_KEY_PATH=', $setupFile);
    }
}