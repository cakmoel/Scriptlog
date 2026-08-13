<?php
/**
 * ThemeCallerCacheTest
 *
 * Unit tests for the request-scoped memoization in theme-caller.php:
 *   - theme_dir_cache() / reset_theme_dir_cache()
 *   - theme_identifier_cache() / reset_theme_identifier_cache()
 *   - Memoization behavior of theme_dir() and theme_identifier()
 */

use PHPUnit\Framework\TestCase;

class ThemeCallerCacheTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('theme_dir_cache')) {
            require_once __DIR__ . '/../../src/lib/utility/theme-caller.php';
        }
        reset_theme_dir_cache();
        reset_theme_identifier_cache();
    }

    protected function tearDown(): void
    {
        reset_theme_dir_cache();
        reset_theme_identifier_cache();
    }

    // ─── theme_dir_cache ───────────────────────────────────────

    public function testThemeDirCacheReturnsByReference(): void
    {
        $cache = &theme_dir_cache();
        $this->assertNull($cache);

        $cache = 'http://example.com/themes/blog/';
        $cache2 = &theme_dir_cache();
        $this->assertSame('http://example.com/themes/blog/', $cache2);
    }

    public function testResetThemeDirCacheClearsState(): void
    {
        $cache = &theme_dir_cache();
        $cache = 'http://example.com/themes/blog/';

        reset_theme_dir_cache();

        $cache2 = &theme_dir_cache();
        $this->assertNull($cache2);
    }

    public function testThemeDirReturnsMemoizedValue(): void
    {
        $cache = &theme_dir_cache();
        $cache = 'http://test.com/themes/mytheme/';

        $result = theme_dir();
        $this->assertSame('http://test.com/themes/mytheme/', $result);
    }

    public function testThemeDirUsesCacheOnSecondCall(): void
    {
        $cache = &theme_dir_cache();
        $cache = 'http://cached.com/themes/cached/';

        $first = theme_dir();
        $second = theme_dir();

        $this->assertSame($first, $second);
        $this->assertSame('http://cached.com/themes/cached/', $first);
    }

    // ─── theme_identifier_cache ────────────────────────────────

    public function testThemeIdentifierCacheReturnsByReference(): void
    {
        $cache = &theme_identifier_cache();
        $this->assertIsArray($cache);
        $this->assertArrayHasKey('theme', $cache);
        $this->assertArrayHasKey('resolved', $cache);
        $this->assertFalse($cache['resolved']);
    }

    public function testResetThemeIdentifierCacheClearsState(): void
    {
        $cache = &theme_identifier_cache();
        $cache['theme'] = ['theme_directory' => 'mytheme'];
        $cache['resolved'] = true;

        reset_theme_identifier_cache();

        $cache2 = &theme_identifier_cache();
        $this->assertNull($cache2['theme']);
        $this->assertFalse($cache2['resolved']);
    }

    public function testThemeIdentifierMemoizesResult(): void
    {
        $cache = &theme_identifier_cache();
        $cache['theme'] = ['theme_directory' => 'blog'];
        $cache['resolved'] = true;

        $result = theme_identifier();
        $this->assertSame('blog', $result['theme_directory']);
    }

    public function testThemeIdentifierReturnsEmptyWhenNoThemeSetAndNotResolved(): void
    {
        reset_theme_identifier_cache();

        $result = theme_identifier();
        $this->assertEmpty($result);
    }

    public function testThemeIdentifierFunctionExists(): void
    {
        $this->assertTrue(function_exists('theme_dir'));
        $this->assertTrue(function_exists('theme_dir_cache'));
        $this->assertTrue(function_exists('reset_theme_dir_cache'));
        $this->assertTrue(function_exists('theme_identifier'));
        $this->assertTrue(function_exists('theme_identifier_cache'));
        $this->assertTrue(function_exists('reset_theme_identifier_cache'));
    }

    public function testResetFunctionsAllowFreshResolution(): void
    {
        $dirCache = &theme_dir_cache();
        $dirCache = 'http://first.com/themes/a/';
        $this->assertSame('http://first.com/themes/a/', theme_dir());

        reset_theme_dir_cache();
        $dirCache = &theme_dir_cache();
        $dirCache = 'http://second.com/themes/b/';
        $this->assertSame('http://second.com/themes/b/', theme_dir());
    }
}
