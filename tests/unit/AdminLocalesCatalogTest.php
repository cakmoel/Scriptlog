<?php
/**
 * AdminLocalesCatalogTest
 *
 * Unit tests for the DB-driven admin locale catalog helpers in
 * lib/utility/sanitize-locale.php:
 *
 *   - admin_locales_catalog()  — single source of truth for the supported
 *     admin content locales. Falls back to the 7 supported languages when no
 *     database connection is available, and is overridden by the active
 *     languages in tbl_languages when it is.
 *   - admin_locale_select()    — renders the <select> with native labels,
 *     escapes every attribute and label, and preserves a legacy stored locale
 *     that is not part of the catalog (BC safety).
 *
 * The fallback path and the DB-override + XSS-escaping path are exercised in
 * isolated PHP subprocesses (same pattern as ThemeSearchUrlTest) so the tests
 * stay deterministic even though the PHPUnit process may hold a live Registry
 * 'dbc' connection from an integration test.
 *
 * @category   UnitTests
 * @version    1.0.0
 * @since      September 2026
 * @license    MIT
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../lib/utility/dropdown.php';
require_once __DIR__ . '/../../lib/utility/sanitize-locale.php';

/**
 * @covers ::admin_locales_catalog
 * @covers ::admin_locale_select
 */
class AdminLocalesCatalogTest extends TestCase
{
    private const SANITIZE_LOCALE = __DIR__ . '/../../lib/utility/sanitize-locale.php';

    /**
     * Run a snippet in a fresh PHP subprocess and return its stdout.
     *
     * @param string $snippet PHP source to execute
     * @return string Raw stdout of the subprocess
     */
    private function runInSubprocess(string $snippet): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'alc_');
        file_put_contents($tmpFile, $snippet);

        $output = shell_exec('php ' . escapeshellarg($tmpFile) . ' 2>/dev/null');
        @unlink($tmpFile);

        $this->assertIsString($output, 'PHP subprocess produced no output');

        return $output;
    }

    private function includeGuard(): string
    {
        return <<<'PHP'
if (!defined('SCRIPTLOG')) {
    define('SCRIPTLOG', 'unit-test');
}
PHP;
    }

    public function testLocaleSelectRendersExpectedStructure(): void
    {
        $html = admin_locale_select('post_locale');

        $this->assertIsString($html);
        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('class="form-control select2"', $html);
        $this->assertStringContainsString('name="post_locale"', $html);
        $this->assertStringContainsString('id="post_locale"', $html);
        $this->assertStringContainsString('</select>', $html);
    }

    public function testLocaleSelectContainsSupportedNativeLabels(): void
    {
        $html = admin_locale_select('post_locale');

        $this->assertStringContainsString('English', $html);
        $this->assertStringContainsString('Español', $html);
        $this->assertStringContainsString('Français', $html);
        $this->assertStringContainsString('العربية', $html);
        $this->assertStringContainsString('中文', $html);
        $this->assertStringContainsString('Bahasa Indonesia', $html);
    }

    public function testLocaleSelectNeverOffersUnsupportedLocales(): void
    {
        $catalog = admin_locales_catalog();

        foreach (['de', 'it', 'ja', 'ko', 'hi', 'ms', 'tr', 'nl', 'pl', 'vi', 'th', 'he'] as $unsupported) {
            $this->assertArrayNotHasKey(
                $unsupported,
                $catalog,
                'Unsupported locale "' . $unsupported . '" must never be offered'
            );
        }
    }

    public function testLocaleSelectMarksSelectedOption(): void
    {
        $html = admin_locale_select('post_locale', 'es');

        $this->assertStringContainsString('value="es"', $html);
        $this->assertStringContainsString('selected', $html);
    }

    public function testLocaleSelectPreservesLegacySelectionOutsideCatalog(): void
    {
        $html = admin_locale_select('post_locale', 'ja');

        $this->assertStringContainsString('value="ja"', $html);
        $this->assertStringContainsString('selected', $html);
        $this->assertStringContainsString('>JA</option>', $html);
    }

    public function testLocaleSelectEscapesNameAttribute(): void
    {
        $html = admin_locale_select('name" onmouseover="alert(1)');

        $this->assertStringNotContainsString('" onmouseover="', $html);
        $this->assertStringContainsString('name="name&quot; onmouseover=&quot;alert(1)"', $html);
    }

    public function testSupportedCodesArePresent(): void
    {
        $catalog = admin_locales_catalog();

        $this->assertArrayHasKey('en', $catalog);
        $this->assertArrayHasKey('ar', $catalog);
        $this->assertArrayHasKey('zh', $catalog);
        $this->assertArrayHasKey('fr', $catalog);
        $this->assertArrayHasKey('es', $catalog);
        $this->assertArrayHasKey('id', $catalog);
    }

    /**
     * Without a database connection the catalog must fall back to the exact
     * seven supported languages with their native names.
     */
    public function testFallbackCatalogWithoutDatabase(): void
    {
        $snippet = <<<'PHP'
<?php
REQUIRE_GUARD

require_once TARGET;

echo json_encode(admin_locales_catalog());
PHP;

        $snippet = str_replace(
            ['REQUIRE_GUARD', 'TARGET'],
            [$this->includeGuard(), var_export(realpath(self::SANITIZE_LOCALE), true)],
            $snippet
        );

        $catalog = json_decode($this->runInSubprocess($snippet), true);

        $this->assertSame(
            [
                'en' => 'English',
                'ar' => 'العربية',
                'zh' => '中文',
                'fr' => 'Français',
                'ru' => 'Русский',
                'es' => 'Español',
                'id' => 'Bahasa Indonesia',
            ],
            $catalog
        );
    }

    /**
     * When a LanguageDao is available the catalog is overridden by the active
     * languages, and admin_locale_select() must escape their labels (XSS).
     */
    public function testDatabaseDrivenCatalogOverridesFallbackAndEscapesLabels(): void
    {
        $snippet = <<<'PHP'
<?php
REQUIRE_GUARD

require_once TARGET;

class LanguageDao
{
    public function findActiveLanguages(): array
    {
        return [
            ['lang_code' => 'en', 'lang_name' => 'English', 'lang_native' => 'English'],
            ['lang_code' => 'de', 'lang_name' => 'German', 'lang_native' => 'Deutsch <script>alert(1)</script>'],
        ];
    }
}

$catalog = admin_locales_catalog();
echo json_encode(
    [
        'catalog' => $catalog,
        'html'    => admin_locale_select('post_locale', 'de'),
    ]
);
PHP;

        $snippet = str_replace(
            ['REQUIRE_GUARD', 'TARGET'],
            [$this->includeGuard(), var_export(realpath(self::SANITIZE_LOCALE), true)],
            $snippet
        );

        $result = json_decode($this->runInSubprocess($snippet), true);

        $this->assertArrayHasKey('de', $result['catalog'], 'Stubbed DB languages must drive the catalog');
        $this->assertArrayNotHasKey('ru', $result['catalog'], 'Fallback must not leak through when DB is available');

        $html = $result['html'];
        $this->assertStringContainsString('value="de" selected', $html);
        $this->assertStringContainsString('Deutsch', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html, 'Labels must be escaped at render time');
    }
}