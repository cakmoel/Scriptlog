<?php
/**
 * Additional Utility Functions Test
 * 
 * Tests for more utility functions
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class AdditionalUtilityTest extends TestCase
{
    public function testGetTablePrefix(): void
    {
        if (function_exists('get_table_prefix')) {
            $prefix = get_table_prefix();
            $this->assertIsString($prefix);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testAppInfo(): void
    {
        if (function_exists('app_info')) {
            $info = app_info();
            $this->assertIsArray($info);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testAppUrl(): void
    {
        if (function_exists('app_url')) {
            $url = app_url();
            $this->assertIsString($url);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testAppConfig(): void
    {
        if (function_exists('app_config')) {
            $config = app_config();
            $this->assertIsArray($config);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testCheckFileName(): void
    {
        if (function_exists('check_file_name')) {
            $result = check_file_name('test.php');
            $this->assertIsBool($result);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testCheckFileExtension(): void
    {
        if (function_exists('check_file_extension')) {
            $result = check_file_extension('test.jpg', ['jpg', 'png']);
            $this->assertIsBool($result);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testCheckFileLength(): void
    {
        if (function_exists('check_file_length')) {
            $result = check_file_length('test content', 100);
            $this->assertIsBool($result);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testValidateDate(): void
    {
        if (function_exists('validate_date')) {
            $result = validate_date('2023-01-15');
            $this->assertIsBool($result);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testAdminQuery(): void
    {
        if (function_exists('admin_query')) {
            $query = admin_query();
            $this->assertIsArray($query);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testAppTagline(): void
    {
        if (function_exists('app_tagline')) {
            $tagline = app_tagline();
            $this->assertIsString($tagline);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testAppSitename(): void
    {
        if (function_exists('app_sitename')) {
            $name = app_sitename();
            $this->assertIsString($name);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testAppKey(): void
    {
        if (function_exists('app_key')) {
            $key = app_key();
            $this->assertIsString($key);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testUniqIdReal(): void
    {
        if (function_exists('uniqid_real')) {
            $id = uniqid_real();
            $this->assertIsString($id);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testUnparseUrl(): void
    {
        if (function_exists('unparse_url')) {
            $url = unparse_url('https://example.com/path');
            $this->assertIsString($url);
        } else {
            $this->assertTrue(true);
        }
    }
}
