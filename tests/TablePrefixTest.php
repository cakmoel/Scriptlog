<?php
/**
 * Table Prefix Test
 * 
 * Tests the table prefix functionality for installation
 * 
 * @category Tests
 * @author Test Suite
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/lib/common.php';

require_once __DIR__ . '/../src/install/include/dbtable.php';

function generate_table_prefix($length = 6)
{
    $characters = 'abcdefghijklmnopqrstuvwxyz';
    $prefix = '';
    for ($i = 0; $i < $length; $i++) {
        $prefix .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $prefix . '_';
}

class TablePrefixTest extends TestCase
{
    public function testGenerateTablePrefix()
    {
        $prefix = generate_table_prefix(6);
        
        $this->assertNotEmpty($prefix);
        $this->assertEquals(7, strlen($prefix));
        $this->assertEquals('_', substr($prefix, -1));
        $this->assertTrue(ctype_lower(substr($prefix, 0, -1)));
    }

    public function testTablePrefixFormat()
    {
        $prefix = generate_table_prefix(4);
        
        $this->assertMatchesRegularExpression('/^[a-z]{4}_$/', $prefix);
    }

    public function testTablePrefixUnique()
    {
        $prefixes = [];
        for ($i = 0; $i < 10; $i++) {
            $prefixes[] = generate_table_prefix(4);
        }
        
        $uniquePrefixes = array_unique($prefixes);
        $this->assertCount(10, $uniquePrefixes);
    }

    public function testGetTableDefinitions()
    {
        $prefix = 'xyz_';
        $tables = get_table_definitions($prefix);

        $this->assertArrayHasKey('tblUser', $tables);
        $this->assertArrayHasKey('tblPost', $tables);
        $this->assertArrayHasKey('tblTopic', $tables);
        $this->assertArrayHasKey('tblSetting', $tables);

        $this->assertStringContainsString('xyz_tbl_users', $tables['tblUser']);
        $this->assertStringContainsString('xyz_tbl_posts', $tables['tblPost']);
        $this->assertStringContainsString('xyz_tbl_users', $tables['saveAdmin']);
    }

    public function testDaoTableMethod()
    {
        $testDao = new TestDao();
        
        $this->assertTrue(method_exists($testDao, 'table'));

        $result = $testDao->testTableMethod('tbl_users');
        $this->assertStringContainsString('tbl_users', $result);
    }
}

class TestDao
{
    protected $prefix = '';

    public function __construct()
    {
        $this->prefix = get_table_prefix();
    }

    protected function table($table)
    {
        return $this->prefix . $table;
    }

    public function testTableMethod($table)
    {
        return $this->table($table);
    }
}
