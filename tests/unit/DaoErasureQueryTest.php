<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * DaoErasureQuery Test
 *
 * Covers the v1.6.0 GDPR hardening of the DAO layer:
 *   - CommentDao::findComments() email filter + sort-column allow-list
 *   - ConsentDao::hasConsented() per-IP lookup + getAllConsents() allow-list
 *   - PostDao::anonymizePostAuthor() fallback author reassignment
 *   - DataRequestDao::getAllRequests() / PrivacyLogDao::getAllLogs() allow-list
 *   - UserDao::deleteUser() boolean return value
 *
 * Uses a capturing DBC stub so generated SQL and bound parameters can be
 * asserted without a live database.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class DaoErasureQueryTest extends TestCase
{
    private $originalDbc;

    protected function setUp(): void
    {
        $this->originalDbc = \Scriptlog\Core\Registry::get('dbc');
        \Scriptlog\Core\Registry::set('dbc', $this->makeCapturingDbc());
    }

    protected function tearDown(): void
    {
        \Scriptlog\Core\Registry::set('dbc', $this->originalDbc);
    }

    private function makeCapturingDbc(): object
    {
        return new class() {
            public $queries = [];
            public $updates = [];

            public function dbQuery($sql, $args = [])
            {
                $this->queries[] = ['sql' => $sql, 'args' => $args];

                return new class() {
                    public function fetch() { return null; }
                    public function fetchAll() { return []; }
                    public function fetchColumn() { return 0; }
                    public function rowCount() { return 0; }
                    public function closeCursor() { return true; }
                };
            }

            public function dbUpdate($table, $params, $where)
            {
                $this->updates[] = ['table' => $table, 'params' => $params, 'where' => $where];

                return 1;
            }

            public function dbDelete($table, $where, $limit = null) { return 1; }
            public function dbInsert($table, $params) { return 1; }
            public function dbTransaction() { return true; }
            public function dbCommit() { return true; }
            public function dbRollBack() { return true; }
            public function dbLastInsertId() { return '1'; }
            public function dbReplace($table, $params, $updateParams) { return true; }
            public function select() { return []; }
            public function get() { return null; }
            public function dbSelect() { return []; }
        };
    }

    private function lastQuery(): array
    {
        $dbc = \Scriptlog\Core\Registry::get('dbc');
        $queries = $dbc->queries;

        return end($queries);
    }

    public function testCommentDaoFindCommentsFiltersByEmail(): void
    {
        $dao = new \Scriptlog\Dao\CommentDao();
        $result = $dao->findComments('ID', 'erased@example.com');

        $this->assertIsArray($result);
        $query = $this->lastQuery();
        $this->assertStringContainsString('WHERE c.comment_author_email = ?', $query['sql']);
        $this->assertSame(['erased@example.com'], $query['args']);
    }

    public function testCommentDaoFindCommentsWithoutEmailOmitsWhereClause(): void
    {
        $dao = new \Scriptlog\Dao\CommentDao();
        $dao->findComments('ID');

        $query = $this->lastQuery();
        $this->assertStringNotContainsString('WHERE', $query['sql']);
        $this->assertSame([], $query['args']);
    }

    public function testCommentDaoFindCommentsRejectsUnknownSortColumn(): void
    {
        $dao = new \Scriptlog\Dao\CommentDao();
        $dao->findComments('comment_author_email; DROP TABLE tbl_comments', 'a@b.com');

        $query = $this->lastQuery();
        $this->assertStringContainsString('ORDER BY c.ID DESC', $query['sql']);
    }

    public function testConsentDaoHasConsentedFiltersByIpWhenProvided(): void
    {
        $dao = new \Scriptlog\Dao\ConsentDao();
        $dao->hasConsented('cookie', '203.0.113.5');

        $query = $this->lastQuery();
        $this->assertStringContainsString('AND consent_ip = ?', $query['sql']);
        $this->assertSame(['cookie', '203.0.113.5'], $query['args']);
    }

    public function testConsentDaoHasConsentedIgnoresEmptyIp(): void
    {
        $dao = new \Scriptlog\Dao\ConsentDao();
        $dao->hasConsented('cookie', '');

        $query = $this->lastQuery();
        $this->assertStringNotContainsString('AND consent_ip = ?', $query['sql']);
        $this->assertSame(['cookie'], $query['args']);
    }

    public function testConsentDaoHasConsentedWithoutIpUsesLegacyLookup(): void
    {
        $dao = new \Scriptlog\Dao\ConsentDao();
        $dao->hasConsented('analytics');

        $query = $this->lastQuery();
        $this->assertStringNotContainsString('AND consent_ip = ?', $query['sql']);
        $this->assertSame(['analytics'], $query['args']);
    }

    public function testConsentDaoGetAllConsentsRejectsUnknownSortColumn(): void
    {
        $dao = new \Scriptlog\Dao\ConsentDao();
        $dao->getAllConsents('consent_ip; DELETE FROM tbl_consents');

        $query = $this->lastQuery();
        $this->assertStringContainsString('ORDER BY ID DESC', $query['sql']);
    }

    public function testPostDaoAnonymizePostAuthorUsesFallbackAuthor(): void
    {
        $dao = new \Scriptlog\Dao\PostDao();
        $result = $dao->anonymizePostAuthor(42, 9);

        $this->assertTrue($result);

        $dbc = \Scriptlog\Core\Registry::get('dbc');
        $this->assertCount(1, $dbc->updates);
        $this->assertSame(['post_author' => 9], $dbc->updates[0]['params']);
        $this->assertSame(['post_author' => 42], $dbc->updates[0]['where']);
    }

    public function testPostDaoAnonymizePostAuthorDefaultsToUserIdOne(): void
    {
        $dao = new \Scriptlog\Dao\PostDao();
        $dao->anonymizePostAuthor(42);

        $dbc = \Scriptlog\Core\Registry::get('dbc');
        $this->assertSame(['post_author' => 1], $dbc->updates[0]['params']);
    }

    public function testDataRequestDaoGetAllRequestsRejectsUnknownSortColumn(): void
    {
        $dao = new \Scriptlog\Dao\DataRequestDao();
        $dao->getAllRequests('request_email; DROP TABLE tbl_data_requests');

        $query = $this->lastQuery();
        $this->assertStringContainsString('ORDER BY ID DESC', $query['sql']);
    }

    public function testDataRequestDaoGetAllRequestsAcceptsAllowedSortColumn(): void
    {
        $dao = new \Scriptlog\Dao\DataRequestDao();
        $dao->getAllRequests('request_status');

        $query = $this->lastQuery();
        $this->assertStringContainsString('ORDER BY request_status DESC', $query['sql']);
    }

    public function testPrivacyLogDaoGetAllLogsRejectsUnknownSortColumn(): void
    {
        $dao = new \Scriptlog\Dao\PrivacyLogDao();
        $dao->getAllLogs('log_email; DROP TABLE tbl_privacy_logs');

        $query = $this->lastQuery();
        $this->assertStringContainsString('ORDER BY ID DESC', $query['sql']);
    }

    public function testPrivacyLogDaoGetAllLogsAcceptsAllowedSortColumn(): void
    {
        $dao = new \Scriptlog\Dao\PrivacyLogDao();
        $dao->getAllLogs('log_action');

        $query = $this->lastQuery();
        $this->assertStringContainsString('ORDER BY log_action DESC', $query['sql']);
    }

    public function testUserDaoDeleteUserReturnsBooleanResult(): void
    {
        $dao = new \Scriptlog\Dao\UserDao();
        $sanitize = $this->createMock(\Scriptlog\Core\Sanitize::class);

        $dbc = \Scriptlog\Core\Registry::get('dbc');
        $result = $dao->deleteUser(5, $sanitize);

        $this->assertIsBool($result);
    }
}
