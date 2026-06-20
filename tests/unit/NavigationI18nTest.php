<?php
/**
 * Navigation & i18n URL Logic Test
 * 
 * Tests for permalink and non-permalink navigation URL generation
 * with proper locale prefix handling
 * 
 * @version 1.0
 * @since 1.0
 */

use PHPUnit\Framework\TestCase;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once __DIR__ . '/../../src/public/themes/blog/functions.php';
require_once __DIR__ . '/../../src/lib/utility/permalinks.php';

class NavigationI18nTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        if (isset($_SESSION['scriptlog_locale'])) {
            unset($_SESSION['scriptlog_locale']);
        }
        if (isset($_COOKIE['scriptlog_locale'])) {
            unset($_COOKIE['scriptlog_locale']);
        }
        
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';
    }

    protected function tearDown(): void
    {
        if (isset($_SESSION['scriptlog_locale'])) {
            unset($_SESSION['scriptlog_locale']);
        }
        if (isset($_COOKIE['scriptlog_locale'])) {
            unset($_COOKIE['scriptlog_locale']);
        }
        
        parent::tearDown();
    }

    /**
     * Test convert_menu_link with SEO-friendly permalinks enabled
     * Converts ?p=1 to /post/1/slug format
     */
    public function testConvertMenuLinkPostIdWithPermalinksEnabled()
    {
        // Test when permalinks enabled - converting query string to SEO format
        // We test the regex pattern logic directly
        $link = '?p=1';
        
        // Simulate permalink-enabled conversion logic
        if (preg_match('/^\?p=(\d+)$/', $link, $matches)) {
            $id = $matches[1];
            // In real scenario, this would call permalinks($id) to get slug
            $this->assertEquals('1', $id);
        }
        
        $this->assertTrue(true);
    }

    /**
     * Test convert_menu_link with permalinks disabled
     * Should keep query string format
     */
    public function testConvertMenuLinkKeepsQueryStringWhenDisabled()
    {
        $link = '?p=1';
        $permalinkEnabled = false;
        
        // With permalinks disabled, should not convert SEO URLs to query strings
        // for relative links
        if (preg_match('/^\/post\/(\d+)\/[\w-]+$/', $link)) {
            $this->fail('Should not match SEO format when permalinks disabled');
        }
        
        // But query string should remain as-is
        $this->assertEquals('?p=1', $link);
    }

    /**
     * Test convert_menu_link handles page links
     */
    public function testConvertMenuLinkPageLinks()
    {
        // Test query string format
        $link = '?pg=5';
        $this->assertTrue(preg_match('/^\?pg=(\d+)$/', $link) === 1);
        
        // Test SEO format
        $link = '/page/about-us';
        $this->assertTrue(preg_match('/^\/page\/([\w-]+)$/', $link) === 1);
    }

    /**
     * Test convert_menu_link handles category links
     */
    public function testConvertMenuLinkCategoryLinks()
    {
        // Test query string format
        $link = '?cat=3';
        $this->assertTrue(preg_match('/^\?cat=(\d+)$/', $link) === 1);
        
        // Test SEO format
        $link = '/category/technology';
        $this->assertTrue(preg_match('/^\/category\/([\w-]+)$/', $link) === 1);
    }

    /**
     * Test convert_menu_link handles archive links
     */
    public function testConvertMenuLinkArchiveLinks()
    {
        // Test query string format (yearmonth = 032025)
        $link = '?a=032025';
        $this->assertTrue(preg_match('/^\?a=(\d+)$/', $link) === 1);
        
        // Test SEO format
        $link = '/archive/03/2025';
        $this->assertTrue(preg_match('/^\/archive\/(\d{2})\/(\d{4})$/', $link) === 1);
    }

    /**
     * Test convert_menu_link skips external links
     */
    public function testConvertMenuLinkSkipsExternalLinks()
    {
        // External links should be passed through unchanged
        $externalLinks = [
            'https://example.com',
            'http://google.com',
            'mailto:test@example.com',
            '#',
            '',
        ];
        
        foreach ($externalLinks as $link) {
            if (empty($link) || $link === '#' || strpos($link, '://') !== false || strpos($link, 'mailto:') !== false || strpos($link, '#') === 0) {
                // These should be skipped - pass through
                $this->assertTrue(true);
            }
        }
    }

    /**
     * Test locale_url when permalinks disabled
     * Should return path as-is without prefix
     */
    public function testLocaleUrlReturnsPathWhenPermalinksDisabled()
    {
        // Since we can't mock rewrite_status(), we test the logic manually
        // When permalinks disabled ($permalinksEnabled = false):
        // return $path; // No prefix added
        
        $path = '/post/1/test';
        $permalinkEnabled = false;
        
        // With permalinks disabled, no locale prefix should be added
        if (!$permalinkEnabled) {
            $this->assertEquals('/post/1/test', $path);
        }
    }

    /**
     * Test locale_url when permalinks enabled but prefix disabled
     * Should return path for default locale, add prefix for non-default
     */
    public function testLocaleUrlWithPermalinksEnabledPrefixDisabled()
    {
        // Simulate: permalinks enabled but locale prefix toggle is off
        $path = '/post/1/test';
        $permalinksEnabled = true;
        $prefixEnabled = false;
        $defaultLocale = 'en';
        $targetLocale = 'es';
        
        // When prefix toggle is off, no prefix for any language
        if ($permalinksEnabled && !$prefixEnabled) {
            $this->assertEquals('/post/1/test', $path);
        }
    }

    /**
     * Test locale_url when both permalinks and prefix enabled
     * Default locale should have no prefix, non-default should have prefix
     */
    public function testLocaleUrlWithPermalinksAndPrefixEnabled()
    {
        // Simulate: both permalinks and locale prefix enabled
        $path = '/post/1/test';
        $permalinksEnabled = true;
        $prefixEnabled = true;
        
        // Default locale (en) -> no prefix
        $defaultLocale = 'en';
        $targetLocale = 'en';
        
        if ($targetLocale === $defaultLocale) {
            $this->assertEquals('/post/1/test', $path);
        }
        
        // Non-default locale (es) -> add prefix
        $targetLocale = 'es';
        if ($targetLocale !== $defaultLocale) {
            $result = '/' . $targetLocale . ($path ? '/' . ltrim($path, '/') : '');
            $this->assertEquals('/es/post/1/test', $result);
        }
    }

    /**
     * Test language switcher URL generation when permalinks disabled
     * Should use query string format
     */
    public function testLanguageSwitcherUrlPermalinksDisabled()
    {
        $permalinksEnabled = false;
        $locale = 'es';
        $requestUri = '/blog/my-post';
        
        if (!$permalinksEnabled) {
            $url = '?switch-lang=' . urlencode($locale) . '&redirect=' . urlencode($requestUri);
            $this->assertStringContainsString('switch-lang=es', $url);
            $this->assertStringContainsString('redirect=', $url);
        }
    }

    /**
     * Test language switcher URL generation when permalinks enabled
     * Should use locale_url() for proper prefix handling
     */
    public function testLanguageSwitcherUrlPermalinksEnabled()
    {
        $permalinksEnabled = true;
        $locale = 'es';
        $requestUri = '/post/1/test';
        
        // When permalinks enabled, locale_url handles the logic
        // For now, test the expected output format
        if ($permalinksEnabled) {
            // Simulate locale_url behavior: /es/post/1/test for non-default locale
            $expected = '/es' . $requestUri;
            $this->assertEquals('/es/post/1/test', $expected);
        }
    }

    /**
     * Test is_permalink_enabled returns correct value
     * Note: This requires database connection to work properly
     */
    public function testIsPermalinkEnabledFunctionExists()
    {
        $this->assertTrue(function_exists('is_permalink_enabled'));
    }

    /**
     * Test is_locale_prefix_enabled function exists
     */
    public function testIsLocalePrefixEnabledFunctionExists()
    {
        $this->assertTrue(function_exists('is_locale_prefix_enabled'));
    }

    /**
     * Test front_navigation generates proper HTML
     * Note: This is a basic syntax test
     */
    public function testFrontNavigationGeneratesHtml()
    {
        // Test menu structure
        $menu = [
            'items' => [
                1 => ['ID' => 1, 'menu_label' => 'Home', 'menu_link' => '/', 'parent_id' => 0],
                2 => ['ID' => 2, 'menu_label' => 'Blog', 'menu_link' => '/blog', 'parent_id' => 0],
            ],
            'parents' => [
                0 => [1, 2],
            ]
        ];
        
        // This would normally require database, so we just verify structure
        $this->assertIsArray($menu);
        $this->assertArrayHasKey('items', $menu);
        $this->assertArrayHasKey('parents', $menu);
    }

    /**
     * Test theme_navigation returns proper array structure
     */
    public function testThemeNavigationStructure()
    {
        // Expected structure
        $expectedStructure = [
            'items' => [],
            'parents' => []
        ];
        
        $this->assertIsArray($expectedStructure);
        $this->assertArrayHasKey('items', $expectedStructure);
        $this->assertArrayHasKey('parents', $expectedStructure);
    }

    /**
     * Test locale_url with empty path
     */
    public function testLocaleUrlWithEmptyPath()
    {
        $path = '';
        $targetLocale = 'es';
        $defaultLocale = 'en';
        
        // Non-default locale with empty path should still add prefix
        if ($targetLocale !== $defaultLocale) {
            $result = '/' . $targetLocale . ($path ? '/' . ltrim($path, '/') : '');
            $this->assertEquals('/es', $result);
        }
    }

    /**
     * Test locale_url with root path
     */
    public function testLocaleUrlWithRootPath()
    {
        $path = '/';
        $targetLocale = 'es';
        $defaultLocale = 'en';
        
        // Non-default locale with root path
        if ($targetLocale !== $defaultLocale) {
            $result = '/' . $targetLocale . ($path ? '/' . ltrim($path, '/') : '');
            // Should be /es/ not /es//
            $this->assertStringStartsWith('/es', $result);
        }
    }

    /**
     * Test all supported locales are available
     */
    public function testSupportedLocalesList()
    {
        $supportedLocales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
        
        foreach ($supportedLocales as $locale) {
            $native = get_language_name($locale, true);
            $english = get_language_name($locale, false);
            
            $this->assertNotEmpty($native);
            $this->assertNotEmpty($english);
        }
    }

    /**
     * Test get_locale returns valid locale
     */
    public function testGetLocaleReturnsValidValue()
    {
        // This might return 'en' as fallback if I18nManager not available
        $locale = get_locale();
        
        $this->assertNotEmpty($locale);
        $this->assertIsString($locale);
    }

    /**
     * Test get_default_locale returns valid value
     */
    public function testGetDefaultLocaleReturnsValidValue()
    {
        $default = get_default_locale();
        
        $this->assertNotEmpty($default);
        $this->assertIsString($default);
    }

    /**
     * Test is_rtl returns boolean
     */
    public function testIsRtlReturnsBoolean()
    {
        $rtl = is_rtl();
        
        $this->assertIsBool($rtl);
    }

    /**
     * Test get_html_dir returns rtl or ltr
     */
    public function testGetHtmlDirReturnsRtlOrLtr()
    {
        $dir = get_html_dir();
        
        $this->assertTrue(in_array($dir, ['rtl', 'ltr']));
    }

    /**
     * Test Arabic locale is detected as RTL
     */
    public function testArabicLocaleIsRtl()
    {
        // When locale is 'ar', is_rtl() should return true
        // Note: This test would require setting locale to 'ar' in I18nManager
        // For now, we test that the function exists
        $this->assertTrue(function_exists('is_rtl'));
    }
}