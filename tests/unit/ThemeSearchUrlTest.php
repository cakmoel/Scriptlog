<?php
/**
 * ThemeSearchUrlTest
 *
 * Unit tests for the theme_search_url() helper in
 * public/themes/blog/functions-post.php.
 *
 * theme_search_url() returns the search page URL honoring the active
 * permalink scheme: {base}/search when permalinks are ON, {base}/ (so the
 * form submits ?q=) when OFF. The helper is memoized with a static variable
 * and depends on app_url()/is_permalink_enabled(), which read the database —
 * so each mode is exercised in a fresh PHP subprocess with stubbed globals
 * (same isolation pattern as TastybitesFunctionsTest).
 *
 * @category   UnitTests
 * @version    1.0.0
 * @since      2026
 */

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the theme_search_url() helper in the blog theme.
 */
class ThemeSearchUrlTest extends TestCase
{
    /**
     * @var string Absolute path to the blog theme's functions-post.php
     */
    private const THEME_FUNCTIONS = __DIR__ . '/../../public/themes/blog/functions-post.php';

    /**
     * Run theme_search_url() in an isolated subprocess with stubbed globals.
     *
     * @param string $permalinkMode Value the stubbed is_permalink_enabled() returns
     * @param string $baseUrl       Value the stubbed app_url() returns
     * @return string Raw output of the subprocess (the helper's return value)
     */
    private function runInSubprocess(string $permalinkMode, string $baseUrl): string
    {
        $snippet = <<<'PHP'
<?php
define('SCRIPTLOG', 'test');
define('DS', DIRECTORY_SEPARATOR);

function app_url() { return '%s'; }
function is_permalink_enabled() { return '%s'; }

require_once %s;

echo theme_search_url();
PHP;

        $snippet = sprintf(
            $snippet,
            $baseUrl,
            $permalinkMode,
            var_export(realpath(self::THEME_FUNCTIONS), true)
        );

        $tmpFile = tempnam(sys_get_temp_dir(), 'tsu_') . '.php';
        file_put_contents($tmpFile, $snippet);

        $output = shell_exec('php ' . escapeshellarg($tmpFile) . ' 2>/dev/null');
        @unlink($tmpFile);

        $this->assertNotNull($output, 'PHP subprocess produced no output for permalink mode: ' . $permalinkMode);

        return $output;
    }

    /**
     * @test
     * With permalinks enabled, theme_search_url() must return {base}/search.
     */
    public function testPermalinksEnabledReturnsSearchPath(): void
    {
        $this->assertSame(
            'https://example.com/search',
            $this->runInSubprocess('yes', 'https://example.com'),
            'theme_search_url() must return the /search path when permalinks are enabled'
        );
    }

    /**
     * @test
     * With permalinks disabled, theme_search_url() must return the app root
     * so the form submits via query string (?q=keyword).
     */
    public function testPermalinksDisabledReturnsAppRoot(): void
    {
        $this->assertSame(
            'https://example.com/',
            $this->runInSubprocess('no', 'https://example.com'),
            'theme_search_url() must return the app root when permalinks are disabled'
        );
    }

    /**
     * @test
     * The helper must always be defined by functions-post.php so callers can
     * rely on it without a hard-coded /search fallback.
     */
    public function testFunctionIsDefinedByThemeFile(): void
    {
        $snippet = <<<'PHP'
<?php
define('SCRIPTLOG', 'test');
define('DS', DIRECTORY_SEPARATOR);

function app_url() { return 'https://example.com'; }
function is_permalink_enabled() { return 'no'; }

require_once %s;

echo function_exists('theme_search_url') ? 'defined' : 'missing';
PHP;

        $snippet = sprintf(
            $snippet,
            var_export(realpath(self::THEME_FUNCTIONS), true)
        );

        $tmpFile = tempnam(sys_get_temp_dir(), 'tsu_') . '.php';
        file_put_contents($tmpFile, $snippet);

        $output = shell_exec('php ' . escapeshellarg($tmpFile) . ' 2>/dev/null');
        @unlink($tmpFile);

        $this->assertSame('defined', $output, 'theme_search_url() must be defined by functions-post.php');
    }
}
