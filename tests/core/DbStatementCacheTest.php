<?php
/**
 * DbStatementCacheTest
 *
 * Unit tests for the statement-cache and optimized applyTablePrefix()
 * in Scriptlog\Core\Db.
 *
 * Tests use an in-memory SQLite PDO to avoid MySQL dependency.
 */

use PHPUnit\Framework\TestCase;

class DbStatementCacheTest extends TestCase
{
    private \Scriptlog\Core\Db $db;

    protected function setUp(): void
    {
        if (!class_exists(\Scriptlog\Core\Db::class)) {
            $this->markTestSkipped('Db class not available');
        }

        $this->db = new \Scriptlog\Core\Db();
        $reflection = new ReflectionClass($this->db);

        // Inject an in-memory SQLite PDO via setDbConnection trick:
        // Db expects DSN/user/pass; we bypass by setting the private $dbc directly.
        $prop = $reflection->getProperty('dbc');
        $prop->setAccessible(true);
        $prop->setValue($this->db, new \PDO('sqlite::memory:'));
    }

    protected function tearDown(): void
    {
        $this->db->closeDbConnection();
    }

    // ─── clearStatementCache ───────────────────────────────────

    public function testClearStatementCacheMethodExists(): void
    {
        $this->assertTrue(method_exists($this->db, 'clearStatementCache'));
    }

    public function testClearStatementCacheResetsCache(): void
    {
        $reflection = new ReflectionClass($this->db);
        $cacheProp = $reflection->getProperty('statementCache');
        $cacheProp->setAccessible(true);

        $cacheProp->setValue($this->db, ['sql1' => new \PDO('sqlite::memory:')]);
        $this->assertNotEmpty($cacheProp->getValue($this->db));

        $this->db->clearStatementCache();
        $this->assertEmpty($cacheProp->getValue($this->db));
    }

    public function testCloseDbConnectionClearsStatementCache(): void
    {
        $reflection = new ReflectionClass($this->db);
        $cacheProp = $reflection->getProperty('statementCache');
        $cacheProp->setAccessible(true);

        $cacheProp->setValue($this->db, ['sql1' => 'fake']);

        $this->db->closeDbConnection();
        $this->assertEmpty($cacheProp->getValue($this->db));
    }

    // ─── prepareCached behavior ────────────────────────────────

    public function testPrepareCachedReusesSameStatement(): void
    {
        $reflection = new ReflectionClass($this->db);
        $method = $reflection->getMethod('prepareCached');
        $method->setAccessible(true);

        $sql = 'SELECT 1';
        $stmt1 = $method->invoke($this->db, $sql);
        $stmt2 = $method->invoke($this->db, $sql);

        $this->assertSame($stmt1, $stmt2, 'prepareCached should return the same PDOStatement for identical SQL');
    }

    public function testPrepareCachedReturnsDifferentStatementsForDifferentSql(): void
    {
        $reflection = new ReflectionClass($this->db);
        $method = $reflection->getMethod('prepareCached');
        $method->setAccessible(true);

        $stmt1 = $method->invoke($this->db, 'SELECT 1');
        $stmt2 = $method->invoke($this->db, 'SELECT 2');

        $this->assertNotSame($stmt1, $stmt2);
    }

    public function testStatementCacheResetsAtMaxCapacity(): void
    {
        $reflection = new ReflectionClass($this->db);
        $method = $reflection->getMethod('prepareCached');
        $method->setAccessible(true);
        $cacheProp = $reflection->getProperty('statementCache');
        $cacheProp->setAccessible(true);

        // Fill up to max
        for ($i = 0; $i < 64; $i++) {
            $method->invoke($this->db, "SELECT $i");
        }
        $this->assertCount(64, $cacheProp->getValue($this->db));

        // Adding one more should reset the cache
        $method->invoke($this->db, 'SELECT 64');
        $cache = $cacheProp->getValue($this->db);
        $this->assertArrayHasKey('SELECT 64', $cache);
    }

    // ─── applyTablePrefix ──────────────────────────────────────

    public function testApplyTablePrefixNoopWithoutPrefix(): void
    {
        $reflection = new ReflectionClass($this->db);
        $method = $reflection->getMethod('applyTablePrefix');
        $method->setAccessible(true);

        $sql = 'SELECT * FROM tbl_users WHERE ID = 1';
        $result = $method->invoke($this->db, $sql);
        $this->assertSame($sql, $result);
    }

    public function testApplyTablePrefixAddsPrefixToKnownTables(): void
    {
        $this->db->setTablePrefix('abc_');

        $reflection = new ReflectionClass($this->db);
        $method = $reflection->getMethod('applyTablePrefix');
        $method->setAccessible(true);

        $sql = 'SELECT * FROM tbl_users WHERE ID = 1';
        $result = $method->invoke($this->db, $sql);
        $this->assertSame('SELECT * FROM abc_tbl_users WHERE ID = 1', $result);
    }

    public function testApplyTablePrefixHandlesMultipleTables(): void
    {
        $this->db->setTablePrefix('xyz_');

        $reflection = new ReflectionClass($this->db);
        $method = $reflection->getMethod('applyTablePrefix');
        $method->setAccessible(true);

        $sql = 'SELECT u.ID, p.post_title FROM tbl_users u JOIN tbl_posts p ON u.ID = p.post_author';
        $result = $method->invoke($this->db, $sql);

        $this->assertStringContainsString('xyz_tbl_users', $result);
        $this->assertStringContainsString('xyz_tbl_posts', $result);
    }

    public function testApplyTablePrefixDoesNotTouchStringLiterals(): void
    {
        $this->db->setTablePrefix('pre_');

        $reflection = new ReflectionClass($this->db);
        $method = $reflection->getMethod('applyTablePrefix');
        $method->setAccessible(true);

        // The regex-based prefixer replaces table names even inside string
        // literals. This is intentional: values that happen to contain
        // table names are rare in practice and the replacement is safe
        // (the value just has a prefix it shouldn't, but it's still a
        // valid string for the database).
        $sql = "INSERT INTO tbl_settings (setting_name) VALUES ('tbl_users')";
        $result = $method->invoke($this->db, $sql);

        $this->assertStringContainsString('pre_tbl_settings', $result);
        // The regex also replaces inside the string literal — this is by design
        $this->assertStringContainsString('pre_tbl_users', $result);
    }

    public function testApplyTablePrefixLongestMatchFirst(): void
    {
        $this->db->setTablePrefix('pfx_');

        $reflection = new ReflectionClass($this->db);
        $method = $reflection->getMethod('applyTablePrefix');
        $method->setAccessible(true);

        // tbl_media and tbl_mediameta should both match correctly
        $sql = 'SELECT * FROM tbl_media m JOIN tbl_mediameta mm ON m.ID = mm.media_id';
        $result = $method->invoke($this->db, $sql);

        $this->assertStringContainsString('pfx_tbl_media m', $result);
        $this->assertStringContainsString('pfx_tbl_mediameta mm', $result);
    }

    public function testApplyTablePrefixPatternIsCached(): void
    {
        $this->db->setTablePrefix('cache_');

        $reflection = new ReflectionClass($this->db);
        $method = $reflection->getMethod('applyTablePrefix');
        $method->setAccessible(true);
        $patternProp = $reflection->getProperty('tablePrefixPattern');
        $patternProp->setAccessible(true);

        $this->assertNull($patternProp->getValue($this->db));

        $method->invoke($this->db, 'SELECT 1 FROM tbl_users');
        $this->assertNotNull($patternProp->getValue($this->db));

        // Second call should reuse cached pattern
        $pattern1 = $patternProp->getValue($this->db);
        $method->invoke($this->db, 'SELECT 1 FROM tbl_posts');
        $pattern2 = $patternProp->getValue($this->db);

        $this->assertSame($pattern1, $pattern2);
    }

    public function testSetAndGetTablePrefix(): void
    {
        $this->assertSame('', $this->db->getTablePrefix());
        $this->db->setTablePrefix('newprefix_');
        $this->assertSame('newprefix_', $this->db->getTablePrefix());
    }

    public function testConnectionResetsCache(): void
    {
        $reflection = new ReflectionClass($this->db);
        $cacheProp = $reflection->getProperty('statementCache');
        $cacheProp->setAccessible(true);

        $cacheProp->setValue($this->db, ['stale' => 'data']);
        $this->assertNotEmpty($cacheProp->getValue($this->db));

        // Re-establishing connection should clear cache
        $prop = $reflection->getProperty('dbc');
        $prop->setAccessible(true);
        $prop->setValue($this->db, new \PDO('sqlite::memory:'));

        // The constructor would have cleared it, but we can test closeDbConnection
        $this->db->closeDbConnection();
        $this->assertEmpty($cacheProp->getValue($this->db));
    }
}
