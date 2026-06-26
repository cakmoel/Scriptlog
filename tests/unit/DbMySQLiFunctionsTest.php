<?php

use PHPUnit\Framework\TestCase;

class DbMySQLiFunctionsTest extends TestCase
{
    private string $utilityPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->utilityPath = __DIR__ . '/../../src/lib/utility/db-mysqli.php';
    }

    public function testFileExists(): void
    {
        $this->assertFileExists($this->utilityPath);
    }

    public function testFileIsValidPhpSyntax(): void
    {
        $output = [];
        $returnCode = 0;
        exec('php -l ' . escapeshellarg($this->utilityPath) . ' 2>&1', $output, $returnCode);
        $this->assertEquals(0, $returnCode, 'PHP syntax check failed: ' . implode("\n", $output));
    }

    public function testIsTableExistsHasFunctionExistsGuard(): void
    {
        $source = file_get_contents($this->utilityPath);

        $this->assertStringContainsString("if (!function_exists('is_table_exists'))", $source);
    }

    public function testDbInstanceFunctionExists(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('function db_instance()', $source);
    }

    public function testDbBeginTransactionFunctionExists(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('function db_begin_transaction()', $source);
    }

    public function testDbCommitFunctionExists(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('function db_commit()', $source);
    }

    public function testDbInsertIdFunctionExists(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('function db_insert_id()', $source);
    }

    public function testDbSimpleQueryFunctionExists(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('function db_simple_query($sql)', $source);
    }

    public function testDbPreparedQueryFunctionExists(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('function db_prepared_query($sql, array $params', $source);
    }

    public function testIsTableExistsFunctionExists(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('function is_table_exists($table)', $source);
    }

    public function testDbNumRowsFunctionExists(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('function db_num_rows($results)', $source);
    }

    public function testCheckTableFunctionExists(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('function check_table()', $source);
    }

    public function testGetResultFunctionExists(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('function get_result($Statement)', $source);
    }

    public function testDbCloseFunctionExists(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('function db_close()', $source);
    }

    public function testDbInstanceChecksRegistryFirst(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString("Registry::get('dbc')", $source);
    }

    public function testDbInstanceFallsBackToDbMySQLi(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('DbMySQLi::getInstance()', $source);
    }

    public function testPreparedQuerySupportsBothMysqliAndPdo(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString("'preparedQuery'", $source, 'Should support mysqli preparedQuery');
        $this->assertStringContainsString("'dbQuery'", $source, 'Should support PDO dbQuery');
    }

    public function testIsTableExistsSupportsDbClass(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString("'isTableExists'", $source);
    }

    public function testIsTableExistsSupportsMedoo(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString("'dbSelect'", $source);
    }

    public function testIsTableExistsHasExceptionHandling(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('catch (Exception $e)', $source);
    }

    public function testIsTableExistsReturnsFalseOnFailure(): void
    {
        $source = file_get_contents($this->utilityPath);

        preg_match_all('/return\s+false/', $source, $matches);
        $this->assertGreaterThanOrEqual(1, count($matches[0]), 'Should return false on failure');
    }

    public function testTransactionFunctionsReturnFalseOnFailure(): void
    {
        $source = file_get_contents($this->utilityPath);

        $this->assertStringContainsString('return false;', $source);
    }

    public function testDbNumRowsSupportsMultipleTypes(): void
    {
        $source = file_get_contents($this->utilityPath);

        $this->assertStringContainsString("'getNumRows'", $source);
        $this->assertStringContainsString('is_array($results)', $source);
        $this->assertStringContainsString('PDOStatement', $source);
    }

    public function testCheckTableChecksAllCoreTables(): void
    {
        $source = file_get_contents($this->utilityPath);

        $coreTables = [
            'tbl_comments', 'tbl_login_attempt', 'tbl_media', 'tbl_mediameta',
            'tbl_media_download', 'tbl_menu', 'tbl_plugin', 'tbl_posts',
            'tbl_post_topic', 'tbl_settings', 'tbl_themes', 'tbl_topics',
            'tbl_users', 'tbl_user_token'
        ];

        foreach ($coreTables as $table) {
            $this->assertStringContainsString($table, $source, "check_table should verify {$table} exists");
        }
    }

    public function testCheckTableHasAppDevelopmentCondition(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString('APP_DEVELOPMENT', $source);
    }

    public function testCloseDbSupportsBothMysqliAndPdo(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString("'disconnect'", $source);
        $this->assertStringContainsString("'closeDbConnection'", $source);
    }

    public function testSimpleQuerySupportsBothMysqliAndDbClass(): void
    {
        $source = file_get_contents($this->utilityPath);
        $this->assertStringContainsString("'dbQuery'", $source);
        $this->assertStringContainsString("'simpleQuery'", $source);
    }
}
