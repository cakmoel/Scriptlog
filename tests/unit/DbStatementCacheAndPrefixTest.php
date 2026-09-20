<?php
/**
 * DbStatementCacheAndPrefixTest
 *
 * Verifies the two P5/P6 optimizations in Scriptlog\Core\Db:
 *   - P6: applyTablePrefix() single-pass alternation produces byte-identical
 *         output to the previous per-table preg_replace loop (parity test)
 *   - P5: prepareCached() reuses a prepared statement for identical SQL text
 *         and prepares fresh statements for distinct SQL
 */

use PHPUnit\Framework\TestCase;
use Scriptlog\Core\Db;

class DbStatementCacheAndPrefixTest extends TestCase
{
    private function getKnownTables(): array
    {
        $ref = new ReflectionProperty(Db::class, 'knownTables');
        $ref->setAccessible(true);
        return $ref->getValue(new Db());
    }

    private function applyPrefixWith(Db $db, string $sql): string
    {
        $method = new ReflectionMethod(Db::class, 'applyTablePrefix');
        $method->setAccessible(true);
        return $method->invoke($db, $sql);
    }

    private function legacyApplyTablePrefix(Db $db, string $sql): string
    {
        $prefix = $db->getTablePrefix();
        if (empty($prefix)) {
            return $sql;
        }

        foreach ($this->getKnownTables() as $table) {
            $pattern = '/(?<![a-zA-Z_])' . preg_quote($table, '/') . '(?![a-zA-Z_])/';
            $sql = preg_replace($pattern, $prefix . $table, $sql);
        }

        return $sql;
    }

    /**
     * A representative corpus of every query shape the DAO layer issues,
     * including prefix-collision names (tbl_media / tbl_mediameta /
     * tbl_media_download) and already-prefixed identifiers.
     */
    private function sqlCorpus(): array
    {
        return [
            "SELECT ID, post_title FROM tbl_posts WHERE post_status = 'publish'",
            "SELECT p.ID, t.ID FROM tbl_posts p JOIN tbl_post_topic pt ON pt.post_id = p.ID JOIN tbl_topics t ON pt.topic_id = t.ID WHERE t.topic_status = 'Y'",
            "SELECT COUNT(c.ID) AS total FROM tbl_comments c WHERE c.comment_post_id = ? AND c.comment_status = 'approved'",
            "SELECT ID, theme_directory FROM tbl_themes WHERE theme_status = 'Y'",
            "SELECT ID, license_key FROM tbl_theme_licenses WHERE theme_id = ?",
            "SELECT setting_name, setting_value FROM tbl_settings",
            "INSERT INTO tbl_posts (post_title, post_status) VALUES (?, ?)",
            "UPDATE tbl_users SET user_login = ? WHERE ID = ?",
            "DELETE FROM tbl_login_attempt WHERE user_login = ?",
            "INSERT INTO tbl_media (media_filename) VALUES (?) ON DUPLICATE KEY UPDATE media_filename = ?",
            "SELECT * FROM tbl_media_download WHERE id = ?",
            "SELECT * FROM tbl_mediameta WHERE media_id = ?",
            "SELECT * FROM tbl_media WHERE ID = ?",
            "SELECT ID, media_id FROM tbl_media_download WHERE token = ?",
            "ALTER TABLE tbl_posts ADD COLUMN post_locale VARCHAR(10) NOT NULL DEFAULT 'en'",
            "SELECT * FROM `tbl_menu` WHERE menu_status = 'Y'",
            "SHOW CREATE TABLE tbl_posts",
            "SELECT COUNT(*) FROM tpglkl_tbl_posts",
            "SELECT user_login FROM tpglkl_tbl_users WHERE ID = 1",
            "SELECT 1 FROM tbl_privacy_policies WHERE is_default = 1",
            "SELECT * FROM tbl_translations WHERE lang_code = ?",
        ];
    }

    public function testApplyTablePrefixMatchesLegacyLoop()
    {
        $db = new Db();
        $db->setTablePrefix('tpglkl_');

        foreach ($this->sqlCorpus() as $sql) {
            $this->assertSame(
                $this->legacyApplyTablePrefix($db, $sql),
                $this->applyPrefixWith($db, $sql),
                'Single-pass applyTablePrefix diverged for: ' . $sql
            );
        }
    }

    public function testApplyTablePrefixNoOpWithoutPrefix()
    {
        $db = new Db();
        $db->setTablePrefix('');

        $sql = 'SELECT * FROM tbl_posts';

        $this->assertSame($sql, $this->applyPrefixWith($db, $sql));
    }

    public function testApplyTablePrefixPrefixesTableNames()
    {
        $db = new Db();
        $db->setTablePrefix('xyz_');

        $this->assertSame(
            'SELECT * FROM xyz_tbl_posts',
            $this->applyPrefixWith($db, 'SELECT * FROM tbl_posts')
        );
    }

    public function testApplyTablePrefixDoesNotDoublePrefix()
    {
        $db = new Db();
        $db->setTablePrefix('xyz_');

        $this->assertSame(
            'SELECT * FROM xyz_tbl_posts',
            $this->applyPrefixWith($db, 'SELECT * FROM xyz_tbl_posts')
        );
    }

    public function testApplyTablePrefixResolvesLongestName()
    {
        $db = new Db();
        $db->setTablePrefix('xyz_');

        $sql = 'SELECT * FROM tbl_media_download JOIN tbl_mediameta ON tbl_mediameta.media_id = tbl_media_download.id JOIN tbl_media ON tbl_media.ID = tbl_media_download.media_id';

        $this->assertSame(
            'SELECT * FROM xyz_tbl_media_download JOIN xyz_tbl_mediameta ON xyz_tbl_mediameta.media_id = xyz_tbl_media_download.id JOIN xyz_tbl_media ON xyz_tbl_media.ID = xyz_tbl_media_download.media_id',
            $this->applyPrefixWith($db, $sql)
        );
    }

    public function testPrepareCachedReusesStatementForIdenticalSql()
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite driver not available');
        }

        $db = new Db(['sqlite::memory:', null, null]);
        $db->dbQuery('CREATE TABLE cache_test (id INTEGER PRIMARY KEY, value TEXT)');
        $db->dbQuery('INSERT INTO cache_test (value) VALUES (?)', ['a']);

        $stmtA = $db->dbQuery('SELECT value FROM cache_test WHERE id = ?', [1]);
        $stmtB = $db->dbQuery('SELECT value FROM cache_test WHERE id = ?', [1]);
        $stmtC = $db->dbQuery('SELECT value FROM cache_test WHERE id = ?', [1]);
        $stmtD = $db->dbQuery('SELECT value FROM cache_test WHERE value = ?', ['a']);

        $this->assertSame($stmtA, $stmtB, 'Identical SQL should reuse the same PDOStatement');
        $this->assertSame($stmtA, $stmtC, 'Identical SQL should reuse the same PDOStatement');
        $this->assertNotSame($stmtA, $stmtD, 'Distinct SQL should prepare a fresh statement');

        $this->assertSame('a', $stmtA->fetchColumn());
    }

    public function testPrepareCachedReusedStatementExecutesFreshArgs()
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite driver not available');
        }

        $db = new Db(['sqlite::memory:', null, null]);
        $db->dbQuery('CREATE TABLE cache_test (id INTEGER PRIMARY KEY, value TEXT)');
        $db->dbQuery('INSERT INTO cache_test (value) VALUES (?)', ['one']);
        $db->dbQuery('INSERT INTO cache_test (value) VALUES (?)', ['two']);

        $stmt = $db->dbQuery('SELECT value FROM cache_test WHERE id = ?', [1]);
        $this->assertSame('one', $stmt->fetchColumn());

        $sameStmt = $db->dbQuery('SELECT value FROM cache_test WHERE id = ?', [2]);
        $this->assertSame($stmt, $sameStmt, 'Cached statement is reused for identical SQL');
        $this->assertSame('two', $sameStmt->fetchColumn(), 'Re-executing with new args returns the new row');
    }

    public function testClearStatementCacheForcesFreshPrepare()
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite driver not available');
        }

        $db = new Db(['sqlite::memory:', null, null]);
        $db->dbQuery('CREATE TABLE cache_test (id INTEGER PRIMARY KEY, value TEXT)');

        $stmtA = $db->dbQuery('SELECT value FROM cache_test WHERE id = ?', [1]);
        $db->clearStatementCache();
        $stmtB = $db->dbQuery('SELECT value FROM cache_test WHERE id = ?', [1]);

        $this->assertNotSame($stmtA, $stmtB, 'After clearing the cache a fresh statement is prepared');
    }
}
