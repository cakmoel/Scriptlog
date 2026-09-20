<?php
/**
 * Post Dropdown Helper Unit Tests
 *
 * Tests for lib/utility/post-dropdown.php view helper functions.
 *
 * @category   UnitTests
 * @version    1.0.0
 * @since     July 2026
 * @license   MIT
 */

use PHPUnit\Framework\TestCase;

if (!defined('SCRIPTLOG')) {
    define('SCRIPTLOG', 'unit-test');
}

require_once __DIR__ . '/../../lib/utility/dropdown.php';
require_once __DIR__ . '/../../lib/utility/sanitize-locale.php';
require_once __DIR__ . '/../../lib/utility/post-dropdown.php';

/**
 * @covers ::post_status_dropdown
 * @covers ::post_status_label
 * @covers ::comment_status_dropdown
 * @covers ::post_visibility_dropdown
 * @covers ::post_locale_dropdown
 */
class PostDropdownTest extends TestCase
{
    public function testPostStatusDropdown(): void
    {
        $html = post_status_dropdown();

        $this->assertIsString($html);
        $this->assertStringContainsString('name="post_status"', $html);
        $this->assertStringContainsString('value="publish"', $html);
        $this->assertStringContainsString('value="draft"', $html);
        $this->assertStringNotContainsString('value="scheduled"', $html);
    }

    public function testPostStatusDropdownWithScheduled(): void
    {
        $html = post_status_dropdown('', true);

        $this->assertStringContainsString('value="scheduled"', $html);
        $this->assertStringContainsString('Scheduled', $html);
    }

    public function testPostStatusDropdownSelected(): void
    {
        $html = post_status_dropdown('draft');

        $this->assertStringContainsString('selected', $html);
        $this->assertStringContainsString('value="draft"', $html);
    }

    public function testPostStatusLabel(): void
    {
        $this->assertSame('Publish', post_status_label('publish'));
        $this->assertSame('Draft', post_status_label('draft'));
        $this->assertSame('Scheduled', post_status_label('scheduled'));
        $this->assertSame('Trash', post_status_label('trash'));
    }

    public function testCommentStatusDropdown(): void
    {
        $html = comment_status_dropdown();

        $this->assertIsString($html);
        $this->assertStringContainsString('name="comment_status"', $html);
        $this->assertStringContainsString('value="open"', $html);
        $this->assertStringContainsString('value="closed"', $html);
    }

    public function testPostVisibilityDropdown(): void
    {
        $html = post_visibility_dropdown();

        $this->assertIsString($html);
        $this->assertStringContainsString('name="visibility"', $html);
        $this->assertStringContainsString('value="public"', $html);
        $this->assertStringContainsString('value="private"', $html);
        $this->assertStringContainsString('value="protected"', $html);
        $this->assertStringContainsString('name="post_password"', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function testPostVisibilityDropdownSelected(): void
    {
        $html = post_visibility_dropdown('protected');

        $this->assertStringContainsString('selected', $html);
        $this->assertStringContainsString('value="protected"', $html);
    }

    public function testPostLocaleDropdown(): void
    {
        $html = post_locale_dropdown();

        $this->assertIsString($html);
        $this->assertStringContainsString('name="post_locale"', $html);
        $this->assertStringContainsString('English', $html);
        $this->assertStringContainsString('Bahasa Indonesia', $html);
        $this->assertStringContainsString('Español', $html);
    }

    public function testPostLocaleDropdownSelected(): void
    {
        $html = post_locale_dropdown('es');

        $this->assertStringContainsString('selected', $html);
        $this->assertStringContainsString('value="es"', $html);
    }
}
