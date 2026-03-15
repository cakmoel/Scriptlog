<?php
/**
 * URL Validation Test
 * 
 * Tests for URL validation utility functions
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class UrlValidationTest extends TestCase
{
    public function testUrlValidationWithValidUrl(): void
    {
        $result = url_validation('https://www.example.com');
        $this->assertTrue($result);
    }
    
    public function testUrlValidationWithHttpUrl(): void
    {
        $result = url_validation('http://www.example.com');
        $this->assertTrue($result);
    }
    
    public function testUrlValidationWithInvalidUrl(): void
    {
        $result = url_validation('not-a-url');
        $this->assertFalse($result);
    }
    
    public function testUrlValidationWithEmptyString(): void
    {
        $result = url_validation('');
        $this->assertFalse($result);
    }
    
    public function testIsValidDomainWithValidDomain(): void
    {
        $result = is_valid_domain('example.com');
        $this->assertTrue($result);
    }
    
    public function testIsValidDomainWithWwwDomain(): void
    {
        $result = is_valid_domain('www.example.com');
        $this->assertTrue($result);
    }
    
    public function testIsValidDomainWithInvalidDomain(): void
    {
        $result = is_valid_domain('not a domain');
        $this->assertFalse($result);
    }
    
    public function testIsValidDomainWithEmptyString(): void
    {
        $result = is_valid_domain('');
        $this->assertFalse($result);
    }
    
    public function testAddHttpToUrl(): void
    {
        $result = add_http('example.com');
        $this->assertEquals('http://example.com', $result);
    }
    
    public function testAddHttpToUrlWithHttp(): void
    {
        $result = add_http('http://example.com');
        $this->assertEquals('http://example.com', $result);
    }
    
    public function testAddHttpToUrlWithHttps(): void
    {
        $result = add_http('https://example.com');
        $this->assertEquals('https://example.com', $result);
    }
}
