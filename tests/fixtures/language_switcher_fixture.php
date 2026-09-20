<?php
/**
 * Language Switcher Test Fixtures
 *
 * Helper functions and data for language switcher integration tests.
 * processSwitchLang() replicates the exact logic from lib/main.php (lines 103-129)
 * for testability (the real code calls header()+exit() which can't run in CLI).
 */

// Load theme functions for real is_rtl(), get_locale(), etc.
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!function_exists('t')) {
    require_once __DIR__ . '/../../public/themes/blog/functions.php';
}

class LanguageSwitcherFixture
{
    private static $dbc = null;

    /**
     * Initialize database connection
     */
    public static function initDb(): void
    {
        if (self::$dbc !== null) {
            return;
        }

        try {
            self::$dbc = new PDO(
                'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
                'blogwareuser',
                'userblogware',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            self::$dbc = null;
        }
    }

    public static function getDb(): ?PDO
    {
        self::initDb();
        return self::$dbc;
    }

    public static function isDbAvailable(): bool
    {
        self::initDb();
        return self::$dbc !== null;
    }

    /**
     * Get languages from database
     */
    public static function getLanguages(): array
    {
        if (!self::isDbAvailable()) {
            return [];
        }
        $stmt = self::$dbc->query("SELECT * FROM tbl_languages ORDER BY lang_sort");
        return $stmt->fetchAll();
    }

    /**
     * Get posts filtered by locale from the real database
     */
    public static function getPostsByLocale(string $locale): array
    {
        if (!self::isDbAvailable()) {
            return [];
        }
        $stmt = self::$dbc->prepare(
            "SELECT * FROM tbl_posts WHERE post_locale = ? AND post_status = 'publish'"
        );
        $stmt->execute([$locale]);
        return $stmt->fetchAll();
    }

    /**
     * Get topics filtered by locale from the real database
     */
    public static function getTopicsByLocale(string $locale): array
    {
        if (!self::isDbAvailable()) {
            return [];
        }
        $stmt = self::$dbc->prepare(
            "SELECT * FROM tbl_topics WHERE topic_locale = ? AND topic_status = 'Y'"
        );
        $stmt->execute([$locale]);
        return $stmt->fetchAll();
    }

    /**
     * Get menu items filtered by locale from the real database
     */
    public static function getMenuItemsByLocale(string $locale): array
    {
        if (!self::isDbAvailable()) {
            return [];
        }
        $stmt = self::$dbc->prepare(
            "SELECT * FROM tbl_menu WHERE menu_locale = ? AND menu_status = 'Y' ORDER BY menu_sort"
        );
        $stmt->execute([$locale]);
        return $stmt->fetchAll();
    }

    /**
     * Get all published posts (no locale filter)
     */
    public static function getAllPublishedPosts(): array
    {
        if (!self::isDbAvailable()) {
            return [];
        }
        $stmt = self::$dbc->query("SELECT * FROM tbl_posts WHERE post_status = 'publish'");
        return $stmt->fetchAll();
    }

    /**
     * Lookup language direction from real database (same logic as I18nManager::getLanguageDirection)
     */
    public static function getLanguageDirectionFromDb(string $locale): string
    {
        if (!self::isDbAvailable()) {
            return 'ltr';
        }
        try {
            $stmt = self::$dbc->prepare(
                "SELECT lang_direction FROM tbl_languages WHERE lang_code = ?"
            );
            $stmt->execute([$locale]);
            $row = $stmt->fetch();
            return $row['lang_direction'] ?? 'ltr';
        } catch (PDOException $e) {
            return 'ltr';
        }
    }

    /**
     * Process switch-lang — replicates lib/main.php lines 103-129 EXACTLY.
     *
     * Differences from real code (required for CLI testability):
     * - Returns redirect URL string instead of calling header()+exit()
     * - Returns null for invalid locales instead of silently ignoring
     * - Returns result struct instead of exit()
     */
    public static function processSwitchLang(
        array &$session,
        array $get
    ): array {
        $result = [
            'valid' => false,
            'locale' => null,
            'redirect_url' => null,
        ];

        // lib/main.php line 104
        if (!isset($get['switch-lang']) || empty($get['switch-lang'])) {
            return $result;
        }

        // lib/main.php line 105
        $langCode = preg_replace('/[^a-z]{2}/', '', strtolower($get['switch-lang']));

        // lib/main.php line 106 — exact same inline array
        $validLocales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];

        // lib/main.php line 108
        if (!in_array($langCode, $validLocales)) {
            return $result;
        }

        // lib/main.php line 109
        $session['scriptlog_locale'] = $langCode;

        // lib/main.php line 110 — setcookie (simulated, can't set real cookie in CLI)
        // setcookie('scriptlog_locale', $langCode, time() + (86400 * 365), '/');

        // lib/main.php lines 112-127 — redirect URL construction
        // lib/main.php line 113
        if (!empty($get['redirect'])) {
            // lib/main.php line 115
            $result['redirect_url'] = urldecode($get['redirect']);
        } else {
            // lib/main.php lines 117-125
            $urlParts = parse_url($get['request_uri'] ?? '/');
            $path = $urlParts['path'] ?? '/';
            $query = [];
            if (isset($urlParts['query'])) {
                parse_str($urlParts['query'], $query);
                unset($query['switch-lang']);
            }
            $newQuery = !empty($query) ? '?' . http_build_query($query) : '';
            $result['redirect_url'] = $path . $newQuery;
        }

        $result['valid'] = true;
        $result['locale'] = $langCode;

        return $result;
    }

    /**
     * Detect locale from session/cookie/default
     * Same logic as the real LocaleDetector + config default
     */
    public static function detectLocale(array $session, array $cookie): string
    {
        // Session takes priority
        if (isset($session['scriptlog_locale'])) {
            $validLocales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
            if (in_array($session['scriptlog_locale'], $validLocales)) {
                return $session['scriptlog_locale'];
            }
        }

        // Cookie fallback
        if (isset($cookie['scriptlog_locale'])) {
            $validLocales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
            if (in_array($cookie['scriptlog_locale'], $validLocales)) {
                return $cookie['scriptlog_locale'];
            }
        }

        // Default
        return 'en';
    }

    /**
     * Initialize I18nManager for testing real is_rtl() function.
     * Uses test database for LanguageDao lookups.
     */
    public static function bootstrapI18nManager(): bool
    {
        if (!self::isDbAvailable()) {
            return false;
        }

        if (!class_exists('I18nManager')) {
            return false;
        }

        $i18n = I18nManager::getInstance();

        if (!$i18n->isInitialized()) {
            $ref = new ReflectionClass($i18n);
            $prop = $ref->getProperty('locale');
            $prop->setAccessible(true);

            $current = $prop->getValue($i18n);
            if (empty($current)) {
                $prop->setValue($i18n, 'en');
            }
        }

        return true;
    }
}
