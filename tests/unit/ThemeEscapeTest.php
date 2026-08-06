<?php

use PHPUnit\Framework\TestCase;

/**
 * Theme Escape Helper Tests
 *
 * Covers theme_escape_html() - the single escaping boundary for theme
 * output added in src/lib/utility/theme-escape.php.
 */
class ThemeEscapeTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/lib/utility/theme-escape.php';
    }

    public function testEscapesHtmlSpecialChars(): void
    {
        $this->assertSame('&lt;b&gt;bold&lt;/b&gt;', theme_escape_html('<b>bold</b>'));
    }

    public function testEscapesDoubleAndSingleQuotes(): void
    {
        $this->assertSame('&quot;quoted&quot;', theme_escape_html('"quoted"'));
        $this->assertSame('&#039;single&#039;', theme_escape_html("'single'"));
    }

    public function testEscapesAmpersands(): void
    {
        $this->assertSame('Tom &amp; Jerry', theme_escape_html('Tom & Jerry'));
    }

    public function testLeavesPlainTextUntouched(): void
    {
        $this->assertSame('Hello, world! 123', theme_escape_html('Hello, world! 123'));
    }

    public function testSubstitutesInvalidUtf8(): void
    {
        $invalid = "\xC3\x28"; // malformed UTF-8 sequence
        $result = theme_escape_html($invalid);
        $this->assertIsString($result);
    }
}
