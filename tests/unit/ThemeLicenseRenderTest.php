<?php

use PHPUnit\Framework\TestCase;

/**
 * ThemeLicenseRenderTest
 *
 * Unit tests for premium_theme_license_supports_render(): free themes
 * pass through untouched, active premium licenses render, and the
 * database-backed "blog" fallback is skipped when no test database is
 * available.
 *
 * @category Tests
 */
class ThemeLicenseRenderTest extends TestCase
{
    private const PREMIUM_INI_DIR = 'license-render-fixture';

    private static $premiumDirCreated = false;

    protected function setUp(): void
    {
        $this->ensurePremiumFixture();
    }

    protected function tearDown(): void
    {
        if (self::$premiumDirCreated !== false) {
            $this->removeDir(self::$premiumDirCreated);
            self::$premiumDirCreated = false;
        }
    }

    private function ensurePremiumFixture(): void
    {
        if (self::$premiumDirCreated !== false) {
            return;
        }

        $iniFile = APP_ROOT . APP_THEME . self::PREMIUM_INI_DIR . DIRECTORY_SEPARATOR . 'theme.ini';

        if (!file_exists(dirname($iniFile))) {
            mkdir(dirname($iniFile), 0755, true);
        }

        file_put_contents($iniFile, "[info]\ntheme_name = Premium Render Fixture\ntheme_designer = Test\ntheme_directory = " . self::PREMIUM_INI_DIR . "\ntheme_type = premium\n");

        self::$premiumDirCreated = dirname($iniFile);
    }

    private function removeDir($dir): void
    {
        if (is_dir($dir)) {
            $items = glob($dir . '/*');
            if ($items) {
                foreach ($items as $item) {
                    if (is_file($item)) {
                        unlink($item);
                    }
                }
            }
            rmdir($dir);
        }
    }

    public function testFreeThemeRowPassesThroughUnchanged(): void
    {
        $theme = [
            'theme_directory' => 'blog',
            'theme_type' => 'free',
            'theme_status' => 'Y',
        ];

        $result = premium_theme_license_supports_render($theme);

        $this->assertSame($theme, $result);
    }

    public function testActivePremiumThemePassesThroughUnchanged(): void
    {
        $theme = [
            'theme_directory' => 'premium-theme',
            'theme_type' => 'premium',
            'license_status' => 'active',
        ];

        $result = premium_theme_license_supports_render($theme);

        $this->assertSame($theme, $result);
    }

    public function testPremiumDetectedFromIniWithActiveLicensePassesThrough(): void
    {
        $theme = [
            'theme_directory' => self::PREMIUM_INI_DIR,
            'license_status' => 'active',
        ];

        $result = premium_theme_license_supports_render($theme);

        $this->assertSame($theme, $result);
    }

    public function testPremiumThemeWithFutureExpiryPassesThroughUnchanged(): void
    {
        $theme = [
            'theme_directory' => 'premium-theme',
            'theme_type' => 'premium',
            'license_status' => 'active',
            'license_expires_at' => date('Y-m-d H:i:s', time() + 3600),
        ];

        $result = premium_theme_license_supports_render($theme);

        $this->assertSame($theme, $result);
    }

    private function requiresTestDatabase(): bool
    {
        return class_exists('Registry')
            && Registry::isKeySet('dbc')
            && Registry::get('dbc') instanceof \PDO;
    }

    public function testExpiredPremiumLicenseFallsBackToBlogTheme(): void
    {
        if (!$this->requiresTestDatabase()) {
            $this->markTestSkipped('Database connection required for the blog fallback lookup');
        }

        $theme = [
            'theme_directory' => 'premium-theme',
            'theme_type' => 'premium',
            'license_status' => 'active',
            'license_expires_at' => date('Y-m-d H:i:s', time() - 3600),
        ];

        $result = premium_theme_license_supports_render($theme);

        $this->assertSame('blog', $result['theme_directory'] ?? null);
    }

    public function testInactivePremiumLicenseFallsBackToBlogTheme(): void
    {
        if (!$this->requiresTestDatabase()) {
            $this->markTestSkipped('Database connection required for the blog fallback lookup');
        }

        $theme = [
            'theme_directory' => 'premium-theme',
            'theme_type' => 'premium',
            'license_status' => 'inactive',
        ];

        $result = premium_theme_license_supports_render($theme);

        $this->assertSame('blog', $result['theme_directory'] ?? null);
    }
}