<?php

use PHPUnit\Framework\TestCase;

/**
 * Post Dropdown Utility Tests
 *
 * Covers the post_status_dropdown(), comment_status_dropdown(),
 * post_visibility_dropdown() and post_locale_dropdown() helpers extracted
 * from the former PostService::postStatusDropDown() / visibilityDropDown()
 * family of methods into src/lib/utility/post-dropdown.php.
 */
class PostDropdownFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/lib/utility/dropdown.php';
        require_once __DIR__ . '/../../src/lib/utility/post-dropdown.php';
    }

    public function testPostStatusDropdownRendersSelect(): void
    {
        $html = post_status_dropdown();

        $this->assertIsString($html);
        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('name="post_status"', $html);
        $this->assertStringContainsString('value="publish"', $html);
        $this->assertStringContainsString('value="draft"', $html);
    }

    public function testPostStatusDropdownMarksSelected(): void
    {
        $html = post_status_dropdown('draft');
        $this->assertStringContainsString('value="draft"  selected', $html);
        $this->assertStringNotContainsString('value="publish"  selected', $html);
    }

    public function testCommentStatusDropdownRendersSelect(): void
    {
        $html = comment_status_dropdown('closed');

        $this->assertIsString($html);
        $this->assertStringContainsString('name="comment_status"', $html);
        $this->assertStringContainsString('value="open"', $html);
        $this->assertStringContainsString('value="closed"  selected', $html);
    }

    public function testPostVisibilityDropdownRendersAllOptions(): void
    {
        $html = post_visibility_dropdown();

        $this->assertIsString($html);
        $this->assertStringContainsString('name="visibility"', $html);
        $this->assertStringContainsString('value="public"', $html);
        $this->assertStringContainsString('value="private"', $html);
        $this->assertStringContainsString('value="protected"', $html);
        $this->assertStringContainsString('checkVisibilitySelection()', $html);
        $this->assertStringContainsString('post_password', $html);
    }

    public function testPostVisibilityDropdownMarksSelected(): void
    {
        $html = post_visibility_dropdown('protected');
        $this->assertStringContainsString('value="protected" selected', $html);
    }

    public function testPostLocaleDropdownRendersLocales(): void
    {
        $html = post_locale_dropdown('en');

        $this->assertIsString($html);
        $this->assertStringContainsString('name="post_locale"', $html);
        $this->assertStringContainsString('value="en"  selected', $html);
        $this->assertStringContainsString('value="id"', $html);
        $this->assertStringContainsString('Indonesian', $html);
    }
}
