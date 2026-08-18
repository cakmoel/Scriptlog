<?php
/**
 * SyncIntegrityHashesTest
 *
 * Unit tests for sync_integrity_hashes() in src/tmp/minify.php.
 */

use PHPUnit\Framework\TestCase;

class SyncIntegrityHashesTest extends TestCase
{
    private $themeDir;
    private $repoRoot;
    private $tempDir;

    protected function setUp(): void
    {
        if (!function_exists('sync_integrity_hashes')) {
            require_once __DIR__ . '/../../src/tmp/minify.php';
        }

        $this->tempDir = sys_get_temp_dir() . '/integrity_test_' . uniqid();
        $this->themeDir = $this->tempDir . '/theme';
        $this->repoRoot = $this->tempDir;

        mkdir($this->themeDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Recursively remove temp dir
        if (is_dir($this->tempDir)) {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($it as $file) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
            rmdir($this->tempDir);
        }
    }

    public function testSyncIntegrityHashesFunctionExists(): void
    {
        $this->assertTrue(function_exists('sync_integrity_hashes'));
    }

    public function testReturnsZeroForEmptyDirectory(): void
    {
        $result = sync_integrity_hashes($this->themeDir, $this->repoRoot);
        $this->assertSame(0, $result);
    }

    public function testReturnsZeroWhenNoIntegrityAttributes(): void
    {
        file_put_contents($this->themeDir . '/header.php', '<html><body>No integrity here</body></html>');
        $result = sync_integrity_hashes($this->themeDir, $this->repoRoot);
        $this->assertSame(0, $result);
    }

    public function testUpdatesStaleIntegrityHash(): void
    {
        // Create a CSS file to hash
        $cssContent = 'body { color: red; }';
        $cssFile = $this->themeDir . '/style.min.css';
        file_put_contents($cssFile, $cssContent);

        $correctHash = base64_encode(hash('sha384', $cssContent, true));
        $wrongHash = base64_encode(hash('sha384', 'wrong content', true));

        // Create template with a wrong integrity hash
        $template = '<link href="<?= theme_dir(); ?>style.min.css" integrity="sha384-' . $wrongHash . '" crossorigin="anonymous">';
        file_put_contents($this->themeDir . '/header.php', $template);

        $updated = sync_integrity_hashes($this->themeDir, $this->repoRoot);

        $this->assertSame(1, $updated);

        $result = file_get_contents($this->themeDir . '/header.php');
        $this->assertStringContainsString('sha384-' . $correctHash, $result);
        $this->assertStringNotContainsString('sha384-' . $wrongHash, $result);
    }

    public function testSkipsMatchingHash(): void
    {
        $cssContent = 'body { color: blue; }';
        $cssFile = $this->themeDir . '/style.min.css';
        file_put_contents($cssFile, $cssContent);

        $correctHash = base64_encode(hash('sha384', $cssContent, true));

        $template = '<link href="<?= theme_dir(); ?>style.min.css" integrity="sha384-' . $correctHash . '" crossorigin="anonymous">';
        file_put_contents($this->themeDir . '/header.php', $template);

        $updated = sync_integrity_hashes($this->themeDir, $this->repoRoot);
        $this->assertSame(0, $updated);
    }

    public function testHandlesAbsoluteUrlPaths(): void
    {
        // Create a file in the repo root
        $cssContent = '.container { width: 100%; }';
        $cssDir = $this->repoRoot . '/public/themes/blog/assets/css';
        mkdir($cssDir, 0755, true);
        file_put_contents($cssDir . '/custom.min.css', $cssContent);

        $correctHash = base64_encode(hash('sha384', $cssContent, true));
        $wrongHash = 'dGVzdA==';

        // Template with absolute URL path
        $template = '<link href="/public/themes/blog/assets/css/custom.min.css" integrity="sha384-' . $wrongHash . '" crossorigin="anonymous">';
        file_put_contents($this->themeDir . '/footer.php', $template);

        $updated = sync_integrity_hashes($this->themeDir, $this->repoRoot);
        $this->assertSame(1, $updated);

        $result = file_get_contents($this->themeDir . '/footer.php');
        $this->assertStringContainsString('sha384-' . $correctHash, $result);
    }

    public function testSkipsNonExistentFiles(): void
    {
        $wrongHash = 'dGVzdA==';
        $template = '<script src="<?= theme_dir(); ?>nonexistent.js" integrity="sha384-' . $wrongHash . '"></script>';
        file_put_contents($this->themeDir . '/header.php', $template);

        $updated = sync_integrity_hashes($this->themeDir, $this->repoRoot);
        $this->assertSame(0, $updated);
    }

    public function testUpdatesMultipleHashesInOneFile(): void
    {
        $css1 = '.a { color: red; }';
        $css2 = '.b { color: blue; }';
        file_put_contents($this->themeDir . '/style.min.css', $css1);
        file_put_contents($this->themeDir . '/custom.min.css', $css2);

        $hash1 = base64_encode(hash('sha384', $css1, true));
        $hash2 = base64_encode(hash('sha384', $css2, true));

        $template = '<link href="<?= theme_dir(); ?>style.min.css" integrity="sha384-stale1">' . "\n"
            . '<link href="<?= theme_dir(); ?>custom.min.css" integrity="sha384-stale2">';
        file_put_contents($this->themeDir . '/header.php', $template);

        $updated = sync_integrity_hashes($this->themeDir, $this->repoRoot);
        $this->assertSame(2, $updated);

        $result = file_get_contents($this->themeDir . '/header.php');
        $this->assertStringContainsString('sha384-' . $hash1, $result);
        $this->assertStringContainsString('sha384-' . $hash2, $result);
        $this->assertStringNotContainsString('stale1', $result);
        $this->assertStringNotContainsString('stale2', $result);
    }

    public function testSkipsQueryStringInUrl(): void
    {
        $cssContent = '.c { color: green; }';
        file_put_contents($this->themeDir . '/style.min.css', $cssContent);

        $correctHash = base64_encode(hash('sha384', $cssContent, true));
        $wrongHash = 'dGVzdA==';

        $template = '<link href="<?= theme_dir(); ?>style.min.css?v=123" integrity="sha384-' . $wrongHash . '">';
        file_put_contents($this->themeDir . '/header.php', $template);

        $updated = sync_integrity_hashes($this->themeDir, $this->repoRoot);
        $this->assertSame(1, $updated);

        $result = file_get_contents($this->themeDir . '/header.php');
        $this->assertStringContainsString('sha384-' . $correctHash, $result);
    }

    public function testIntReturnType(): void
    {
        $result = sync_integrity_hashes($this->themeDir, $this->repoRoot);
        $this->assertIsInt($result);
    }

    public function testOnlyProcessesPhpFiles(): void
    {
        file_put_contents($this->themeDir . '/style.css', '<html>not a php file</html>');
        $result = sync_integrity_hashes($this->themeDir, $this->repoRoot);
        $this->assertSame(0, $result);
    }
}
