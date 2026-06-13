<?php
/**
 * Download Page Data Function Test
 * 
 * Tests for get_download_page_data() function in theme functions.php
 * 
 * @version 1.0
 * @since 1.0
 */

use PHPUnit\Framework\TestCase;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

// Define SCRIPTLOG constant to pass the check in functions.php
if (!defined('SCRIPTLOG')) {
    define('SCRIPTLOG', hash_hmac('sha256', 'test', 'test'));
}

require_once __DIR__ . '/../../public/themes/blog/functions.php';

class DownloadPageDataTest extends TestCase
{
    protected $dbAvailable = false;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Check if database connection is available
        if (class_exists('Registry')) {
            try {
                Registry::get('dbc');
                $this->dbAvailable = true;
            } catch (Exception $e) {
                $this->dbAvailable = false;
            }
        }
    }

    /**
     * Test that get_download_page_data function exists
     */
    public function testFunctionExists()
    {
        $this->assertTrue(function_exists('get_download_page_data'));
    }

    /**
     * Test with empty identifier
     */
    public function testEmptyIdentifier()
    {
        $result = get_download_page_data('');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Invalid download identifier', $result['error']);
    }

    /**
     * Test with null identifier
     */
    public function testNullIdentifier()
    {
        $result = get_download_page_data(null);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test function returns array when database is not available
     */
    public function testReturnsArrayWithoutDb()
    {
        if ($this->dbAvailable) {
            $this->markTestSkipped('Database is available, skipping this test');
        }
        
        // Test with invalid identifier (not empty, but won't match)
        $result = get_download_page_data('invalid-uuid-that-does-not-exist');
        $this->assertIsArray($result);
    }

    /**
     * Test with valid identifier format when database is not available
     */
    public function testValidUuidFormatWithoutDb()
    {
        if ($this->dbAvailable) {
            $this->markTestSkipped('Database is available, skipping this test');
        }
        
        $uuid = '12345678-1234-1234-1234-123456789012';
        $result = get_download_page_data($uuid);
        $this->assertIsArray($result);
    }
}
