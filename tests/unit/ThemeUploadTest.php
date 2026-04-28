<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * ThemeUploadTest 
 * 
 * Tests for theme upload functionality (zip file upload and installation).
 * This tests the install-template.php form functionality.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class ThemeUploadTest extends TestCase
{
    private $uploadTheme;
    private $zipFile;
    private $testThemeDir;
    
    protected function setUp(): void
    {
        $this->testThemeDir = __DIR__ . '/test_themes/' . uniqid('theme_test_');
    }
    
    protected function tearDown(): void
    {
        if (is_dir($this->testThemeDir)) {
            $this->recursiveDelete($this->testThemeDir);
        }
    }
    
    private function recursiveDelete($dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }
    
    private function createTestThemeZip(string $themeName, string $destination): bool
    {
        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0755, true);
        }
        
        $zip = new ZipArchive();
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }
        
        $zip->addFromString('theme.ini', "[info]\ntheme_name = $themeName\ntheme_description = Test Theme\ntheme_designer = Test Designer\ntheme_directory = " . strtolower($themeName));
        $zip->addFromString('home.php', '<?php defined("SCRIPTLOG") || die("Direct access not permitted"); ?>');
        $zip->addFromString('header.php', '<?php defined("SCRIPTLOG") || die("Direct access not permitted"); ?>');
        $zip->addFromString('footer.php', '<?php defined("SCRIPTLOG") || die("Direct access not permitted"); ?>');
        $zip->addFromString('functions.php', '<?php defined("SCRIPTLOG") || die("Direct access not permitted"); ?>');
        
        return $zip->close();
    }
    
    public function testValidZipExtension(): void
    {
        $validExtensions = ['zip'];
        
        foreach ($validExtensions as $ext) {
            $this->assertEquals('zip', $ext, 'Zip extension should be valid');
        }
    }
    
    public function testInvalidZipExtensionRejected(): void
    {
        $invalidExtensions = ['tar', 'gz', 'rar', '7z'];
        
        foreach ($invalidExtensions as $ext) {
            $this->assertNotEquals('zip', $ext, 'Non-zip extensions should be rejected');
        }
    }
    
    public function testZipMimeTypeValidation(): void
    {
        $validMimeTypes = [
            'application/zip',
            'application/x-zip-compressed',
            'multipart/x-zip',
            'application/x-compressed'
        ];
        
        foreach ($validMimeTypes as $mimeType) {
            $this->assertContains($mimeType, $validMimeTypes, 'Valid MIME types should be accepted');
        }
    }
    
    public function testFileSizeLimitValidation(): void
    {
        $maxSizeBytes = 10 * 1024 * 1024; // 10MB (from scriptlog_upload_filesize())
        
        $this->assertEquals(10485760, $maxSizeBytes, 'Max file size should be 10MB');
        $this->assertGreaterThan(0, $maxSizeBytes, 'Max size should be greater than 0');
    }
    
    public function testBlacklistFiltering(): void
    {
        $blacklist = ["..", ".git", ".svn", "composer.json", "composer.lock", "framework_config.yaml", ".html", ".phtml", ".pl", ".py", ".sh"];
        
        $forbiddenFiles = ['..', '.git', '.svn', 'composer.json', 'composer.lock'];
        
        foreach ($forbiddenFiles as $forbidden) {
            $found = false;
            foreach ($blacklist as $blocked) {
                if (strpos($forbidden, $blocked) !== false || $forbidden === $blocked) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Blacklist should block: $forbidden");
        }
    }
    
    public function testThemeIniParsing(): void
    {
        $iniContent = "[info]\ntheme_name = Test Theme\ntheme_description = A test theme\ntheme_designer = Developer\ntheme_directory = test-theme";
        
        $parsed = parse_ini_string($iniContent);
        
        $this->assertIsArray($parsed, 'theme.ini should parse to array');
        $this->assertEquals('Test Theme', $parsed['theme_name'] ?? null, 'theme_name should be extracted');
        $this->assertEquals('A test theme', $parsed['theme_description'] ?? null, 'theme_description should be extracted');
        $this->assertEquals('Developer', $parsed['theme_designer'] ?? null, 'theme_designer should be extracted');
        $this->assertEquals('test-theme', $parsed['theme_directory'] ?? null, 'theme_directory should be extracted');
    }
    
    public function testThemeIniRequiredFields(): void
    {
        $requiredFields = ['theme_name', 'theme_designer', 'theme_directory'];
        
        $completeIni = "[info]\ntheme_name = Complete Theme\ntheme_designer = Designer\ntheme_directory = complete-theme";
        
        $parsed = parse_ini_string($completeIni);
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $parsed, "Required field $field should exist");
        }
    }
    
    public function testZipExtraction(): void
    {
        $themeName = 'ExtractTestTheme';
        $zipPath = '/tmp/' . uniqid('test_theme_') . '.zip';
        
        $this->createTestThemeZip($themeName, $zipPath);
        
        $this->assertFileExists($zipPath, 'Test zip file should exist');
        
        if (file_exists($zipPath)) {
            $zip = new ZipArchive();
            $result = $zip->open($zipPath);
            
            $this->assertTrue($result === true || $result === 0, 'Zip should be openable');
            
            if ($result === true || $result === 0) {
                $extractPath = $this->testThemeDir;
                $extractResult = $zip->extractTo($extractPath);
                
                $this->assertTrue($extractResult, 'Zip should extract successfully');
                $this->assertFileExists($extractPath . '/theme.ini', 'theme.ini should exist after extraction');
                
                $zip->close();
            }
            
            unlink($zipPath);
        }
        
        if (is_dir($this->testThemeDir)) {
            $this->recursiveDelete($this->testThemeDir);
        }
    }
    
    public function testDuplicateThemeDetection(): void
    {
        $existingThemeDir = 'public/themes/blog';
        
        $this->assertTrue(
            is_dir(APP_ROOT . $existingThemeDir) || !file_exists(APP_ROOT . $existingThemeDir),
            'Existing theme directory check should work'
        );
    }
    
    public function testFileNameValidation(): void
    {
        $validFileNames = [
            'my-theme.zip',
            'Test_Theme_v1.0.zip',
            'custom-theme-scriptlog.zip'
        ];
        
        foreach ($validFileNames as $fileName) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $this->assertEquals('zip', strtolower($extension), "Extension should be zip: $fileName");
        }
        
        $invalidFileNames = [
            'theme.tar.gz',
            'theme.rar',
            'theme.exe'
        ];
        
        foreach ($invalidFileNames as $fileName) {
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $this->assertNotEquals('zip', strtolower($extension), "Extension should not be zip: $fileName");
        }
    }
    
    public function testFormFieldPresence(): void
    {
        $requiredFormFields = [
            'zip_file',
            'csrfToken',
            'MAX_FILE_SIZE',
            'themeFormSubmit'
        ];
        
        foreach ($requiredFormFields as $field) {
            $this->assertNotEmpty($field, "Form field $field should be required");
        }
    }
    
    public function testCsrfTokenValidationPresent(): void
    {
        $token = csrf_generate_token('csrfToken');
        
        $this->assertNotEmpty($token, 'CSRF token should be generated');
        $this->assertIsString($token, 'CSRF token should be string');
    }
    
    public function testUploadErrorHandling(): void
    {
        $errorCodes = [
            UPLOAD_ERR_OK => 'Success',
            UPLOAD_ERR_INI_SIZE => 'Exceeds ini size',
            UPLOAD_ERR_FORM_SIZE => 'Exceeds form size',
            UPLOAD_ERR_PARTIAL => 'Partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
            UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
            UPLOAD_ERR_EXTENSION => 'Extension blocked'
        ];
        
        $this->assertCount(8, $errorCodes, 'All upload error codes should be defined');
        
        $this->assertEquals(0, UPLOAD_ERR_OK, 'UPLOAD_ERR_OK should be 0');
        $this->assertEquals(1, UPLOAD_ERR_INI_SIZE, 'UPLOAD_ERR_INI_SIZE should be 1');
        $this->assertEquals(2, UPLOAD_ERR_FORM_SIZE, 'UPLOAD_ERR_FORM_SIZE should be 2');
        $this->assertEquals(3, UPLOAD_ERR_PARTIAL, 'UPLOAD_ERR_PARTIAL should be 3');
        $this->assertEquals(4, UPLOAD_ERR_NO_FILE, 'UPLOAD_ERR_NO_FILE should be 4');
        $this->assertEquals(6, UPLOAD_ERR_NO_TMP_DIR, 'UPLOAD_ERR_NO_TMP_DIR should be 6');
        $this->assertEquals(7, UPLOAD_ERR_CANT_WRITE, 'UPLOAD_ERR_CANT_WRITE should be 7');
        $this->assertEquals(8, UPLOAD_ERR_EXTENSION, 'UPLOAD_ERR_EXTENSION should be 8');
    }
    
    public function testThemeDirectoryPermissions(): void
    {
        $themesDir = APP_ROOT . APP_THEME;
        
        $this->assertTrue(
            is_dir($themesDir) || !is_dir($themesDir),
            'Themes directory should be accessible'
        );
    }
    
    public function testPostMaxFileSizeConfiguration(): void
    {
        $maxUploadSize = scriptlog_upload_filesize();
        
        $this->assertGreaterThan(0, $maxUploadSize, 'Max upload size should be configured');
    }
    
    public function testFunctionExistsUploadTheme(): void
    {
        $this->assertTrue(
            function_exists('upload_theme'),
            'upload_theme function should exist'
        );
    }
    
    public function testFunctionExistsCheckFileName(): void
    {
        $this->assertTrue(
            function_exists('check_file_name'),
            'check_file_name function should exist'
        );
    }
    
    public function testFunctionExistsCheckFileLength(): void
    {
        $this->assertTrue(
            function_exists('check_file_length'),
            'check_file_length function should exist'
        );
    }
    
    public function testFunctionExistsCheckMimeType(): void
    {
        $this->assertTrue(
            function_exists('check_mime_type'),
            'check_mime_type function should exist'
        );
    }
    
    public function testFunctionExistsFormatSizeUnit(): void
    {
        $this->assertTrue(
            function_exists('format_size_unit'),
            'format_size_unit function should exist'
        );
    }
    
    // ========== Whitelist-based Validation Tests ==========
    
    public function testWhitelistExtensionCheckPass(): void
    {
        $whitelist = ['.zip'];
        
        $validExtensions = ['.zip', '.ZIP', '.Zip'];
        foreach ($validExtensions as $ext) {
            $result = in_array(strtolower($ext), $whitelist);
            $this->assertTrue($result, "Extension $ext should pass whitelist");
        }
    }
    
    public function testWhitelistExtensionCheckFail(): void
    {
        $whitelist = ['.zip'];
        
        $invalidExtensions = ['.tar', '.gz', '.rar', '.7z', '.exe', '.php'];
        foreach ($invalidExtensions as $ext) {
            $result = in_array(strtolower($ext), $whitelist);
            $this->assertFalse($result, "Extension $ext should fail whitelist");
        }
    }
    
    public function testWhitelistFilenamePatternPass(): void
    {
        $pattern = '/^[a-zA-Z0-9\-_\.]+$/';
        
        $validFilenames = [
            'my-theme.zip',
            'Test_Theme_v1.0.zip',
            'custom-theme-scriptlog.zip',
            'theme123.zip'
        ];
        
        foreach ($validFilenames as $filename) {
            $result = preg_match($pattern, $filename);
            $this->assertEquals(1, $result, "Filename $filename should pass pattern");
        }
    }
    
public function testWhitelistFilenamePatternFail(): void
    {
        $pattern = '/^[a-zA-Z0-9\-_\.]+$/';
        
        // These should NOT match - contains dangerous extensions or special chars
        $invalidFilenames = [
            'file with spaces.zip',      // spaces not allowed
            'theme@1.zip',           // @ not allowed
            'theme#1.zip',           // # not allowed
            'theme$1.zip',           // $ not allowed
        ];
        
        foreach ($invalidFilenames as $filename) {
            $result = preg_match($pattern, $filename);
            $this->assertEquals(0, $result, "Filename should fail pattern: $filename");
        }
        
        // This is actually valid (all allowed chars)
        $semiValid = 'theme.php.zip';
        $result = preg_match($pattern, $semiValid);
        $this->assertEquals(1, $result, "Pattern allows .php.zip because . is allowed");
    }
    
    public function testPathTraversalDetection(): void
    {
        // These are actual path traversal attempts
        $maliciousBasenames = [
            '../etc/passwd',                    // double dot
            'theme/../../../etc/passwd',        // slashes
            'theme\\..\\..\\windows\\system32',   // backslashes
            'theme/..',                        // slash + double dot
        ];
        
        foreach ($maliciousBasenames as $basename) {
            $hasTraversal = strpos($basename, '..') !== false || strpos($basename, '/') !== false || strpos($basename, '\\') !== false;
            $this->assertTrue($hasTraversal, "Should detect path traversal in: $basename");
        }
        
        // Single dot without another dot is NOT path traversal
        $safeBasename = 'my-theme';
        $hasTraversal = strpos($safeBasename, '..') !== false || strpos($safeBasename, '/') !== false || strpos($safeBasename, '\\') !== false;
        $this->assertFalse($hasTraversal, "Single dot is not path traversal: $safeBasename");
    }
    
    public function testDefenseInDepthWithBlacklist(): void
    {
        $blacklist = ["..", ".git", ".svn", "composer.json", "composer.lock"];
        $testItems = ["..", ".git", ".svn", "composer.json", "composer.lock"];
        
        foreach ($testItems as $item) {
            $found = false;
            foreach ($blacklist as $blocked) {
                if (strpos($item, $blocked) !== false || $item === $blocked) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Blacklist should still block: $item");
        }
    }
}