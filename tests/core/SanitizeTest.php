<?php
/**
 * Sanitize Tests
 *
 * Phase 3.5: Core Classes - Sanitize (8 tests)
 * Tests for input sanitization, XSS prevention, type filtering
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class SanitizeTest extends TestCase
{
    private $sanitize;

    protected function setUp(): void
    {
        $this->sanitize = new Sanitize();
    }

    public function testSanitasiSqlReturnsInteger(): void
    {
        $result = $this->sanitize->sanitasi('42', 'sql');
        $this->assertIsInt($result);
        $this->assertEquals(42, $result);
    }

    public function testSanitasiSqlStripsSpecialChars(): void
    {
        $result = $this->sanitize->sanitasi('12; DROP TABLE users', 'sql');
        $this->assertIsInt($result);
        $this->assertEquals(12, $result);
    }

    public function testSanitasiXssRemovesAngleBrackets(): void
    {
        $result = $this->sanitize->sanitasi('<script>alert("xss")</script>', 'xss');
        $this->assertIsString($result);
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    public function testSanitasiXssReturnsCleanString(): void
    {
        $result = $this->sanitize->sanitasi('Hello World', 'xss');
        $this->assertIsString($result);
    }

    public function testSanitasiUriRemovesDangerousChars(): void
    {
        $result = $this->sanitize->sanitasi('/path?query=value&xss=<script>', 'uri');
        $this->assertIsString($result);
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    public function testMildSanitizerReturnsString(): void
    {
        $result = Sanitize::mildSanitizer('test string');
        $this->assertIsString($result);
    }

    public function testSevereSanitizerReturnsString(): void
    {
        $result = Sanitize::severeSanitizer('test string');
        $this->assertIsString($result);
    }

    public function testSanitizeAliasMethod(): void
    {
        $result = $this->sanitize->sanitize('42', 'sql');
        $this->assertEquals(42, $result);
    }
}
