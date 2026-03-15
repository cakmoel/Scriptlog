<?php
/**
 * String and Date Functions Test
 * 
 * Tests for string and date utility functions
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class StringDateTest extends TestCase
{
    public function testDateConversionWithTimestamp(): void
    {
        $result = date_conversion('2023-01-15 10:30:00');
        $this->assertIsString($result);
    }
    
    public function testConvertToParagraphWithText(): void
    {
        $input = "Line 1\nLine 2\nLine 3";
        $result = convert_to_paragraph($input);
        $this->assertStringContainsString('<p>', $result);
    }
    
    public function testDropdownWithOptions(): void
    {
        $options = ['Option 1', 'Option 2', 'Option 3'];
        $result = dropdown('test_select', $options, 'Option 1');
        $this->assertStringContainsString('<select', $result);
        $this->assertStringContainsString('test_select', $result);
    }
}
