<?php
/**
 * Admin Locale Initialization Test
 * 
 * Tests that the admin panel locale defaults to the language chosen
 * during installation (stored as lang_default in tbl_settings).
 * 
 * @version 1.0
 * @since 1.0
 */

use PHPUnit\Framework\TestCase;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class AdminLocaleInitializationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        if (isset($_SESSION['admin_locale'])) {
            unset($_SESSION['admin_locale']);
        }
        if (isset($_COOKIE['admin_locale'])) {
            unset($_COOKIE['admin_locale']);
        }
    }

    protected function tearDown(): void
    {
        if (isset($_SESSION['admin_locale'])) {
            unset($_SESSION['admin_locale']);
        }
        if (isset($_COOKIE['admin_locale'])) {
            unset($_COOKIE['admin_locale']);
        }
        
        parent::tearDown();
    }

    /**
     * Test admin_get_locale defaults to 'en' when no session/cookie is set
     */
    public function testAdminGetLocaleDefaultsToEnglish()
    {
        $this->assertEquals('en', admin_get_locale());
    }

    /**
     * Test that uninitialized state is detected correctly
     */
    public function testUninitializedStateDetected()
    {
        $uninitialized = !isset($_SESSION['admin_locale']) && !isset($_COOKIE['admin_locale']);
        
        $this->assertTrue($uninitialized);
        $this->assertEquals('en', admin_get_locale());
    }

    /**
     * Test that session takes priority over cookie
     */
    public function testSessionTakesPriorityOverCookie()
    {
        $_COOKIE['admin_locale'] = 'ar';
        $_SESSION['admin_locale'] = 'zh';
        
        $this->assertEquals('zh', admin_get_locale());
    }

    /**
     * Test that cookie is used when session is not set
     */
    public function testCookieUsedWhenSessionNotSet()
    {
        $_COOKIE['admin_locale'] = 'es';
        
        $this->assertEquals('es', admin_get_locale());
    }

    /**
     * Test that session alone is sufficient
     */
    public function testSessionAloneSetsLocale()
    {
        $_SESSION['admin_locale'] = 'fr';
        
        $this->assertEquals('fr', admin_get_locale());
    }

    /**
     * Test the DB initialization condition
     * 
     * When neither session nor cookie is set, the condition
     * should trigger a DB lookup for lang_default
     */
    public function testDbInitializationCondition()
    {
        // Before: uninitialized
        $shouldLoadFromDb = !isset($_SESSION['admin_locale']) && !isset($_COOKIE['admin_locale']);
        $this->assertTrue($shouldLoadFromDb);
        $this->assertEquals('en', admin_get_locale());
        
        // Simulate DB returning lang_default = 'id'
        $defaultLang = 'id';
        $availableLocales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
        
        if (in_array($defaultLang, $availableLocales)) {
            $_SESSION['admin_locale'] = $defaultLang;
            $_COOKIE['admin_locale'] = $defaultLang;
        }
        
        // After: initialized
        $this->assertEquals('id', admin_get_locale());
        
        $shouldSkipDbLoad = !isset($_SESSION['admin_locale']) && !isset($_COOKIE['admin_locale']);
        $this->assertFalse($shouldSkipDbLoad);
    }

    /**
     * Test that after locale is set, DB query condition evaluates to false
     */
    public function testDbQuerySkippedAfterLocaleSet()
    {
        $_SESSION['admin_locale'] = 'fr';
        
        $shouldQueryDb = !isset($_SESSION['admin_locale']) && !isset($_COOKIE['admin_locale']);
        
        $this->assertFalse($shouldQueryDb);
        $this->assertEquals('fr', admin_get_locale());
    }

    /**
     * Test that admin_get_locale returns the correct value for all locales
     * when session is set (simulating admin_set_locale behavior)
     */
    public function testAdminGetLocaleAllLocales()
    {
        $locales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
        
        foreach ($locales as $locale) {
            $this->setUp();
            $_SESSION['admin_locale'] = $locale;
            $this->assertEquals($locale, admin_get_locale());
        }
    }

    /**
     * Test the full initialization flow that runs in admin/index.php
     * 
     * This simulates the exact logic:
     * 1. User logs in for first time after installing with 'id'
     * 2. No session/cookie set -> condition triggers DB lookup
     * 3. DB returns lang_default = 'id'
     * 4. Locale is set
     * 5. Subsequent requests skip DB lookup
     */
    public function testFullInitializationFlow()
    {
        // Step 1: Uninitialized (first admin login after installation)
        $this->assertTrue(!isset($_SESSION['admin_locale']) && !isset($_COOKIE['admin_locale']));
        $this->assertEquals('en', admin_get_locale());
        
        // Step 2: Simulate DB returning lang_default = 'id'
        $defaultLang = 'id';
        $availableLocales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
        
        // Step 3: Execute same logic as admin/index.php
        if (!isset($_SESSION['admin_locale']) && !isset($_COOKIE['admin_locale'])) {
            if (in_array($defaultLang, $availableLocales)) {
                $_SESSION['admin_locale'] = $defaultLang;
                $_COOKIE['admin_locale'] = $defaultLang;
            }
        }
        
        // Step 4: Verify locale reflects chosen installation language
        $this->assertEquals('id', admin_get_locale());
        
        // Step 5: Subsequent requests skip DB query
        $this->assertTrue(isset($_SESSION['admin_locale']));
        $shouldQueryDb = !isset($_SESSION['admin_locale']) && !isset($_COOKIE['admin_locale']);
        $this->assertFalse($shouldQueryDb);
    }

    /**
     * Test DB load condition only triggers when both session and cookie are empty
     */
    public function testDbLoadOnlyWhenSessionAndCookieEmpty()
    {
        // Neither set -> triggers DB check
        $this->assertTrue(!isset($_SESSION['admin_locale']) && !isset($_COOKIE['admin_locale']));
        
        // Session only -> skip DB
        $_SESSION['admin_locale'] = 'en';
        $this->assertFalse(!isset($_SESSION['admin_locale']) && !isset($_COOKIE['admin_locale']));
        
        // Cookie only -> skip DB
        unset($_SESSION['admin_locale']);
        $_COOKIE['admin_locale'] = 'en';
        $this->assertFalse(!isset($_SESSION['admin_locale']) && !isset($_COOKIE['admin_locale']));
    }

    /**
     * Test that setting locale to English still correctly stores and retrieves 'en'
     */
    public function testEnglishLocaleStoredAndRetrieved()
    {
        $_SESSION['admin_locale'] = 'en';
        $this->assertEquals('en', admin_get_locale());
    }

    /**
     * Test the condition guard: session+locale combo detection
     * 
     * This guards against a scenario where admin_get_locale() returns
     * 'en' by default but session isn't actually set
     */
    public function testConditionGuardAgainstDefaultAmbiguity()
    {
        // Baseline: uninitialized
        $this->assertNull($_SESSION['admin_locale'] ?? null);
        $this->assertNull($_COOKIE['admin_locale'] ?? null);
        $this->assertEquals('en', admin_get_locale()); // default fallback
        
        // Simulate DB having lang_default = 'en' and setting it
        $defaultLang = 'en';
        $availableLocales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
        
        if (!isset($_SESSION['admin_locale']) && !isset($_COOKIE['admin_locale'])) {
            if (in_array($defaultLang, $availableLocales)) {
                $_SESSION['admin_locale'] = $defaultLang;
                $_COOKIE['admin_locale'] = $defaultLang;
            }
        }
        
        // Now session IS set - subsequent requests won't re-query DB
        $this->assertTrue(isset($_SESSION['admin_locale']));
        $this->assertEquals('en', admin_get_locale());
    }

    /**
     * Test that available locale codes match the valid locales list
     */
    public function testAvailableLocalesCoverAllLangs()
    {
        $availableLocales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
        
        foreach ($availableLocales as $locale) {
            $_SESSION['admin_locale'] = $locale;
            $this->assertEquals($locale, admin_get_locale());
        }
    }
}
