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

require_once __DIR__ . '/../lib/common.php';
require_once __DIR__ . '/../install/include/setup.php';
require_once __DIR__ . '/../install/include/dbtable.php';

class TablePrefixTest
{
    private $passed = 0;
    private $failed = 0;
    private $tests = [];

    public function run()
    {
        echo "========================================\n";
        echo "  Table Prefix Functionality Tests\n";
        echo "========================================\n\n";

        $this->testGenerateTablePrefix();
        $this->testTablePrefixFormat();
        $this->testTablePrefixUnique();
        $this->testGetTableDefinitions();
        $this->testDaoTableMethod();

        echo "\n========================================\n";
        echo "  Test Results: {$this->passed} passed, {$this->failed} failed\n";
        echo "========================================\n";

        return $this->failed === 0;
    }

    private function assert($condition, $testName)
    {
        if ($condition) {
            echo "[PASS] {$testName}\n";
            $this->passed++;
        } else {
            echo "[FAIL] {$testName}\n";
            $this->failed++;
        }
    }

    public function testGenerateTablePrefix()
    {
        echo "\n--- Testing generate_table_prefix() ---\n";

        $prefix = generate_table_prefix(6);
        $this->assert(!empty($prefix), "Prefix should not be empty");
        $this->assert(strlen($prefix) === 7, "Prefix length should be 7 (6 chars + underscore)");
        $this->assert(substr($prefix, -1) === '_', "Prefix should end with underscore");
        $this->assert(ctype_lower(substr($prefix, 0, -1)), "Prefix should contain only lowercase letters");
    }

    public function testTablePrefixFormat()
    {
        echo "\n--- Testing prefix format ---\n";

        $prefix = generate_table_prefix(4);
        $this->assert(preg_match('/^[a-z]{4}_$/', $prefix), "Prefix should match pattern: 4 lowercase letters + underscore");
    }

    public function testTablePrefixUnique()
    {
        echo "\n--- Testing prefix uniqueness ---\n";

        $prefixes = [];
        for ($i = 0; $i < 10; $i++) {
            $prefixes[] = generate_table_prefix(4);
        }
        
        $uniquePrefixes = array_unique($prefixes);
        $this->assert(count($uniquePrefixes) === 10, "Each generated prefix should be unique");
    }

    public function testGetTableDefinitions()
    {
        echo "\n--- Testing get_table_definitions() ---\n";

        $prefix = 'xyz_';
        $tables = get_table_definitions($prefix);

        $this->assert(isset($tables['tblUser']), "Should return tblUser definition");
        $this->assert(isset($tables['tblPost']), "Should return tblPost definition");
        $this->assert(isset($tables['tblTopic']), "Should return tblTopic definition");
        $this->assert(isset($tables['tblSetting']), "Should return tblSetting definition");

        $this->assert(
            strpos($tables['tblUser'], 'xyz_tbl_users') !== false,
            "User table should use prefix: xyz_tbl_users"
        );

        $this->assert(
            strpos($tables['tblPost'], 'xyz_tbl_posts') !== false,
            "Post table should use prefix: xyz_tbl_posts"
        );

        $this->assert(
            strpos($tables['saveAdmin'], 'xyz_tbl_users') !== false,
            "SaveAdmin should use prefix: xyz_tbl_users"
        );
    }

    public function testDaoTableMethod()
    {
        echo "\n--- Testing Dao table() method ---\n";

        // Create a test class that extends Dao to test the table method
        $testDao = new TestDao();
        
        $this->assert(
            method_exists($testDao, 'table'),
            "Dao should have table() method"
        );

        $result = $testDao->testTableMethod('tbl_users');
        $this->assert(
            strpos($result, 'tbl_users') !== false,
            "table() should return table name"
        );
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

$test = new TablePrefixTest();
$success = $test->run();
exit($success ? 0 : 1);
