<?php
/**
 * MinifyUtilityTest
 *
 * Unit tests for the theme asset minifier utility (tmp/minify.php),
 * covering the cache_busted_url() helper, the minify_css()/minify_js()
 * cleaners, and the CLI theme-scoping contract:
 *
 *   php tmp/minify.php            # every theme under public/themes/
 *   php tmp/minify.php blog       # only public/themes/blog/
 *
 * These functions were added with the minifier's theme-scoping and
 * integrity-hash refresh work; without tests a registry rename here would
 * silently regress the asset rebuild pipeline used before deployments.
 */

use PHPUnit\Framework\TestCase;

class MinifyUtilityTest extends TestCase
{
    /**
     * Load only the function definitions from tmp/minify.php.
     *
     * The script body (everything from "$themes_dir =" onwards) performs a
     * real minification pass and rewrites templates on disk, so it must never
     * run inside a test process. The function-definition header is evaluated
     * exactly once per suite run.
     */
    public static function setUpBeforeClass(): void
    {
        if (function_exists('cache_busted_url')) {
            return;
        }

        $source = file_get_contents(dirname(__DIR__, 2) . '/tmp/minify.php');
        $marker = '$themes_dir = __DIR__';
        $pos = strpos($source, $marker);

        self::assertNotFalse(
            $pos,
            'tmp/minify.php is expected to contain the "' . $marker . '" script body marker.'
        );
        $header = substr($source, 0, $pos);

        // eval() does not accept a leading PHP opening tag.
        $header = preg_replace('/^\s*<\?php\s*/', '', $header);

        eval($header);
    }

    /**
     * The cache-busting token is derived from the asset digest so the URL
     * changes exactly when the served file content changes.
     */
    public function testCacheBustedUrlTokenDerivesFromDigest(): void
    {
        $digest = base64_encode(hash('sha384', 'asset-bytes', true));
        $expected = substr(hash('sha256', $digest), 0, 12);

        $this->assertSame(
            'assets/js/front.min.js?v=' . $expected,
            cache_busted_url('assets/js/front.min.js', $digest)
        );
    }

    public function testCacheBustedUrlTokenChangesWhenDigestChanges(): void
    {
        $digestOne = base64_encode(hash('sha384', 'old-content', true));
        $digestTwo = base64_encode(hash('sha384', 'new-content', true));

        $this->assertNotSame($digestOne, $digestTwo);
        $this->assertNotSame(
            cache_busted_url('assets/js/front.min.js', $digestOne),
            cache_busted_url('assets/js/front.min.js', $digestTwo)
        );
    }

    public function testCacheBustedUrlKeepsPhpExpressionPrefixIntact(): void
    {
        $digest = base64_encode(hash('sha384', 'theme-asset', true));
        $expected = substr(hash('sha256', $digest), 0, 12);

        $this->assertSame(
            '<?= theme_dir(); ?>assets/js/front.min.js?v=' . $expected,
            cache_busted_url('<?= theme_dir(); ?>assets/js/front.min.js', $digest)
        );
    }

    public function testCacheBustedUrlPreservesExistingQueryParameters(): void
    {
        $digest = base64_encode(hash('sha384', 'tracked-asset', true));
        $expected = substr(hash('sha256', $digest), 0, 12);

        $this->assertSame(
            'assets/js/front.min.js?muted=1&v=' . $expected,
            cache_busted_url('assets/js/front.min.js?muted=1', $digest)
        );
    }

    public function testCacheBustedUrlReplacesStaleToken(): void
    {
        $digest = base64_encode(hash('sha384', 'refreshed-asset', true));
        $expected = substr(hash('sha256', $digest), 0, 12);

        $this->assertSame(
            'assets/js/front.min.js?v=' . $expected,
            cache_busted_url('assets/js/front.min.js?v=a488ee89b0fd', $digest)
        );
    }

    public function testMinifyCssRemovesCommentsAndWhitespace(): void
    {
        $css = "/* banner comment */\nbody {\n    color: #333;\n    margin: 0;\n}\n";

        $this->assertSame(
            'body{color:#333;margin:0}',
            minify_css($css)
        );
    }

    public function testMinifyCssReturnsEmptyInputUnchanged(): void
    {
        $this->assertSame('', minify_css(''));
    }

    public function testMinifyJsRemovesBlockCommentsAndReducesWhitespace(): void
    {
        $js = "/* note */\nvar a = 1;\nvar b = 2;\n";

        $minified = minify_js($js);

        $this->assertStringNotContainsString('note', $minified);
        $this->assertStringContainsString('var a = 1;', $minified);
        $this->assertStringContainsString('var b = 2;', $minified);
        $this->assertSame('', minify_js(''));
    }

    /**
     * The optional CLI scope must reject an unknown theme with exit code 1
     * before any minification pass runs (so a typo cannot silently rebuild
     * every theme).
     */
    public function testUnknownThemeScopeExitsWithError(): void
    {
        $script = dirname(__DIR__, 2) . '/tmp/minify.php';
        $cmd = PHP_BINARY . ' ' . escapeshellarg($script) . ' __no_such_theme__ 2>&1';

        exec($cmd, $output, $exitCode);

        $this->assertSame(1, $exitCode, 'Unknown theme scope should exit with status 1. Output: ' . implode("\n", $output));
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Unknown theme: __no_such_theme__', $output[0]);
        $this->assertStringContainsString('Available themes:', $output[1]);
    }
}
