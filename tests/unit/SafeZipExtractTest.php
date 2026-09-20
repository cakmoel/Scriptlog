<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for safe_zip_extract() — the canonical safe ZIP extraction used by
 * the theme and plugin uploaders (RCE F2/F8 remediation).
 *
 * Covers the happy path plus the zip-slip class of bugs: "../" traversal,
 * Windows-style "..\" traversal, absolute paths, and the escape guard. Also
 * verifies skip patterns are honoured.
 */
class SafeZipExtractTest extends TestCase
{
    /** @var string */
    private $tmpBase;

    protected function setUp(): void
    {
        $this->tmpBase = sys_get_temp_dir() . '/sze_' . uniqid();
        mkdir($this->tmpBase, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpBase)) {
            $this->removeTree($this->tmpBase);
        }
    }

    private function removeTree(string $dir): void
    {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeTree($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    private function makeZip(array $entries): string
    {
        $zipPath = $this->tmpBase . '/archive.zip';
        $zip = new ZipArchive();
        $this->assertTrue(
            $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true,
            'Test zip should open for writing'
        );

        foreach ($entries as $name => $content) {
            if (substr($name, -1) === '/') {
                $zip->addEmptyDir(rtrim($name, '/'));
            } else {
                $zip->addFromString($name, $content);
            }
        }
        $zip->close();
        return $zipPath;
    }

    public function testExtractsWellFormedArchive(): void
    {
        $dest = $this->tmpBase . '/dest';
        mkdir($dest, 0755, true);

        $zipPath = $this->makeZip([
            'style.css' => 'body {}',
            'js/main.js' => 'console.log(1);',
            'img/' => '',
        ]);

        $this->assertTrue(safe_zip_extract($zipPath, $dest));
        $this->assertFileExists($dest . '/style.css');
        $this->assertFileExists($dest . '/js/main.js');
        $this->assertSame('console.log(1);', file_get_contents($dest . '/js/main.js'));
    }

    public function testRejectsDotDotTraversal(): void
    {
        $dest = $this->tmpBase . '/dest';
        mkdir($dest, 0755, true);

        $zipPath = $this->makeZip([
            '../evil.txt' => 'pwned',
        ]);

        try {
            safe_zip_extract($zipPath, $dest);
            $this->fail('Expected InvalidArgumentException for "../" entry');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('unsafe path', $e->getMessage());
        }

        // No file may have escaped the destination.
        $this->assertFileDoesNotExist($this->tmpBase . '/evil.txt');
        $this->assertFileDoesNotExist($dest . '/evil.txt');
    }

    public function testRejectsWindowsStyleBackslashTraversal(): void
    {
        $dest = $this->tmpBase . '/dest';
        mkdir($dest, 0755, true);

        $zipPath = $this->makeZip([
            '..\\evil.txt' => 'pwned',
        ]);

        try {
            safe_zip_extract($zipPath, $dest);
            $this->fail('Expected InvalidArgumentException for "..\\" entry');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('unsafe path', $e->getMessage());
        }

        $this->assertFileDoesNotExist($this->tmpBase . '/evil.txt');
        $this->assertFileDoesNotExist($dest . '/evil.txt');
    }

    public function testRejectsAbsolutePathEntry(): void
    {
        $dest = $this->tmpBase . '/dest';
        mkdir($dest, 0755, true);

        $zipPath = $this->makeZip([
            '/etc/cron.d/evil' => 'pwned',
        ]);

        try {
            safe_zip_extract($zipPath, $dest);
            $this->fail('Expected InvalidArgumentException for absolute path entry');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('unsafe path', $e->getMessage());
        }
    }

    public function testRejectsDriveLetterEntry(): void
    {
        $dest = $this->tmpBase . '/dest';
        mkdir($dest, 0755, true);

        $zipPath = $this->makeZip([
            'C:/windows/evil.txt' => 'pwned',
        ]);

        try {
            safe_zip_extract($zipPath, $dest);
            $this->fail('Expected InvalidArgumentException for drive-letter entry');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('unsafe path', $e->getMessage());
        }
    }

    public function testSkipPatternsAreHonoured(): void
    {
        $dest = $this->tmpBase . '/dest';
        mkdir($dest, 0755, true);

        $zipPath = $this->makeZip([
            'index.php' => '<?php',
            'composer.json' => '{}',
        ]);

        $this->assertTrue(
            safe_zip_extract($zipPath, $dest, ['/^composer\.json$/i'])
        );
        $this->assertFileExists($dest . '/index.php');
        $this->assertFileDoesNotExist($dest . '/composer.json');
    }

    public function testRejectsSymlinkEntry(): void
    {
        $dest = $this->tmpBase . '/dest';
        mkdir($dest, 0755, true);

        $zipPath = $this->tmpBase . '/link.zip';
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true);
        $zip->addFromString('link', 'target.txt');
        $zip->setExternalAttributesName('link', ZipArchive::OPSYS_UNIX, 0120777 << 16);
        $zip->close();

        try {
            safe_zip_extract($zipPath, $dest);
            $this->fail('Expected InvalidArgumentException for a symlink entry');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('symbolic link', $e->getMessage());
        }

        $this->assertFileDoesNotExist($dest . '/link');
    }

    public function testThrowsWhenArchiveIsNotAZip(): void
    {
        $dest = $this->tmpBase . '/dest';
        mkdir($dest, 0755, true);

        $notZip = $this->tmpBase . '/not-a.zip';
        file_put_contents($notZip, 'this is not a zip file');

        try {
            safe_zip_extract($notZip, $dest);
            $this->fail('Expected InvalidArgumentException for non-zip archive');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Unable to open', $e->getMessage());
        }
    }
}