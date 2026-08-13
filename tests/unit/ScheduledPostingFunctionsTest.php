<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

/**
 * ScheduledPostingFunctionsTest
 *
 * Unit tests for the scheduled-posting helpers introduced with the Writing
 * (scheduled posting) feature:
 *
 *   - post_status_dropdown($selected, $includeScheduled)
 *   - post_status_label($status)
 *   - scheduled_post_enabled()
 *   - validate_date() accepting full datetime input
 *   - admin_query() exposing the option-writing admin page
 *
 * @category Tests
 * @version  1.0
 */
class ScheduledPostingFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('post_status_dropdown')) {
            require_once __DIR__ . '/../../src/lib/utility/dropdown.php';
            require_once __DIR__ . '/../../src/lib/utility/post-dropdown.php';
        }
        if (!function_exists('validate_date')) {
            require_once __DIR__ . '/../../src/lib/utility/validate-date.php';
        }
    }

    // -----------------------------------------------------------------------
    // post_status_dropdown() with the scheduled option
    // -----------------------------------------------------------------------

    public function testDropdownExcludesScheduledByDefault(): void
    {
        $html = post_status_dropdown();
        $this->assertStringNotContainsString('value="scheduled"', $html);
        $this->assertStringContainsString('value="publish"', $html);
        $this->assertStringContainsString('value="draft"', $html);
    }

    public function testDropdownIncludesScheduledWhenRequested(): void
    {
        $html = post_status_dropdown('', true);
        $this->assertStringContainsString('value="scheduled"', $html);
    }

    public function testDropdownMarksScheduledSelected(): void
    {
        $html = post_status_dropdown('scheduled', true);
        $this->assertMatchesRegularExpression('/value="scheduled"[^>]*selected/', $html);
    }

    // -----------------------------------------------------------------------
    // post_status_label()
    // -----------------------------------------------------------------------

    public function testStatusLabelMapsKnownStatuses(): void
    {
        $this->assertSame('Publish', post_status_label('publish'));
        $this->assertSame('Draft', post_status_label('draft'));
        $this->assertSame('Scheduled', post_status_label('scheduled'));
    }

    public function testStatusLabelFallsBackToHumanizedValue(): void
    {
        $this->assertSame('Trash', post_status_label('trash'));
    }

    // -----------------------------------------------------------------------
    // scheduled_post_enabled()
    // -----------------------------------------------------------------------

    public function testScheduledPostEnabledReturnsBoolean(): void
    {
        $this->assertIsBool(scheduled_post_enabled());
    }

    public function testScheduledPostEnabledDefaultsTrueWhenClassesMissing(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/utility/writing-settings.php');
        $this->assertStringContainsString('return true;', $source);
        $this->assertStringContainsString('writing_scheduled_post_enabled', $source);
    }

    // -----------------------------------------------------------------------
    // validate_date() with datetime input
    // -----------------------------------------------------------------------

    public function testValidateDateAcceptsPlainDate(): void
    {
        $this->assertTrue(validate_date('2026-08-13'));
    }

    public function testValidateDateAcceptsFullDatetime(): void
    {
        $this->assertTrue(validate_date('2026-08-13 10:30:00'));
    }

    public function testValidateDateRejectsInvalidDate(): void
    {
        $this->assertFalse(validate_date('2026-13-45'));
    }

    public function testValidateDateRejectsGarbageInput(): void
    {
        $this->assertFalse(validate_date('not-a-date'));
    }

    public function testValidateDateRejectsInvalidTime(): void
    {
        $this->assertFalse(validate_date('2026-08-13 25:00:00'));
    }

    // -----------------------------------------------------------------------
    // admin_query() option-writing mapping
    // -----------------------------------------------------------------------

    public function testAdminQueryMapsOptionWriting(): void
    {
        $query = admin_query();
        $this->assertIsArray($query);
        $this->assertArrayHasKey('option-writing', $query);
        $this->assertSame('option-writing.php', $query['option-writing']);
    }
}
