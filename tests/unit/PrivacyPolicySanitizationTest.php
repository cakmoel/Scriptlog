<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the privacy-policy sanitization pipeline (R5).
 *
 * The privacy policy content is rich HTML (h2/p/ul/li/strong) so it must be
 * sanitized on save with HTMLPurifier (purify_dirty_html) and again on the
 * public page with an htmLawed whitelist, while the admin textarea escapes
 * the stored markup via escape_html(). These tests assert that stored XSS
 * payloads are neutralised at every stage and that legitimate markup is kept.
 */
class PrivacyPolicySanitizationTest extends TestCase
{
    /**
     * XSS payload covering scripts, event handlers and javascript: URLs.
     *
     * @var string
     */
    private $xssPayload = '<h2>Hello</h2><ul><li>item</li></ul><p>Text <script>alert(1)</script><img src="x" onerror="alert(2)"></p><a href="javascript:alert(3)">link</a><strong>bold</strong>';

    /**
     * Load the sanitization helpers used by the pipeline.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!defined('SCRIPTLOG')) {
            define('SCRIPTLOG', 'test');
        }

        if (function_exists('call_htmlpurifier')) {
            call_htmlpurifier();
        }

        $this->assertTrue(function_exists('purify_dirty_html'));
        $this->assertTrue(function_exists('htmLawed'));
        $this->assertTrue(function_exists('escape_html'));
    }

    /**
     * purify_dirty_html() (HTMLPurifier, used on save) must strip scripts,
     * event handlers and javascript: URLs while keeping safe markup.
     *
     * @return void
     */
    public function testPurifyDirtyHtmlStripsStoredXssOnSave(): void
    {
        $clean = purify_dirty_html($this->xssPayload);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('javascript:', $clean);
        $this->assertStringContainsString('<h2>', $clean);
        $this->assertStringContainsString('<strong>', $clean);
    }

    /**
     * htmLawed() with the whitelist used on the public page must neutralise
     * scripts and event handlers while preserving headings, lists and
     * emphasis used by the default policy content. (javascript: hrefs are
     * already removed at save time by HTMLPurifier.)
     *
     * @return void
     */
    public function testHtmLawedWhitelistStripsXssOnPublicRender(): void
    {
        $clean = htmLawed($this->xssPayload, array(
            'elements' => 'h2,h3,h4,p,ul,ol,li,strong,em,a,br,blockquote,code,pre',
            'deny_attribute' => 'style,onclick,onerror,onload,onmouseover,onfocus,onblur,onchange,onsubmit,onkeydown,onkeyup,onkeypress',
            'keep_bad' => 0
        ));

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('<img', $clean);
        $this->assertStringContainsString('<h2>', $clean);
        $this->assertStringContainsString('<ul>', $clean);
        $this->assertStringContainsString('<strong>', $clean);
    }

    /**
     * escape_html() used in the admin textarea must encode the stored HTML so
     * a </textarea> payload cannot break out of the editing box.
     *
     * @return void
     */
    public function testEscapeHtmlNeutralisesTextareaBreakout(): void
    {
        $payload = '</textarea><script>alert(1)</script><h2>Title</h2>';
        $escaped = escape_html($payload, 'html');

        $this->assertStringNotContainsString('</textarea>', $escaped);
        $this->assertStringNotContainsString('<script', $escaped);
        $this->assertStringContainsString('&lt;/textarea&gt;', $escaped);
        $this->assertStringContainsString('&lt;h2&gt;', $escaped);
    }

    /**
     * The full pipeline — save, then render — leaves no executable script in
     * the final public output.
     *
     * @return void
     */
    public function testSaveThenRenderPipelineIsXssFree(): void
    {
        $stored = purify_dirty_html($this->xssPayload);
        $rendered = htmLawed($stored, array(
            'elements' => 'h2,h3,h4,p,ul,ol,li,strong,em,a,br,blockquote,code,pre',
            'deny_attribute' => 'style,onclick,onerror,onload,onmouseover,onfocus,onblur,onchange,onsubmit,onkeydown,onkeyup,onkeypress',
            'keep_bad' => 0
        ));

        $this->assertStringNotContainsString('<script', $rendered);
        $this->assertStringNotContainsString('onerror', $rendered);
        $this->assertStringNotContainsString('javascript:', $rendered);
        $this->assertStringContainsString('<h2>Hello</h2>', $rendered);
    }
}
