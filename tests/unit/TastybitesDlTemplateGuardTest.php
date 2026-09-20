<?php
/**
 * TastybitesDlTemplateGuardTest
 *
 * Guard and code-quality tests for public/themes/tastybites/download.php.
 *
 * Validates: Requirements 1.1, 4.6, 9.9
 *
 * @category   UnitTests
 * @version    1.0.0
 * @since      2026
 * @license    MIT
 */

use PHPUnit\Framework\TestCase;

class TastybitesDlTemplateGuardTest extends TestCase
{
    /** Absolute path to the file under test. */
    private string $filePath;

    /** Source code of the file under test (loaded once per test class). */
    private string $source;

    protected function setUp(): void
    {
        $this->filePath = realpath(__DIR__ . '/../../public/themes/tastybites/download.php');
        $this->assertNotFalse(
            $this->filePath,
            'download.php was not found at the expected path.'
        );
        $this->source = file_get_contents($this->filePath);
        $this->assertNotFalse(
            $this->source,
            'Failed to read download.php.'
        );
    }

    /**
     * Requirement 1.1 — Security guard present.
     *
     * The Tastybites_Download_Template SHALL begin with
     * `defined('SCRIPTLOG') || die` as its first executable statement.
     */
    public function testScriptlogGuardPresent(): void
    {
        $this->assertStringContainsString(
            "defined('SCRIPTLOG') || die",
            $this->source,
            "download.php must contain the SCRIPTLOG guard: defined('SCRIPTLOG') || die"
        );
    }

    /**
     * Requirement 4.6 — No direct class instantiation.
     *
     * The Tastybites_Download_Template SHALL NOT instantiate DownloadController
     * directly; it must delegate to get_download_page_data() instead.
     */
    public function testNoDirectClassInstantiation(): void
    {
        $this->assertStringNotContainsString(
            'new DownloadController',
            $this->source,
            'download.php must NOT contain "new DownloadController" — use get_download_page_data() instead.'
        );
    }

    /**
     * Requirement 9.9 — No alert() calls in JavaScript.
     *
     * The Tastybites_Download_Template SHALL NOT call alert() or window.alert()
     * anywhere in the copy-to-clipboard JavaScript.
     */
    public function testNoAlertCall(): void
    {
        $this->assertStringNotContainsString(
            'alert(',
            $this->source,
            'download.php must NOT contain any alert( call.'
        );

        $this->assertStringNotContainsString(
            'window.alert(',
            $this->source,
            'download.php must NOT contain any window.alert( call.'
        );
    }
}
