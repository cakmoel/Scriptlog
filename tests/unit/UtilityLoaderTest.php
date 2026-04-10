<?php

use PHPUnit\Framework\TestCase;

class UtilityLoaderTest extends TestCase
{
    private string $utilityLoaderPath;
    private string $utilityDir;
    private string $generatorPath;
    private string $backupPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->utilityLoaderPath = __DIR__ . '/../../src/lib/utility-loader.php';
        $this->utilityDir = __DIR__ . '/../../src/lib/utility/';
        $this->generatorPath = __DIR__ . '/../../src/generate-utility-list.php';
        $this->backupPath = sys_get_temp_dir() . '/utility-loader-backup-' . uniqid() . '.php';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->backupPath)) {
            unlink($this->backupPath);
        }
        parent::tearDown();
    }

    public function testUtilityLoaderFileExists(): void
    {
        $this->assertFileExists($this->utilityLoaderPath);
    }

    public function testUtilityLoaderIsValidPhp(): void
    {
        $output = [];
        $returnCode = 0;
        exec('php -l ' . escapeshellarg($this->utilityLoaderPath) . ' 2>&1', $output, $returnCode);
        
        $this->assertEquals(0, $returnCode, implode("\n", $output));
    }

    public function testUtilityLoaderContainsLoadCoreUtilitiesFunction(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        $this->assertStringContainsString('function load_core_utilities()', $content);
    }

    public function testUtilityLoaderHasFunctionGuard(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        $this->assertStringContainsString("if (!function_exists('load_core_utilities'))", $content);
    }

    public function testUtilityLoaderDefinesUtilityFilesArray(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        $this->assertStringContainsString('$utility_files = [', $content);
    }

    public function testUtilityLoaderDefinesUtilityDirectory(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        $this->assertStringContainsString('$utility_dir = __DIR__', $content);
    }

    public function testUtilityLoaderContainsRequireOnce(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        $this->assertStringContainsString('require_once', $content);
    }

    public function testUtilityLoaderHasGeneratedWarning(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        $this->assertStringContainsString('GENERATED UTILITY LOADER FILE', $content);
        $this->assertStringContainsString('generate-utility-list.php', $content);
    }

    public function testLoadCoreUtilitiesFunctionExists(): void
    {
        require_once $this->utilityLoaderPath;
        $this->assertTrue(function_exists('load_core_utilities'));
    }

    public function testLoadCoreUtilitiesIsCallable(): void
    {
        require_once $this->utilityLoaderPath;
        $this->assertTrue(is_callable('load_core_utilities'));
    }

    public function testUtilityFilesArrayContainsMultipleEntries(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        preg_match('/\$utility_files = \[(.*?)\];/s', $content, $matches);
        
        $this->assertNotEmpty($matches);
        $this->assertStringContainsString("'", $matches[1]);
    }

    public function testUtilityLoaderListsAtLeast100Files(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        preg_match_all('/\'([a-z0-9-]+\.php)\',/', $content, $matches);
        
        $this->assertGreaterThan(100, count($matches[1]));
    }

    public function testUtilityLoaderIncludesCommonUtilityFiles(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        
        $this->assertStringContainsString("'absolute-path.php'", $content);
        $this->assertStringContainsString("'absolute-url.php'", $content);
        $this->assertStringContainsString("'sanitizer.php'", $content);
    }

    public function testUtilityDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->utilityDir);
    }

    public function testUtilityDirectoryContainsPhpFiles(): void
    {
        $phpFiles = glob($this->utilityDir . '*.php');
        $this->assertNotEmpty($phpFiles);
    }

    public function testUtilityDirectoryHasAtLeast100PhpFiles(): void
    {
        $phpFiles = glob($this->utilityDir . '*.php');
        $this->assertGreaterThan(100, count($phpFiles));
    }

    public function testUtilityLoaderListsMatchesActualFiles(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        preg_match_all('/\'([a-z0-9-]+\.php)\',/', $content, $matches);
        
        $listedFiles = $matches[1];
        $actualFiles = array_map('basename', glob($this->utilityDir . '*.php'));
        
        $diff = array_diff($listedFiles, $actualFiles);
        $this->assertEmpty($diff, 'Files in loader that do not exist: ' . implode(', ', $diff));
    }

    public function testActualUtilityFilesMatchListedFiles(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        preg_match_all('/\'([a-z0-9-]+\.php)\',/', $content, $matches);
        
        $listedFiles = $matches[1];
        $actualFiles = array_map('basename', glob($this->utilityDir . '*.php'));
        
        $diff = array_diff($actualFiles, $listedFiles);
        $this->assertEmpty($diff, 'Actual files not in loader: ' . implode(', ', $diff));
    }

    public function testGenerateUtilityListScriptExists(): void
    {
        $this->assertFileExists($this->generatorPath);
    }

    public function testGenerateUtilityListScriptIsValidPhp(): void
    {
        $output = [];
        $returnCode = 0;
        exec('php -l ' . escapeshellarg($this->generatorPath) . ' 2>&1', $output, $returnCode);
        
        $this->assertEquals(0, $returnCode, implode("\n", $output));
    }

    public function testGenerateUtilityListScriptDefinesUtilityDir(): void
    {
        $content = file_get_contents($this->generatorPath);
        $this->assertStringContainsString('$utility_dir', $content);
        $this->assertStringContainsString("'/lib/utility/'", $content);
    }

    public function testGenerateUtilityListScriptDefinesOutputFile(): void
    {
        $content = file_get_contents($this->generatorPath);
        $this->assertStringContainsString('$output_file', $content);
        $this->assertStringContainsString("'/lib/utility-loader.php'", $content);
    }

    public function testGenerateUtilityListUsesGlob(): void
    {
        $content = file_get_contents($this->generatorPath);
        $this->assertStringContainsString('glob(', $content);
    }

    public function testGenerateUtilityListUsesBasename(): void
    {
        $content = file_get_contents($this->generatorPath);
        $this->assertStringContainsString('basename', $content);
    }

    public function testGenerateUtilityListWritesToFile(): void
    {
        $content = file_get_contents($this->generatorPath);
        $this->assertStringContainsString('file_put_contents', $content);
    }

    public function testGenerateUtilityListOutputsSuccessMessage(): void
    {
        $content = file_get_contents($this->generatorPath);
        $this->assertStringContainsString('Successfully generated', $content);
    }

    public function testGenerateUtilityListOutputsFileCount(): void
    {
        $content = file_get_contents($this->generatorPath);
        $this->assertStringContainsString('count($utility_files)', $content);
    }

    public function testGenerateUtilityListCreatesProperFormat(): void
    {
        copy($this->utilityLoaderPath, $this->backupPath);
        
        ob_start();
        include $this->generatorPath;
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Successfully generated', $output);
        
        $newContent = file_get_contents($this->utilityLoaderPath);
        $this->assertStringStartsWith('<?php', $newContent);
    }

    public function testUtilityLoaderReturnsOnFunctionExists(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        $this->assertStringContainsString("if (function_exists('load_core_utilities'))", $content);
    }

    public function testUtilityLoaderReturnsLoadCoreUtilities(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        $this->assertStringContainsString('return load_core_utilities()', $content);
    }

    public function testUtilityLoaderUsesDirectorySeparator(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        $this->assertStringContainsString('DIRECTORY_SEPARATOR', $content);
    }

    public function testUtilityLoaderUsesForeachLoop(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        $this->assertStringContainsString('foreach', $content);
    }

    public function testSpecificUtilityFilesExist(): void
    {
        $utilities = [
            'absolute-path.php',
            'absolute-url.php',
            'sanitizer.php',
            'escape-html.php',
            'email-validation.php',
            'url-validation.php',
            'make-slug.php',
            'get-ip-address.php',
        ];

        foreach ($utilities as $utility) {
            $this->assertFileExists($this->utilityDir . $utility, "Utility file $utility should exist");
        }
    }

    public function testUtilityLoaderContainsSecurityRelatedFiles(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        
        $this->assertStringContainsString('csrf-defender.php', $content);
        $this->assertStringContainsString('escape-html.php', $content);
        $this->assertStringContainsString('sanitizer.php', $content);
    }

    public function testUtilityLoaderContainsValidationFiles(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        
        $this->assertStringContainsString('email-validation.php', $content);
        $this->assertStringContainsString('url-validation.php', $content);
    }

    public function testUtilityLoaderContainsUploadFiles(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        
        $this->assertStringContainsString('upload-media.php', $content);
        $this->assertStringContainsString('upload-photo.php', $content);
    }

    public function testUtilityLoaderContainsDatabaseFiles(): void
    {
        $content = file_get_contents($this->utilityLoaderPath);
        
        $this->assertStringContainsString('db-mysqli.php', $content);
        $this->assertStringContainsString('medooin.php', $content);
    }

    public function testUtilityLoaderCanBeRequiredWithoutErrors(): void
    {
        $result = @require_once $this->utilityLoaderPath;
        $this->assertNotFalse($result);
    }

    public function testLoadCoreUtilitiesDoesNotThrowErrors(): void
    {
        if (!function_exists('load_core_utilities')) {
            require_once $this->utilityLoaderPath;
        }
        
        $this->assertTrue(load_core_utilities() === null || !empty(load_core_utilities()));
    }

    public function testGenerateUtilityListPreservesComment(): void
    {
        copy($this->utilityLoaderPath, $this->backupPath);
        
        include $this->generatorPath;
        
        $newContent = file_get_contents($this->utilityLoaderPath);
        $this->assertStringContainsString('GENERATED UTILITY LOADER FILE', $newContent);
        $this->assertStringContainsString('DO NOT EDIT THIS FILE MANUALLY', $newContent);
    }
}
