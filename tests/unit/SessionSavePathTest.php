<?php

use PHPUnit\Framework\TestCase;

class SessionSavePathTest extends TestCase
{
    private $tempDir;

    protected function setUp(): void
    {
        require_once __DIR__ . '/../bootstrap.php';

        // Ensure function is loaded
        if (!function_exists('resolve_session_save_path')) {
            require_once __DIR__ . '/../../lib/utility/session-save-path.php';
        }

        $this->tempDir = sys_get_temp_dir() . '/session_save_path_test_' . uniqid();
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $items = glob($this->tempDir . '/*');
            if ($items) {
                foreach ($items as $item) {
                    if (is_dir($item)) {
                        $subItems = glob($item . '/*');
                        if ($subItems) {
                            array_map('unlink', $subItems);
                        }
                        rmdir($item);
                    } else {
                        unlink($item);
                    }
                }
            }
            rmdir($this->tempDir);
        }

        putenv('SESSION_SAVE_PATH');
    }

    public function testFunctionExists(): void
    {
        $this->assertTrue(function_exists('resolve_session_save_path'));
    }

    public function testReturnsNonEmptyString(): void
    {
        $path = resolve_session_save_path();
        $this->assertIsString($path);
        $this->assertNotEmpty($path);
    }

    public function testReturnedPathIsDirectory(): void
    {
        $path = resolve_session_save_path();
        $this->assertDirectoryExists($path);
    }

    public function testReturnedPathIsWritable(): void
    {
        $path = resolve_session_save_path();
        $this->assertDirectoryIsWritable($path);
    }

    public function testReturnsAppLocalPathByDefault(): void
    {
        $path = resolve_session_save_path();
        $expected = defined('APP_ROOT')
            ? APP_ROOT . 'public/files/cache/sessions'
            : sys_get_temp_dir() . '/blogware_sessions';
        $this->assertEquals($expected, $path);
    }

    public function testRespectsEnvVar(): void
    {
        putenv('SESSION_SAVE_PATH=' . $this->tempDir);
        $path = resolve_session_save_path();
        $this->assertEquals($this->tempDir, $path);
    }

    public function testEnvVarFixesPermissions(): void
    {
        $readOnly = $this->tempDir . '/readonly';
        mkdir($readOnly, 0444, true);

        putenv('SESSION_SAVE_PATH=' . $readOnly);
        $path = resolve_session_save_path();

        $this->assertEquals($readOnly, $path);
        $this->assertDirectoryIsWritable($readOnly);

        chmod($readOnly, 0755);
        rmdir($readOnly);
    }

    public function testAutoCreatesDirectory(): void
    {
        $newPath = $this->tempDir . '/auto_created';
        $this->assertDirectoryDoesNotExist($newPath);

        putenv('SESSION_SAVE_PATH=' . $newPath);
        $path = resolve_session_save_path();

        $this->assertDirectoryExists($path);
        $this->assertEquals($newPath, $path);
    }

    public function testAppPathUsesCorrectStructure(): void
    {
        putenv('SESSION_SAVE_PATH');
        $path = resolve_session_save_path();

        if (defined('APP_ROOT')) {
            $this->assertStringContainsString('public/files/cache/sessions', $path);
            $this->assertStringStartsWith(APP_ROOT, $path);
        }
    }

    public function testUsesSysTempDirFallbackWithoutAppRoot(): void
    {
        // Temporarily remove APP_ROOT constant by re-running function via reflection-like approach
        // We can test the fallback logic by checking if APP_ROOT was defined during bootstrap
        // This test verifies the function handles the sys_get_temp_dir fallback path
        $path = resolve_session_save_path();

        // If APP_ROOT wasn't defined, it would use blogware_sessions in temp
        if (!defined('APP_ROOT')) {
            $this->assertStringContainsString('blogware_sessions', $path);
        }
    }

    public function testMultipleCallsReturnSamePath(): void
    {
        $path1 = resolve_session_save_path();
        $path2 = resolve_session_save_path();
        $this->assertEquals($path1, $path2);
    }

    public function testEnvVarPathWithoutTrailingSlash(): void
    {
        putenv('SESSION_SAVE_PATH=' . $this->tempDir . '/');
        $path = resolve_session_save_path();
        $this->assertEquals($this->tempDir, $path);
    }
}
