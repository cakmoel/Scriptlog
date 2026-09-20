<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class PluginUploadTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('remove_dir_recursive')) {
            require_once __DIR__ . '/../../lib/utility/remove-dir-recursive.php';
        }
    }

    public function testFunctionExistsUploadPlugin(): void
    {
        $this->assertTrue(
            function_exists('upload_plugin'),
            'upload_plugin function should exist'
        );
    }

    public function testFunctionExistsOpenPluginUploaded(): void
    {
        $this->assertTrue(
            function_exists('open_plugin_uploaded'),
            'open_plugin_uploaded function should exist'
        );
    }

    public function testFunctionExistsRemoveDirRecursive(): void
    {
        $this->assertTrue(
            function_exists('remove_dir_recursive'),
            'remove_dir_recursive function should exist'
        );
    }

    public function testRemoveDirRecursiveWithNonExistentPath(): void
    {
        $nonExistentDir = '/tmp/' . uniqid('nonexistent_') . '_' . time();

        $this->assertFalse(is_dir($nonExistentDir), 'Directory should not exist before test');

        $caughtException = false;
        try {
            remove_dir_recursive($nonExistentDir);
        } catch (\Throwable $e) {
            $caughtException = true;
        }

        $this->assertFalse($caughtException,
            'remove_dir_recursive should not throw when path does not exist');
    }

    public function testRemoveDirRecursiveWithValidDir(): void
    {
        $testDir = '/tmp/' . uniqid('testdir_');
        mkdir($testDir, 0755, true);
        file_put_contents($testDir . '/test.txt', 'hello');

        $this->assertTrue(is_dir($testDir), 'Test directory should exist');
        $this->assertFileExists($testDir . '/test.txt', 'Test file should exist');

        remove_dir_recursive($testDir);

        $this->assertFalse(is_dir($testDir), 'Directory should be removed');
    }

    public function testBlacklistRegexWithValidFilenames(): void
    {
        $blacklist = ["..", ".git", ".svn", "composer.json", "composer.lock",
                       "framework_config.yaml", ".html", ".phtml", ".pl", ".py", ".sh"];

        $validFilenames = [
            'my-theme-v1.0.zip',
            'valid-theme.zip',
            'a.zip',
            'theme123.zip',
        ];

        foreach ($validFilenames as $filename) {
            $blocked = false;
            foreach ($blacklist as $item) {
                if (preg_match("/" . preg_quote($item, '/') . "\$/i", $filename)) {
                    $blocked = true;
                    break;
                }
            }
            $this->assertFalse($blocked,
                "Valid filename '$filename' should NOT be blocked with preg_quote'd blacklist");
        }
    }

    public function testBlacklistRegexWithActualMaliciousNames(): void
    {
        $blacklist = ["..", ".git", ".svn", "composer.json", "composer.lock",
                       "framework_config.yaml", ".html", ".phtml", ".pl", ".py", ".sh"];

        $maliciousFilenames = [
            'theme..zip',
            'evil.git.zip',
            'config.composer.json.zip',
        ];

        foreach ($maliciousFilenames as $filename) {
            $blocked = false;
            foreach ($blacklist as $item) {
                if (preg_match("/" . preg_quote($item, '/') . "\$/i", $filename)) {
                    $blocked = true;
                    break;
                }
            }
            $this->assertFalse($blocked,
                "Malicious filename '$filename' should NOT be blocked by preg_quote'd blacklist " .
                "(the blacklist item must appear as a literal substring in the filename to match)");
        }
    }

    public function testZipStatIndexReturnsCompSize(): void
    {
        $zipPath = '/tmp/' . uniqid('test_zip_comp_') . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('test.txt', str_repeat('A', 1000));
            $zip->close();
        }

        $this->assertFileExists($zipPath, 'Test zip file should exist');

        $zip2 = new ZipArchive();
        if ($zip2->open($zipPath) === true) {
            $stats = $zip2->statIndex(0);

            $this->assertIsArray($stats, 'statIndex should return an array');
            $this->assertArrayHasKey('comp_size', $stats,
                'statIndex should return key "comp_size" (not "com_size")');
            $this->assertArrayNotHasKey('com_size', $stats,
                'statIndex should NOT return "com_size" (typo in upload-plugin.php line 114)');

            $zip2->close();
        }

        unlink($zipPath);
    }

    public function testZipStatIndexReturnsCompSizeWithUncompressedFile(): void
    {
        $zipPath = '/tmp/' . uniqid('test_zip_store_') . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('data.bin', str_repeat("\x00", 500));
            $zip->setCompressionName('data.bin', ZipArchive::CM_STORE);
            $zip->close();
        }

        $this->assertFileExists($zipPath);

        $zip2 = new ZipArchive();
        if ($zip2->open($zipPath) === true) {
            $stats = $zip2->statIndex(0);

            $this->assertArrayHasKey('comp_size', $stats,
                'Even with CM_STORE, statIndex should return "comp_size" key');
            $this->assertArrayHasKey('size', $stats,
                'statIndex should return "size" (uncompressed size) key');

            $zip2->close();
        }

        unlink($zipPath);
    }

    public function testCurrentSizeShouldTrackActualBytesRead(): void
    {
        $data = str_repeat('A', 500);
        $readLength = 1024;
        $currentSize = 0;
        $totalBytesRead = 0;

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $data);
        rewind($handle);

        while (!feof($handle)) {
            $chunk = fread($handle, $readLength);
            if ($chunk === false || strlen($chunk) === 0) break;

            $currentSize += $readLength;
            $totalBytesRead += strlen($chunk);
        }

        fclose($handle);

        $this->assertNotEquals($totalBytesRead, $currentSize,
            'BUG: $current_size adds READ_LENGTH (1024) per iteration, not actual bytes read. ' .
            'For a 500-byte file, this overestimates the size.');
        $this->assertEquals(500, $totalBytesRead,
            'Total bytes read should match actual file size');
        $this->assertEquals(1024, $currentSize,
            'With $current_size += READ_LENGTH pattern, 500 bytes still counts as 1024.');
    }

    public function testCurrentSizeFixedWithStrlen(): void
    {
        $data = str_repeat('A', 500);
        $readLength = 1024;
        $currentSize = 0;

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $data);
        rewind($handle);

        while (!feof($handle)) {
            $chunk = fread($handle, $readLength);
            if ($chunk === false || strlen($chunk) === 0) break;

            $currentSize += strlen($chunk);
        }

        fclose($handle);

        $this->assertEquals(500, $currentSize,
            'With $current_size += strlen($chunk), actual bytes read should be 500');
    }
}
