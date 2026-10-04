<?php
/**
 * SecurityRemediationTest
 *
 * Unit tests for the RCE remediation + performance hardening working set:
 * per-form CSRF slots, plugin directory basename, dotenv cache, CSP nonce
 * builder, page-cache anonymity/search guards, post-content sanitizers,
 * scheduled-post throttle, nginx deny rules and the idempotent
 * HTMLPurifier loader.
 *
 * All tests are hermetic (temp dirs, no database).
 */

use PHPUnit\Framework\TestCase;
use Scriptlog\Core\Sanitize;
use Scriptlog\Dao\ConfigurationDao;
use Scriptlog\Dao\PostDao;
use Scriptlog\Service\PluginService;
use Scriptlog\Service\ScheduledPostService;

class SecurityRemediationTest extends TestCase
{
    private $origCookie;
    private $origGet;
    private $origServer;
    private $origSessionId;

    protected function setUp(): void
    {
        $this->origCookie = $_COOKIE;
        $this->origGet = $_GET;
        $this->origServer = $_SERVER;

        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTPS']);
    }

    protected function tearDown(): void
    {
        $_COOKIE = $this->origCookie;
        $_GET = $this->origGet;
        $_SERVER = $this->origServer;
        if (function_exists('reset_theme_meta_cache')) {
            reset_theme_meta_cache();
        }
    }

    // ---------- form-security: per-form CSRF slots ----------

    public function testCheckFormRequestAllowsPerFormCsrfSlots(): void
    {
        $this->assertTrue(check_form_request(['csrfPostForm' => 'x'], []));
        $this->assertTrue(check_form_request(['csrfThemeInstall' => 'x'], []));
        $this->assertTrue(check_form_request(['csrfToken' => 'x'], []));
    }

    public function testCheckFormRequestStillRejectsUnknownKeys(): void
    {
        $this->assertFalse(check_form_request(['evil' => 'x'], []));
        $this->assertTrue(check_form_request(['title' => 'x'], ['title']));
    }

    // ---------- PluginService: directory traversal guard (R5) ----------

    public function testPluginDirectoryIsReducedToBasename(): void
    {
        $pluginDao = $this->createMock(\PluginDao::class);
        $validator = $this->createMock(\FormValidator::class);
        $sanitizer = $this->createMock(\Sanitize::class);
        $svc = new PluginService($pluginDao, $validator, $sanitizer);
        $prop = new ReflectionProperty(PluginService::class, 'directory');
        $prop->setAccessible(true);

        $svc->setPluginDirectory('../../evil');
        $this->assertSame('evil', $prop->getValue($svc));

        $svc->setPluginDirectory('/abs/path/my-plugin');
        $this->assertSame('my-plugin', $prop->getValue($svc));

        $svc->setPluginDirectory('plain-dir');
        $this->assertSame('plain-dir', $prop->getValue($svc));
    }

    // ---------- dotenv-cache (S1) ----------

    public function testDotenvCachePathsResolveUnderAppRoot(): void
    {
        list($envFile, $cacheFile) = dotenv_cache_paths('/var/www/app/');
        $this->assertSame('/var/www/app/.env', $envFile);
        $this->assertStringEndsWith('dotenv' . DIRECTORY_SEPARATOR . 'env.cache.php', $cacheFile);
    }

    public function testDotenvCacheLoadBuildsCacheThenServesWarm(): void
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dotenv_test_' . uniqid();
        mkdir($dir, 0755, true);
        $envFile = $dir . DIRECTORY_SEPARATOR . '.env';
        $cacheFile = $dir . DIRECTORY_SEPARATOR . 'env.cache.php';
        file_put_contents($envFile, "DOTENV_CACHE_TEST_VAR=hello123\n");

        unset($_ENV['DOTENV_CACHE_TEST_VAR'], $_SERVER['DOTENV_CACHE_TEST_VAR']);

        dotenv_cache_load($envFile, $cacheFile, function () {
            $_ENV['DOTENV_CACHE_TEST_VAR'] = 'hello123';
        });
        $this->assertSame('hello123', $_ENV['DOTENV_CACHE_TEST_VAR']);
        $this->assertFileExists($cacheFile);

        // Warm path: loader must not run, value still replayed immutably.
        unset($_ENV['DOTENV_CACHE_TEST_VAR'], $_SERVER['DOTENV_CACHE_TEST_VAR']);
        touch($cacheFile, filemtime($envFile) + 10);
        dotenv_cache_load($envFile, $cacheFile, function () {
            throw new RuntimeException('cold loader must not run on warm cache');
        });
        $this->assertSame('hello123', $_ENV['DOTENV_CACHE_TEST_VAR']);

        // Immutable replay: pre-existing values win.
        $_ENV['DOTENV_CACHE_TEST_VAR'] = 'pinned';
        dotenv_cache_load($envFile, $cacheFile, function () {
            throw new RuntimeException('cold loader must not run on warm cache');
        });
        $this->assertSame('pinned', $_ENV['DOTENV_CACHE_TEST_VAR']);

        unset($_ENV['DOTENV_CACHE_TEST_VAR'], $_SERVER['DOTENV_CACHE_TEST_VAR']);
    }

    // ---------- CSP builder (R2) ----------

    public function testBuildCspHeadersCarriesNonceAndBlocksEventHandlers(): void
    {
        $headers = build_csp_headers('https://example.com', 'abc123');
        $this->assertArrayHasKey('enforced', $headers);
        $this->assertArrayHasKey('reportOnly', $headers);
        $this->assertStringContainsString("'nonce-abc123'", $headers['enforced']);
        $this->assertStringContainsString("'nonce-abc123'", $headers['reportOnly']);
        $this->assertStringContainsString("script-src-attr 'none'", $headers['enforced']);
        $this->assertStringNotContainsString("'nonce-'", build_csp_headers('https://example.com', '')['enforced']);
    }

    // ---------- page-cache guards (C1/H1) ----------

    public function testPageCacheIsAnonymousRejectsAllAuthMarkers(): void
    {
        unset($_COOKIE['scriptlog_auth'], $_COOKIE['scriptlog_validator'], $_COOKIE['scriptlog_selector']);
        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['scriptlog_session_id']);
        }
        $this->assertTrue(page_cache_is_anonymous());

        $_COOKIE['scriptlog_auth'] = 't';
        $this->assertFalse(page_cache_is_anonymous());
        unset($_COOKIE['scriptlog_auth']);

        $_COOKIE['scriptlog_validator'] = 't';
        $this->assertFalse(page_cache_is_anonymous());
        unset($_COOKIE['scriptlog_validator']);

        $_COOKIE['scriptlog_selector'] = 't';
        $this->assertFalse(page_cache_is_anonymous());
        unset($_COOKIE['scriptlog_selector']);
    }

    public function testPageCacheIsSearchRequestCoversAllModes(): void
    {
        unset($_GET['search'], $_GET['s'], $_GET['q']);
        $_SERVER['REQUEST_URI'] = '/about';
        $this->assertFalse(page_cache_is_search_request());

        $_GET['q'] = 'hello';
        $this->assertTrue(page_cache_is_search_request());
        unset($_GET['q']);

        $_GET['s'] = 'hello';
        $this->assertTrue(page_cache_is_search_request());
        unset($_GET['s']);

        $_GET['search'] = 'hello';
        $this->assertTrue(page_cache_is_search_request());
        unset($_GET['search']);

        $_SERVER['REQUEST_URI'] = '/search';
        $this->assertTrue(page_cache_is_search_request());
        $_SERVER['REQUEST_URI'] = '/';
    }

    // ---------- post-content sanitizers ----------

    public function testStripEventHandlerAttributesRemovesAllHandlers(): void
    {
        $html = '<img src="x" onerror="alert(1)" ONCLICK="alert(2)" onpointerenter=alert(3) alt="ok">';
        $clean = strip_event_handler_attributes($html);
        $this->assertStringNotContainsString('onerror', strtolower($clean));
        $this->assertStringNotContainsString('onclick', strtolower($clean));
        $this->assertStringNotContainsString('onpointerenter', strtolower($clean));
        $this->assertStringContainsString('alt="ok"', $clean);
        $this->assertStringContainsString('src="x"', $clean);
    }

    public function testNeutralizeDangerousUrlSchemesBlocksScriptVectors(): void
    {
        $html = '<a href="javascript:alert(1)">x</a><a href="  VBSCRIPT:msg">y</a>'
            . '<a href="data:text/html,<script>alert(1)</script>">z</a>'
            . '<a href="https://example.com/?x=1">ok</a>';
        $clean = neutralize_dangerous_url_schemes($html);
        $this->assertStringNotContainsString('javascript:', strtolower($clean));
        $this->assertStringNotContainsString('vbscript:', strtolower($clean));
        $this->assertStringNotContainsString('data:text/html', strtolower($clean));
        $this->assertStringContainsString('https://example.com/?x=1', $clean);
    }

    public function testSanitizePostContentUsesFileCacheAndStripsHandlers(): void
    {
        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sanitize_test_' . uniqid() . DIRECTORY_SEPARATOR;
        $content = '&lt;img src=&quot;x&quot; onerror=&quot;alert(1)&quot;&gt;';

        $first = sanitize_post_content($content, $cacheDir);
        $this->assertStringNotContainsString('onerror', strtolower($first));
        $this->assertFileExists($cacheDir . '.htaccess');

        // Second call with an unwritable-blank dir still returns same output
        // via the in-memory map.
        $second = sanitize_post_content($content, $cacheDir);
        $this->assertSame($first, $second);
    }

    // ---------- scheduled-post throttle (S3) ----------

    public function testPublishDuePostsThrottledSkipsWhenStampFresh(): void
    {
        $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sched_test_' . uniqid() . DIRECTORY_SEPARATOR;
        mkdir($cacheDir, 0755, true);
        file_put_contents($cacheDir . 'scheduled-post-check.txt', 'x');

        $postDao = $this->createMock(PostDao::class);
        $postDao->expects($this->never())->method($this->anything());
        $configDao = $this->createMock(ConfigurationDao::class);
        $sanitizer = $this->createMock(Sanitize::class);

        $service = new ScheduledPostService($postDao, $configDao, $sanitizer);
        $this->assertSame(0, $service->publishDuePostsThrottled($cacheDir, 300));
    }

    // ---------- nginx deny rules (H2) ----------

    public function testNginxTemplateDeniesSensitivePaths(): void
    {
        $this->assertTrue(function_exists('read_nginx_config_template'));
        foreach (['yes', 'no'] as $mode) {
            $content = read_nginx_config_template($mode);
            $this->assertStringContainsString('install/migrate-', $content, "mode $mode must block migrate scripts");
            $this->assertStringContainsString('public/files/cache', $content, "mode $mode must block file caches");
            $this->assertStringContainsString('(php|phtml|phar', $content, "mode $mode must block uploadbed executables");
        }
    }

    // ---------- HTMLPurifier loader (REM-7) ----------

    public function testCallHtmlpurifierIsIdempotent(): void
    {
        $this->assertTrue(function_exists('call_htmlpurifier'));
        $this->assertNotFalse(call_htmlpurifier());
        $this->assertNotFalse(call_htmlpurifier());
        $this->assertTrue(defined('SCRIPTLOG_HTMLPURIFIER_LOADED'));
    }

    // ---------- theme-meta memo holder ----------

    public function testThemeMetaCacheHolderResets(): void
    {
        $holder = &theme_meta_cache_holder();
        $holder = ['x' => 1];
        reset_theme_meta_cache();
        $holder2 = &theme_meta_cache_holder();
        $this->assertNull($holder2);
    }
}
