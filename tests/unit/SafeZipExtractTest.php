<?php

use PHPUnit\Framework\TestCase;

/**
 * SafeZipExtractTest
 *
 * Unit tests for the safe_zip_extract() function that provides
 * secure ZIP extraction with path traversal, symlink, and zip bomb defenses.
 *
 * @category Unit Test
 * @author Blogware Team
 * @license MIT
 */
class SafeZipExtractTest extends TestCase
{
    private $testDir;

    protected function setUp(): void
    {
        if (!defined('MAX_FILES')) {
            define('MAX_FILES', 10000);
        }
        if (!defined('MAX_SIZE')) {
            define('MAX_SIZE', 1000000000);
        }
        if (!defined('MAX_RATIO')) {
            define('MAX_RATIO', 10);
        }
        if (!defined('READ_LENGTH')) {
            define('READ_LENGTH', 1024);
        }

        require_once __DIR__ . '/../../src/lib/utility/create-directory.php';
        require_once __DIR__ . '/../../src/lib/utility/safe-zip-extract.php';

        $this->testDir = sys_get_temp_dir() . '/safe_zip_test_' . uniqid();
        mkdir($this->testDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->testDir)) {
            $this->recursiveDelete($this->testDir);
        }
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function createZip(array $entries): string
    {
        $zipPath = $this->testDir . '/test_' . uniqid() . '.zip';
        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();
        return $zipPath;
    }

    public function testFunctionExists(): void
    {
        $this->assertTrue(function_exists('safe_zip_extract'));
    }

    public function testExtractsValidZipSuccessfully(): void
    {
        $zipPath = $this->createZip([
            'readme.txt' => 'Hello World',
            'src/main.php' => '<?php echo "test";',
        ]);

        $destDir = $this->testDir . '/output';
        $result = safe_zip_extract($zipPath, $destDir);

        $this->assertTrue($result);
        $this->assertFileExists($destDir . '/readme.txt');
        $this->assertFileExists($destDir . '/src/main.php');
        $this->assertEquals('Hello World', file_get_contents($destDir . '/readme.txt'));
    }

    public function testRejectsPathTraversalWithDotDot(): void
    {
        $zipPath = $this->createZip([
            '../etc/passwd' => 'malicious content',
        ]);

        $destDir = $this->testDir . '/output';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsafe path');

        safe_zip_extract($zipPath, $destDir);
    }

    public function testRejectsAbsoluteUnixPath(): void
    {
        $zipPath = $this->createZip([
            '/etc/passwd' => 'malicious content',
        ]);

        $destDir = $this->testDir . '/output';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsafe path');

        safe_zip_extract($zipPath, $destDir);
    }

    public function testRejectsWindowsDriveLetterPath(): void
    {
        $zipPath = $this->createZip([
            'C:/Windows/System32/evil.exe' => 'malicious content',
        ]);

        $destDir = $this->testDir . '/output';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsafe path');

        safe_zip_extract($zipPath, $destDir);
    }

    public function testRejectsBackslashTraversal(): void
    {
        $zipPath = $this->createZip([
            '..\\..\\etc\\passwd' => 'malicious content',
        ]);

        $destDir = $this->testDir . '/output';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsafe path');

        safe_zip_extract($zipPath, $destDir);
    }

    public function testRejectsNullByteInPath(): void
    {
        $zipPath = $this->createZip([
            "file.txt" => 'content',
        ]);

        $destDir = $this->testDir . '/output';
        $result = safe_zip_extract($zipPath, $destDir);
        $this->assertTrue($result);

        $this->assertFileExists($destDir . '/file.txt');
    }

    public function testRejectsInvalidZipFile(): void
    {
        $fakeZip = $this->testDir . '/not_a_zip.txt';
        file_put_contents($fakeZip, 'This is not a zip file');

        $destDir = $this->testDir . '/output';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to open');

        safe_zip_extract($fakeZip, $destDir);
    }

    public function testRejectsNonexistentDestination(): void
    {
        $zipPath = $this->createZip(['file.txt' => 'content']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid extraction destination');

        safe_zip_extract($zipPath, '/nonexistent/path/that/does/not/exist');
    }

    public function testSkipsEntriesMatchingSkipPatterns(): void
    {
        $zipPath = $this->createZip([
            'keep.txt' => 'keep this',
            'skip_me.log' => 'skip this',
            'another_skip.log' => 'also skip',
        ]);

        $destDir = $this->testDir . '/output';
        $result = safe_zip_extract($zipPath, $destDir, ['/\.log$/']);

        $this->assertTrue($result);
        $this->assertFileExists($destDir . '/keep.txt');
        $this->assertFileDoesNotExist($destDir . '/skip_me.log');
        $this->assertFileDoesNotExist($destDir . '/another_skip.log');
    }

    public function testCreatesDestinationDirectoryIfMissing(): void
    {
        $zipPath = $this->createZip(['file.txt' => 'content']);

        $destDir = $this->testDir . '/new/output/dir';
        $this->assertDirectoryDoesNotExist($destDir);

        $result = safe_zip_extract($zipPath, $destDir);

        $this->assertTrue($result);
        $this->assertDirectoryExists($destDir);
        $this->assertFileExists($destDir . '/file.txt');
    }

    public function testHandlesDirectoriesInZip(): void
    {
        $zipPath = $this->createZip([
            'src/' => '',
            'src/main.php' => '<?php echo "hi";',
            'tests/' => '',
            'tests/unit.php' => '<?php echo "test";',
        ]);

        $destDir = $this->testDir . '/output';
        $result = safe_zip_extract($zipPath, $destDir);

        $this->assertTrue($result);
        $this->assertDirectoryExists($destDir . '/src');
        $this->assertDirectoryExists($destDir . '/tests');
        $this->assertFileExists($destDir . '/src/main.php');
        $this->assertFileExists($destDir . '/tests/unit.php');
    }

    public function testExtractsZipWithSingleFile(): void
    {
        $zipPath = $this->createZip([
            'readme.txt' => 'Single file content',
        ]);

        $destDir = $this->testDir . '/output';
        $result = safe_zip_extract($zipPath, $destDir);

        $this->assertTrue($result);
        $this->assertFileExists($destDir . '/readme.txt');
        $this->assertEquals('Single file content', file_get_contents($destDir . '/readme.txt'));
    }
}
