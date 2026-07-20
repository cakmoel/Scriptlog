<?php

use PHPUnit\Framework\TestCase;

class SafeHtmlFunctionsTest extends TestCase
{
    protected function setUp(): void
    {
        if (!function_exists('safe_html')) {
            require_once __DIR__ . '/../../src/lib/utility/safe-html.php';
        }
    }

    public function testSafeHtmlFunctionExists(): void
    {
        $this->assertTrue(function_exists('safe_html'));
    }

    public function testSafeHtmlStripsTags(): void
    {
        $result = safe_html('<b>bold</b>');
        $this->assertEquals('bold', $result);
    }

    public function testSafeHtmlEncodesSpecialChars(): void
    {
        $result = safe_html('Hello & "world"');
        $this->assertEquals('Hello &amp; &quot;world&quot;', $result);
    }

    public function testSafeHtmlTrimsWhitespace(): void
    {
        $result = safe_html('  hello  ');
        $this->assertEquals('hello', $result);
    }

    public function testSafeHtmlHandlesNullData(): void
    {
        $result = safe_html(null);
        $this->assertEquals('', $result);
    }

    public function testSafeHtmlHandlesEmptyString(): void
    {
        $result = safe_html('');
        $this->assertEquals('', $result);
    }

    public function testSafeHtmlWithScriptTag(): void
    {
        $result = safe_html("<script>alert('xss')</script>");
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function testSafeFilterHtmlFunctionExists(): void
    {
        $this->assertTrue(function_exists('safe_filter_html'));
    }

    public function testSafeHtmlRemovesNewlines(): void
    {
        $result = safe_html("hello\nworld");
        $this->assertStringContainsString('hello', $result);
        $this->assertStringContainsString('world', $result);
    }
}
