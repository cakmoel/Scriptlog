<?php

use PHPUnit\Framework\TestCase;

/**
 * SanitizePostContentTest
 *
 * Unit tests for sanitize_post_content() and post_content_deny_attributes()
 * functions extracted from ProtectedPostService into a standalone utility.
 *
 * @category Unit Test
 * @author Blogware Team
 * @license MIT
 */
class SanitizePostContentTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/lib/utility/sanitize-post-content.php';
    }

    public function testFunctionsExist(): void
    {
        $this->assertTrue(function_exists('sanitize_post_content'));
        $this->assertTrue(function_exists('post_content_deny_attributes'));
    }

    public function testDenyAttributesReturnsArray(): void
    {
        $attrs = post_content_deny_attributes();
        $this->assertIsArray($attrs);
        $this->assertNotEmpty($attrs);
    }

    public function testDenyAttributesContainsStyle(): void
    {
        $attrs = post_content_deny_attributes();
        $this->assertContains('style', $attrs);
    }

    public function testDenyAttributesContainsEventHandlers(): void
    {
        $attrs = post_content_deny_attributes();

        $eventHandlers = [
            'onclick', 'onerror', 'onload', 'onmouseover',
            'onfocus', 'onblur', 'onchange', 'onsubmit',
            'onkeydown', 'onkeyup', 'onkeypress',
        ];

        foreach ($eventHandlers as $handler) {
            $this->assertContains($handler, $attrs, "Deny attributes should include $handler");
        }
    }

    public function testDenyAttributesHas12Entries(): void
    {
        $attrs = post_content_deny_attributes();
        $this->assertCount(12, $attrs);
    }

    public function testSanitizeDecodesHtmlEntitiesDoubleEncoded(): void
    {
        $doubleEncoded = '&amp;lt;p&amp;gt;Hello&amp;lt;/p&amp;gt;';
        $result = sanitize_post_content($doubleEncoded);

        $this->assertIsString($result);
        $this->assertStringNotContainsString('&amp;', $result);
    }

    public function testSanitizeStripsInlineStyleAttributes(): void
    {
        $content = '<p style="color:red;">Hello</p>';
        $result = sanitize_post_content($content);

        $this->assertStringNotContainsString('style=', $result);
    }

    public function testSanitizeStripsStyleAttributeWithoutQuotes(): void
    {
        $content = '<p style=color:red>Hello</p>';
        $result = sanitize_post_content($content);

        $this->assertStringNotContainsString('style=', $result);
    }

    public function testSanitizeReturnsStringForEmptyInput(): void
    {
        $result = sanitize_post_content('');
        $this->assertIsString($result);
    }

    public function testSanitizeReturnsStringForPlainTextInput(): void
    {
        $result = sanitize_post_content('Just plain text');
        $this->assertIsString($result);
        $this->assertStringContainsString('Just plain text', $result);
    }

    public function testSanitizeHandlesNullByteInContent(): void
    {
        $content = "Hello\x00World";
        $result = sanitize_post_content($content);
        $this->assertIsString($result);
    }

    public function testSanitizePreservesSafeHtml(): void
    {
        $content = '<p>Hello <strong>World</strong></p>';
        $result = sanitize_post_content($content);

        $this->assertIsString($result);
        $this->assertStringContainsString('Hello', $result);
    }

    public function testSanitizeWorksWithoutHtmLawed(): void
    {
        if (function_exists('htmLawed')) {
            $this->markTestSkipped('htmLawed is available; testing fallback path');
        }

        $content = '<p style="color:red;">Test</p>';
        $result = sanitize_post_content($content);

        $this->assertStringNotContainsString('style=', $result);
        $this->assertStringContainsString('Test', $result);
    }
}
