<?php
/**
 * Sanitization and Security Functions Test
 * 
 * Tests for sanitization utility functions
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class SanitizationTest extends TestCase
{
    public function testRemoveXssWithNormalString(): void
    {
        $input = '<script>alert("xss")</script>Hello';
        $result = remove_xss($input);
        $this->assertStringNotContainsString('<script>', $result);
    }
    
    public function testRemoveXssWithHtmlEntities(): void
    {
        $input = '<div>Hello World</div>';
        $result = remove_xss($input);
        $this->assertIsString($result);
    }
    
    public function testRemoveXssWithEmptyString(): void
    {
        $result = remove_xss('');
        $this->assertEquals('', $result);
    }
    
    public function testEscapeHtmlWithNormalString(): void
    {
        $input = '<div>Hello</div>';
        $result = escape_html($input);
        $this->assertStringContainsString('&lt;', $result);
    }
    
    public function testEscapeHtmlWithEmptyString(): void
    {
        $result = escape_html('');
        $this->assertEquals('', $result);
    }
    
    public function testSanitizeEmailWithValidEmail(): void
    {
        $result = sanitize_email('test@example.com');
        $this->assertEquals('test@example.com', $result);
    }
    
    public function testRemoveAccentsWithAccentedChars(): void
    {
        $result = remove_accents('café');
        $this->assertEquals('cafe', $result);
    }
    
    public function testRemoveAccentsWithNormalChars(): void
    {
        $result = remove_accents('hello');
        $this->assertEquals('hello', $result);
    }
}
