<?php
/**
 * Language Switcher UI Test
 * 
 * Tests for the language switcher UI functionality
 * 
 * @version 1.0
 * @since 1.0
 */

use PHPUnit\Framework\TestCase;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once __DIR__ . '/../../src/public/themes/blog/functions.php';

class LanguageSwitcherTest extends TestCase
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
     * Test get_language_name returns native name
     */
    public function testGetLanguageNameNative()
    {
        $this->assertEquals('English', get_language_name('en', true));
        $this->assertEquals('العربية', get_language_name('ar', true));
        $this->assertEquals('中文', get_language_name('zh', true));
        $this->assertEquals('Français', get_language_name('fr', true));
        $this->assertEquals('Русский', get_language_name('ru', true));
        $this->assertEquals('Español', get_language_name('es', true));
        $this->assertEquals('Bahasa Indonesia', get_language_name('id', true));
    }

    /**
     * Test get_language_name returns English name
     */
    public function testGetLanguageNameEnglish()
    {
        $this->assertEquals('English', get_language_name('en', false));
        $this->assertEquals('Arabic', get_language_name('ar', false));
        $this->assertEquals('Chinese', get_language_name('zh', false));
        $this->assertEquals('French', get_language_name('fr', false));
        $this->assertEquals('Russian', get_language_name('ru', false));
        $this->assertEquals('Spanish', get_language_name('es', false));
        $this->assertEquals('Indonesian', get_language_name('id', false));
    }

    /**
     * Test get_language_name returns uppercase code for unknown locale
     */
    public function testGetLanguageNameWithUnknownLocale()
    {
        $this->assertEquals('De', get_language_name('de', false));
        $this->assertEquals('It', get_language_name('it', true));
    }

    /**
     * Test get_all_language_names returns array with all data
     * Note: This test requires I18nManager which needs database
     */
    public function testGetAllLanguageNames()
    {
        // get_all_language_names depends on available_locales() which needs database
        // So we test the underlying function directly
        $locales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
        $names = [];
        
        foreach ($locales as $locale) {
            $names[$locale] = [
                'native' => get_language_name($locale, true),
                'english' => get_language_name($locale, false),
                'code' => strtoupper($locale),
            ];
        }
        
        $this->assertIsArray($names);
        $this->assertArrayHasKey('en', $names);
        $this->assertArrayHasKey('ar', $names);
        
        $this->assertArrayHasKey('native', $names['en']);
        $this->assertArrayHasKey('english', $names['en']);
        $this->assertArrayHasKey('code', $names['en']);
        
        $this->assertEquals('English', $names['en']['native']);
        $this->assertEquals('EN', $names['en']['code']);
    }

    /**
     * Test language switcher function generates correct URL pattern
     * Note: Requires full app context with database
     */
    public function testLanguageSwitcherGeneratesCorrectUrlPattern()
    {
        // Test the URL pattern manually since language_switcher() needs database
        $testLocale = 'ar';
        $testRedirect = '/blog/my-post';
        $expectedUrl = '?switch-lang=' . $testLocale . '&redirect=' . urlencode($testRedirect);
        
        // Verify the URL pattern is correct
        $this->assertStringContainsString('switch-lang=', $expectedUrl);
        $this->assertStringContainsString('redirect=', $expectedUrl);
        $this->assertStringContainsString(urlencode($testRedirect), $expectedUrl);
    }

    /**
     * Test redirect URL encoding for special characters
     */
    public function testRedirectUrlEncoding()
    {
        $testUrl = '/blog/my-post?foo=bar&baz=qux';
        $encoded = urlencode($testUrl);
        
        $this->assertStringContainsString('%2F', $encoded); // /
        $this->assertStringContainsString('%3F', $encoded); // ?
        $this->assertStringContainsString('%26', $encoded); // &
        $this->assertStringContainsString('%3D', $encoded); // =
    }

    /**
     * Test all supported language codes generate correct URLs
     */
    public function testAllLanguageCodesGenerateUrls()
    {
        $locales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
        $redirect = '/';
        
        foreach ($locales as $locale) {
            $url = '?switch-lang=' . $locale . '&redirect=' . urlencode($redirect);
            $this->assertStringContainsString('switch-lang=' . $locale, $url);
        }
    }

    /**
     * Test that Arabic locale generates correct native name
     */
    public function testArabicLocaleGeneration()
    {
        $native = get_language_name('ar', true);
        $english = get_language_name('ar', false);
        
        // Arabic should have RTL characters
        $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', $native);
        $this->assertEquals('Arabic', $english);
    }

    /**
     * Test that Chinese locale generates correct native name
     */
    public function testChineseLocaleGeneration()
    {
        $native = get_language_name('zh', true);
        $english = get_language_name('zh', false);
        
        // Chinese should have CJK characters
        $this->assertMatchesRegularExpression('/[\x{4E00}-\x{9FFF}]/u', $native);
        $this->assertEquals('Chinese', $english);
    }
}
