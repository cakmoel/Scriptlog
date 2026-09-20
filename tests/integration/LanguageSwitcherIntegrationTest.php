<?php
/**
 * Language Switcher Integration Tests
 *
 * End-to-end tests for the frontend language switching flow.
 * - Phase 1: Switch-lang parameter processing (mirrors lib/main.php)
 * - Phase 2: Database content filtering by locale (real DB queries)
 * - Phase 3: RTL detection through real is_rtl() function
 * - Phase 4: End-to-end flows with session/cookie/redirect
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../fixtures/language_switcher_fixture.php';

class LanguageSwitcherIntegrationTest extends TestCase
{
    private $session = [];
    private $cookie = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = [];
        $this->cookie = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $this->ensureFixtureData();
    }

    /**
     * Guarantee the locale-filtered content the DB tests expect exists,
     * regardless of test ordering or leftover state in blogware_test.
     *
     * @return void
     */
    private function ensureFixtureData(): void
    {
        $pdo = LanguageSwitcherFixture::getDb();
        if ($pdo === null) {
            return;
        }

        $ensurePost = function (string $slug, string $locale) use ($pdo): void {
            $exists = $pdo->prepare("SELECT COUNT(*) FROM tbl_posts WHERE post_slug = ?");
            $exists->execute([$slug]);
            if ((int) $exists->fetchColumn() === 0) {
                $pdo->prepare(
                    "INSERT INTO tbl_posts (post_title, post_slug, post_content, post_status, post_type, post_locale, post_author, post_date) VALUES (?, ?, ?, 'publish', 'blog', ?, 1, NOW())"
                )->execute([ucfirst(str_replace('-', ' ', $slug)) . ' Post', $slug, 'Locale fixture content', $locale]);
            }
        };

        $ensureTopic = function (string $slug, string $locale) use ($pdo): void {
            $exists = $pdo->prepare("SELECT COUNT(*) FROM tbl_topics WHERE topic_slug = ?");
            $exists->execute([$slug]);
            if ((int) $exists->fetchColumn() === 0) {
                $pdo->prepare(
                    "INSERT INTO tbl_topics (topic_title, topic_slug, topic_status, topic_locale) VALUES (?, ?, 'Y', ?)"
                )->execute([ucfirst(str_replace('-', ' ', $slug)) . ' Category', $slug, $locale]);
            }
        };

        $ensurePost('chinese-post', 'zh');
        $ensurePost('french-post', 'fr');
        $ensureTopic('arabic-category', 'ar');
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_SERVER['REQUEST_URI'] = '/';
        parent::tearDown();
    }

    // ========================================================================
    // Phase 1: HTTP Switch-Lang Processing
    // Tests the exact same logic as lib/main.php lines 103-129
    // ========================================================================

    public function testSwitchLangSetsSessionAndReturnsRedirect(): void
    {
        $get = ['switch-lang' => 'ar', 'request_uri' => '/'];
        $result = LanguageSwitcherFixture::processSwitchLang($this->session, $get);

        $this->assertTrue($result['valid']);
        $this->assertEquals('ar', $result['locale']);
        $this->assertEquals('ar', $this->session['scriptlog_locale']);
        $this->assertEquals('/', $result['redirect_url']);
    }

    public function testSwitchLangForAllSupportedLocales(): void
    {
        $locales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];

        foreach ($locales as $locale) {
            $get = ['switch-lang' => $locale, 'request_uri' => '/'];
            $result = LanguageSwitcherFixture::processSwitchLang($this->session, $get);

            $this->assertTrue($result['valid'], "Failed for locale: $locale");
            $this->assertEquals($locale, $result['locale']);
            $this->assertEquals($locale, $this->session['scriptlog_locale']);

            unset($this->session['scriptlog_locale']);
        }
    }

    public function testRedirectUrlPreservesOriginalPathAndQuery(): void
    {
        $get = [
            'switch-lang' => 'ar',
            'request_uri' => '/blog/my-post?foo=bar',
        ];
        $result = LanguageSwitcherFixture::processSwitchLang($this->session, $get);

        $this->assertEquals('/blog/my-post?foo=bar', $result['redirect_url']);
    }

    public function testRedirectUrlStripsSwitchLangFromQuery(): void
    {
        $get = [
            'switch-lang' => 'ar',
            'request_uri' => '/blog?switch-lang=ar&foo=bar',
        ];
        $result = LanguageSwitcherFixture::processSwitchLang($this->session, $get);

        $this->assertStringNotContainsString('switch-lang', $result['redirect_url']);
        $this->assertEquals('/blog?foo=bar', $result['redirect_url']);
    }

    public function testRedirectUsesExplicitRedirectParam(): void
    {
        $get = [
            'switch-lang' => 'fr',
            'redirect' => '/custom-redirect-path',
            'request_uri' => '/some-page',
        ];
        $result = LanguageSwitcherFixture::processSwitchLang($this->session, $get);

        // lib/main.php line 113-115: redirect param takes priority over REQUEST_URI
        $this->assertEquals('/custom-redirect-path', $result['redirect_url']);
    }

    public function testRedirectDecodesUrlEncodedParam(): void
    {
        $get = [
            'switch-lang' => 'id',
            'redirect' => '/blog%2Fmy-post%3Ffoo%3Dbar',
            'request_uri' => '/',
        ];
        $result = LanguageSwitcherFixture::processSwitchLang($this->session, $get);

        // lib/main.php line 115: urldecode() is applied
        $this->assertEquals('/blog/my-post?foo=bar', $result['redirect_url']);
    }

    public function testSessionIsSetBySwitchLang(): void
    {
        $this->session['scriptlog_locale'] = 'fr';

        $locale = LanguageSwitcherFixture::detectLocale($this->session, $this->cookie);
        $this->assertEquals('fr', $locale);
    }

    public function testCookieIsUsedWhenSessionNotSet(): void
    {
        $this->cookie['scriptlog_locale'] = 'zh';

        $locale = LanguageSwitcherFixture::detectLocale($this->session, $this->cookie);
        $this->assertEquals('zh', $locale);
    }

    public function testInvalidSessionLocaleFallsBack(): void
    {
        $this->session['scriptlog_locale'] = 'xx';

        $locale = LanguageSwitcherFixture::detectLocale($this->session, $this->cookie);
        $this->assertEquals('en', $locale);
    }

    // ========================================================================
    // Phase 2: Database Content Filtering by Locale
    // Tests run real SQL against the test database (blogware_test)
    // ========================================================================

    public function testPostsAreFilteredByLocale(): void
    {
        if (!LanguageSwitcherFixture::isDbAvailable()) {
            $this->markTestSkipped('Test database not available');
        }

        $allPosts = LanguageSwitcherFixture::getAllPublishedPosts();
        $this->assertNotEmpty($allPosts, 'No test posts in database');

        $enPosts = LanguageSwitcherFixture::getPostsByLocale('en');
        $arPosts = LanguageSwitcherFixture::getPostsByLocale('ar');
        $zhPosts = LanguageSwitcherFixture::getPostsByLocale('zh');
        $frPosts = LanguageSwitcherFixture::getPostsByLocale('fr');

        $this->assertNotEmpty($enPosts, 'Expected English posts');
        $this->assertNotEmpty($arPosts, 'Expected Arabic posts');
        $this->assertNotEmpty($zhPosts, 'Expected Chinese posts');
        $this->assertNotEmpty($frPosts, 'Expected French posts');

        foreach ($enPosts as $post) {
            $this->assertEquals('en', $post['post_locale']);
        }
        foreach ($arPosts as $post) {
            $this->assertEquals('ar', $post['post_locale']);
        }

        // Verify filtering is exclusive — each locale gets only its own posts
        $enCount = count($enPosts);
        $arCount = count($arPosts);
        $totalCount = count($allPosts);

        $this->assertLessThan($totalCount, $enCount, 'en posts should be less than total');
        $this->assertLessThan($totalCount, $arCount, 'ar posts should be less than total');
        $this->assertSame($enCount + $arCount + count($zhPosts) + count($frPosts), $totalCount,
            'All locale posts should sum to total');
    }

    public function testTopicsAreFilteredByLocale(): void
    {
        if (!LanguageSwitcherFixture::isDbAvailable()) {
            $this->markTestSkipped('Test database not available');
        }

        $enTopics = LanguageSwitcherFixture::getTopicsByLocale('en');
        $arTopics = LanguageSwitcherFixture::getTopicsByLocale('ar');

        $this->assertNotEmpty($enTopics, 'Expected English topics');
        $this->assertNotEmpty($arTopics, 'Expected Arabic topics');

        foreach ($enTopics as $topic) {
            $this->assertEquals('en', $topic['topic_locale']);
        }
        foreach ($arTopics as $topic) {
            $this->assertEquals('ar', $topic['topic_locale']);
        }
    }

    public function testMenuItemsAreFilteredByLocale(): void
    {
        if (!LanguageSwitcherFixture::isDbAvailable()) {
            $this->markTestSkipped('Test database not available');
        }

        $enMenu = LanguageSwitcherFixture::getMenuItemsByLocale('en');
        $arMenu = LanguageSwitcherFixture::getMenuItemsByLocale('ar');

        $this->assertNotEmpty($enMenu, 'Expected English menu items');
        $this->assertNotEmpty($arMenu, 'Expected Arabic menu items');

        foreach ($enMenu as $item) {
            $this->assertEquals('en', $item['menu_locale']);
        }
        foreach ($arMenu as $item) {
            $this->assertEquals('ar', $item['menu_locale']);
        }

        // Verify correct English labels
        $this->assertEquals('Home', $enMenu[0]['menu_label']);
        $this->assertEquals('Blog', $enMenu[1]['menu_label']);
    }

    public function testNonexistentLocaleReturnsEmptyResults(): void
    {
        if (!LanguageSwitcherFixture::isDbAvailable()) {
            $this->markTestSkipped('Test database not available');
        }

        $posts = LanguageSwitcherFixture::getPostsByLocale('xx');
        $topics = LanguageSwitcherFixture::getTopicsByLocale('xx');
        $menus = LanguageSwitcherFixture::getMenuItemsByLocale('xx');

        $this->assertEmpty($posts);
        $this->assertEmpty($topics);
        $this->assertEmpty($menus);
    }

    // ========================================================================
    // Phase 3: RTL Detection (tests through real is_rtl() and DB lang_direction)
    // ========================================================================

    public function testArabicIsRtlInDatabase(): void
    {
        if (!LanguageSwitcherFixture::isDbAvailable()) {
            $this->markTestSkipped('Test database not available');
        }

        $direction = LanguageSwitcherFixture::getLanguageDirectionFromDb('ar');
        $this->assertEquals('rtl', $direction);
    }

    public function testEnglishIsLtrInDatabase(): void
    {
        if (!LanguageSwitcherFixture::isDbAvailable()) {
            $this->markTestSkipped('Test database not available');
        }

        $direction = LanguageSwitcherFixture::getLanguageDirectionFromDb('en');
        $this->assertEquals('ltr', $direction);
    }

    public function testAllNonArabicLocalesAreLtrInDatabase(): void
    {
        if (!LanguageSwitcherFixture::isDbAvailable()) {
            $this->markTestSkipped('Test database not available');
        }

        $locales = ['en', 'zh', 'fr', 'ru', 'es', 'id'];
        foreach ($locales as $locale) {
            $direction = LanguageSwitcherFixture::getLanguageDirectionFromDb($locale);
            $this->assertEquals('ltr', $direction, "$locale should be ltr in DB");
        }
    }

    public function testRealIsRtlFunctionReturnsTrueForArabic(): void
    {
        if (!LanguageSwitcherFixture::isDbAvailable()) {
            $this->markTestSkipped('Test database not available');
        }

        // Bootstrap I18nManager to enable real is_rtl() function
        if (!LanguageSwitcherFixture::bootstrapI18nManager()) {
            $this->markTestSkipped('I18nManager not available');
        }

        // Set locale to Arabic
        $i18n = I18nManager::getInstance();
        $i18n->setLanguageDirection('rtl');

        $this->assertTrue(is_rtl(), 'is_rtl() should return true for Arabic');
    }

    public function testRealGetHtmlDirReturnsRtlForArabic(): void
    {
        if (!LanguageSwitcherFixture::isDbAvailable()) {
            $this->markTestSkipped('Test database not available');
        }
        if (!LanguageSwitcherFixture::bootstrapI18nManager()) {
            $this->markTestSkipped('I18nManager not available');
        }

        $i18n = I18nManager::getInstance();
        $i18n->setLanguageDirection('rtl');

        $this->assertEquals('rtl', get_html_dir());
    }

    // ========================================================================
    // Phase 4: End-to-End Flows
    // ========================================================================

    public function testCompleteLanguageSwitchFromEnglishToArabic(): void
    {
        // Step 1: Detect locale — defaults to English
        $locale = LanguageSwitcherFixture::detectLocale($this->session, $this->cookie);
        $this->assertEquals('en', $locale);

        // Step 2: User clicks Arabic language
        $get = ['switch-lang' => 'ar', 'request_uri' => '/blog/my-post'];
        $result = LanguageSwitcherFixture::processSwitchLang($this->session, $get);

        // Step 3: Verify locale changed
        $this->assertTrue($result['valid']);
        $this->assertEquals('ar', $result['locale']);
        $this->assertEquals('ar', $this->session['scriptlog_locale']);

        // Step 4: Verify redirect preserves original path
        $this->assertEquals('/blog/my-post', $result['redirect_url']);

        // Step 5: Verify Arabic is RTL in database
        if (LanguageSwitcherFixture::isDbAvailable()) {
            $direction = LanguageSwitcherFixture::getLanguageDirectionFromDb('ar');
            $this->assertEquals('rtl', $direction);
        }
    }

    public function testLanguageSwitchPersistsAcrossSimulatedRequests(): void
    {
        // First request: switch to French
        $get = ['switch-lang' => 'fr', 'request_uri' => '/'];
        LanguageSwitcherFixture::processSwitchLang($this->session, $get);
        $this->assertEquals('fr', $this->session['scriptlog_locale']);

        // Second request (session persists, no cookie): still French
        $detected = LanguageSwitcherFixture::detectLocale($this->session, []);
        $this->assertEquals('fr', $detected);

        // Third request: still French
        $detected = LanguageSwitcherFixture::detectLocale($this->session, []);
        $this->assertEquals('fr', $detected);
    }

    public function testLanguageSwitchPersistsAcrossSessionRestore(): void
    {
        // Simulate session save & restore (session_start / session_decode)
        $get = ['switch-lang' => 'id', 'request_uri' => '/'];
        LanguageSwitcherFixture::processSwitchLang($this->session, $get);
        $this->assertEquals('id', $this->session['scriptlog_locale']);

        // Simulate new PHP request — session data carried over from storage
        $restoredSession = $this->session;
        $cookieFallback = [];

        $locale = LanguageSwitcherFixture::detectLocale($restoredSession, $cookieFallback);
        $this->assertEquals('id', $locale,
            'Locale must persist across page requests via session');
    }

    public function testInvalidLocaleIsRejected(): void
    {
        $get = ['switch-lang' => 'xx', 'request_uri' => '/'];
        $result = LanguageSwitcherFixture::processSwitchLang($this->session, $get);

        $this->assertFalse($result['valid']);
        $this->assertNull($result['locale']);
        $this->assertNull($result['redirect_url']);
        $this->assertArrayNotHasKey('scriptlog_locale', $this->session);
    }

    public function testMalformedLocaleCodeIsRejected(): void
    {
        $get = ['switch-lang' => 'en-us', 'request_uri' => '/'];
        $result = LanguageSwitcherFixture::processSwitchLang($this->session, $get);

        $this->assertFalse($result['valid']);
    }

    public function testEmptySwitchLangIsIgnored(): void
    {
        $get = ['switch-lang' => '', 'request_uri' => '/'];
        $result = LanguageSwitcherFixture::processSwitchLang($this->session, $get);

        $this->assertFalse($result['valid']);
        $this->assertArrayNotHasKey('scriptlog_locale', $this->session);
    }

    public function testDefaultLocaleFallbackWhenNothingSet(): void
    {
        $locale = LanguageSwitcherFixture::detectLocale($this->session, $this->cookie);
        $this->assertEquals('en', $locale);
    }

    public function testUpperCaseLocaleIsNormalizedToLowercase(): void
    {
        $get = ['switch-lang' => 'AR', 'request_uri' => '/'];
        $result = LanguageSwitcherFixture::processSwitchLang($this->session, $get);

        $this->assertEquals('ar', $result['locale']);
        $this->assertEquals('ar', $this->session['scriptlog_locale']);
    }

    public function testCookieFallbackWhenSessionExpires(): void
    {
        // Session has no locale, cookie has Indonesian
        $this->cookie['scriptlog_locale'] = 'id';

        $locale = LanguageSwitcherFixture::detectLocale($this->session, $this->cookie);
        $this->assertEquals('id', $locale);
    }

    public function testSessionTakesPriorityOverCookie(): void
    {
        // Both session and cookie set — session should win
        $this->session['scriptlog_locale'] = 'fr';
        $this->cookie['scriptlog_locale'] = 'ar';

        $locale = LanguageSwitcherFixture::detectLocale($this->session, $this->cookie);
        $this->assertEquals('fr', $locale,
            'Session locale must take priority over cookie');
    }

    public function testMultipleSequentialSwitches(): void
    {
        // Switch: en -> fr
        $get = ['switch-lang' => 'fr', 'request_uri' => '/'];
        LanguageSwitcherFixture::processSwitchLang($this->session, $get);
        $this->assertEquals('fr', LanguageSwitcherFixture::detectLocale($this->session, []));

        // Switch: fr -> ar
        $get = ['switch-lang' => 'ar', 'request_uri' => '/'];
        LanguageSwitcherFixture::processSwitchLang($this->session, $get);
        $this->assertEquals('ar', LanguageSwitcherFixture::detectLocale($this->session, []));

        // Switch: ar -> zh
        $get = ['switch-lang' => 'zh', 'request_uri' => '/'];
        LanguageSwitcherFixture::processSwitchLang($this->session, $get);
        $this->assertEquals('zh', LanguageSwitcherFixture::detectLocale($this->session, []));
    }
}
