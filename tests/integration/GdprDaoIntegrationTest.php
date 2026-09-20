<?php

use PHPUnit\Framework\TestCase;
use Scriptlog\Core\AppException;
use Scriptlog\Core\Db;
use Scriptlog\Core\FormValidator;
use Scriptlog\Core\Registry;
use Scriptlog\Core\Sanitize;
use Scriptlog\Dao\CommentDao;
use Scriptlog\Dao\ConsentDao;
use Scriptlog\Dao\DataRequestDao;
use Scriptlog\Dao\PostDao;
use Scriptlog\Dao\PrivacyLogDao;
use Scriptlog\Dao\UserDao;
use Scriptlog\Dao\UserTokenDao;
use Scriptlog\Service\DataRequestService;
use Scriptlog\Service\UserService;

/**
 * Integration tests for the GDPR data layer.
 *
 * Exercises the real DataRequestDao, PrivacyLogDao and ConsentDao classes
 * against the blogware_test database through the application's Db wrapper
 * (registered in the Registry), plus the DB-backed paths of
 * DataRequestService (request creation audit trail, export, deletion).
 *
 * @coversDefaultClass Scriptlog\Dao\DataRequestDao
 */
class GdprDaoIntegrationTest extends TestCase
{
    /**
     * Raw PDO handle used for seeding and cleanup only.
     *
     * @var PDO|null
     */
    private static $pdo;

    /**
     * Application Db wrapper registered in the Registry for DAO access.
     *
     * @var Db|null
     */
    private static $db;

    /**
     * Data request ids created by the current test, for teardown cleanup.
     *
     * @var int[]
     */
    private $createdDataRequestIds = [];

    /**
     * Privacy log ids created by the current test, for teardown cleanup.
     *
     * @var int[]
     */
    private $createdPrivacyLogIds = [];

    /**
     * Consent ids created by the current test, for teardown cleanup.
     *
     * @var int[]
     */
    private $createdConsentIds = [];

    /**
     * User ids created by the current test, for teardown cleanup.
     *
     * @var int[]
     */
    private $createdUserIds = [];

    /**
     * Post ids created by the current test, for teardown cleanup.
     *
     * @var int[]
     */
    private $createdPostIds = [];

    /**
     * Comment ids created by the current test, for teardown cleanup.
     *
     * @var int[]
     */
    private $createdCommentIds = [];

    /**
     * Unique email for the current test run.
     *
     * @var string
     */
    private $testEmail;

    /**
     * Establish the test database connection.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        try {
            self::$pdo = new PDO(
                'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
                'blogwareuser',
                'userblogware',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            self::$db = new Db([
                'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
                'blogwareuser',
                'userblogware',
            ]);
        } catch (PDOException $e) {
            self::$pdo = null;
            self::$db = null;
        }
    }

    /**
     * Release the database connection.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
        self::$db = null;
    }

    /**
     * Register the Db wrapper, refresh unique fixture identifiers and purge
     * any leftovers from interrupted runs.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (self::$db === null || self::$pdo === null) {
            $this->markTestSkipped('Test database not available');
            return;
        }

        Registry::set('dbc', self::$db);

        $this->testEmail = uniqid('gdpr-dao-', false) . '@example.com';
        $this->purgeLeftovers($this->testEmail);
    }

    /**
     * Remove every row the current test inserted.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        if (self::$pdo === null) {
            return;
        }

        foreach ($this->createdDataRequestIds as $id) {
            self::$pdo->exec('DELETE FROM tbl_data_requests WHERE ID = ' . (int)$id);
        }
        foreach ($this->createdPrivacyLogIds as $id) {
            self::$pdo->exec('DELETE FROM tbl_privacy_logs WHERE ID = ' . (int)$id);
        }
        foreach ($this->createdConsentIds as $id) {
            self::$pdo->exec('DELETE FROM tbl_consents WHERE ID = ' . (int)$id);
        }
        foreach ($this->createdCommentIds as $id) {
            self::$pdo->exec('DELETE FROM tbl_comments WHERE ID = ' . (int)$id);
        }
        foreach ($this->createdPostIds as $id) {
            self::$pdo->exec('DELETE FROM tbl_posts WHERE ID = ' . (int)$id);
        }
        foreach ($this->createdUserIds as $id) {
            self::$pdo->exec('DELETE FROM tbl_users WHERE ID = ' . (int)$id);
        }

        $this->createdDataRequestIds = [];
        $this->createdPrivacyLogIds = [];
        $this->createdConsentIds = [];
        $this->createdUserIds = [];
        $this->createdPostIds = [];
        $this->createdCommentIds = [];
        $this->purgeLeftovers($this->testEmail);
    }

    /**
     * Delete rows matching the fixture email from all GDPR tables.
     *
     * @param string $email
     * @return void
     */
    private function purgeLeftovers($email): void
    {
        self::$pdo->exec("DELETE FROM tbl_data_requests WHERE request_email = " . self::$pdo->quote($email));
        self::$pdo->exec("DELETE FROM tbl_privacy_logs WHERE log_email = " . self::$pdo->quote($email));
        self::$pdo->exec("DELETE FROM tbl_consents WHERE consent_ip = '127.0.0.1'");
    }

    /**
     * Create a data request through the DAO and remember it for cleanup.
     *
     * @param DataRequestDao $dao
     * @param string $type
     * @param string $status
     * @return int
     */
    private function insertDataRequest(DataRequestDao $dao, $type = 'access', $status = 'pending')
    {
        $id = $dao->createRequest($type, $this->testEmail, '127.0.0.1', 'Integration fixture');
        if ($status !== 'pending') {
            $dao->updateRequestStatus($id, $status, 'Fixture update');
        }
        $this->createdDataRequestIds[] = (int)$id;
        return (int)$id;
    }

    /**
     * Create a privacy log through the DAO and remember it for cleanup.
     *
     * @param PrivacyLogDao $dao
     * @param string $action
     * @return int
     */
    private function insertPrivacyLog(PrivacyLogDao $dao, $action = 'data_request_created')
    {
        $id = $dao->createLog($action, 'access', 1, $this->testEmail, 'Integration fixture', '127.0.0.1');
        $this->createdPrivacyLogIds[] = (int)$id;
        return (int)$id;
    }

    /**
     * Create a consent record through the DAO and remember it for cleanup.
     *
     * @param ConsentDao $dao
     * @param string $status
     * @return int
     */
    private function insertConsent(ConsentDao $dao, $status = 'accepted')
    {
        $id = $dao->recordConsent('cookie', $status, '127.0.0.1', 'Integration UA');
        $this->createdConsentIds[] = (int)$id;
        return (int)$id;
    }

    /**
     * Seed a user row for the given email and remember it for cleanup.
     *
     * @param string $email
     * @return int
     */
    private function seedUser($email)
    {
        $login = preg_replace('/[^a-z0-9]/i', '', $email) . '_' . mt_rand(1000, 9999);
        $stmt = self::$pdo->prepare(
            "INSERT INTO tbl_users (user_login, user_email, user_pass, user_level, user_session)
             VALUES (?, ?, ?, 'administrator', '')"
        );
        $stmt->execute([$login, $email, password_hash('IntegrationPass', PASSWORD_BCRYPT)]);
        $id = (int)self::$pdo->lastInsertId();
        $this->createdUserIds[] = $id;
        return $id;
    }

    /**
     * Seed a post row and remember it for cleanup.
     *
     * @return int
     */
    private function seedPost()
    {
        $slug = 'gdpr-' . uniqid('', false);
        $stmt = self::$pdo->prepare(
            "INSERT INTO tbl_posts (post_author, post_title, post_slug, post_content, post_status, post_type)
             VALUES (?, ?, ?, ?, 'publish', 'blog')"
        );
        $stmt->execute([1, $slug, $slug, 'GDPR integration fixture']);
        $id = (int)self::$pdo->lastInsertId();
        $this->createdPostIds[] = $id;
        return $id;
    }

    /**
     * Seed a comment authored by the given email on the given post.
     *
     * @param int $postId
     * @param string $email
     * @return int
     */
    private function seedComment($postId, $email)
    {
        $stmt = self::$pdo->prepare(
            "INSERT INTO tbl_comments (comment_post_id, comment_author_name, comment_author_ip,
             comment_author_email, comment_content, comment_status)
             VALUES (?, ?, '127.0.0.1', ?, ?, 'approved')"
        );
        $stmt->execute([$postId, 'Integration User', $email, 'Comment from ' . $email]);
        $id = (int)self::$pdo->lastInsertId();
        $this->createdCommentIds[] = $id;
        return $id;
    }

    /**
     * Seed one comment per supplied email on a fresh post.
     *
     * @param array $emails
     * @return int[] IDs of the seeded comments
     */
    private function seedCommentsForEmails(array $emails)
    {
        $postId = $this->seedPost();
        $ids = [];
        foreach ($emails as $email) {
            $ids[] = $this->seedComment($postId, $email);
        }
        return $ids;
    }

    /**
     * DataRequestDao: createRequest() persists a pending request with the
     * supplied email, type, IP and note.
     *
     * @return void
     */
    public function testDataRequestDaoCreateAndFetchRequest(): void
    {
        $dao = new DataRequestDao();
        $id = $this->insertDataRequest($dao, 'access', 'pending');

        $row = $dao->getRequestById($id);

        $this->assertIsArray($row);
        $this->assertSame($this->testEmail, $row['request_email']);
        $this->assertSame('access', $row['request_type']);
        $this->assertSame('pending', $row['request_status']);
        $this->assertSame('Integration fixture', $row['request_note']);
    }

    /**
     * DataRequestDao: updateRequestStatus() records the new status and stamps
     * the completion date when a request is marked completed.
     *
     * @return void
     */
    public function testDataRequestDaoUpdateStatusStampsCompletionDate(): void
    {
        $dao = new DataRequestDao();
        $id = $this->insertDataRequest($dao, 'erasure', 'pending');

        $this->assertTrue($dao->updateRequestStatus($id, 'completed', 'Done by admin'));

        $row = $dao->getRequestById($id);

        $this->assertSame('completed', $row['request_status']);
        $this->assertNotEmpty($row['request_completed_date']);
    }

    /**
     * DataRequestDao: getRequestByEmail() returns every request for the email
     * including the one just created.
     *
     * @return void
     */
    public function testDataRequestDaoFindRequestsByEmail(): void
    {
        $dao = new DataRequestDao();
        $id = $this->insertDataRequest($dao, 'access', 'pending');

        $rows = $dao->getRequestByEmail($this->testEmail);

        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertSame($id, (int)$rows[0]['ID']);
    }

    /**
     * DataRequestDao: getRequestsByStatus() filters requests by their status.
     *
     * @return void
     */
    public function testDataRequestDaoFindRequestsByStatus(): void
    {
        $dao = new DataRequestDao();
        $this->insertDataRequest($dao, 'access', 'pending');

        $pending = $dao->getRequestsByStatus('pending');

        $this->assertIsArray($pending);
        $this->assertNotEmpty($pending);

        $this->insertDataRequest($dao, 'erasure', 'completed');

        $completed = $dao->getRequestsByStatus('completed');

        $this->assertIsArray($completed);
        $this->assertNotEmpty($completed);
    }

    /**
     * DataRequestDao: getAllRequests() returns all requests regardless of
     * status.
     *
     * @return void
     */
    public function testDataRequestDaoGetAllRequests(): void
    {
        $dao = new DataRequestDao();
        $this->insertDataRequest($dao, 'access', 'pending');

        $rows = $dao->getAllRequests();

        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
    }

    /**
     * DataRequestDao: getAllRequests() honours an allow-listed sort column
     * (verified by descending ID order) and falls back to the safe default
     * when handed untrusted input instead of interpolating it.
     *
     * @return void
     */
    public function testDataRequestDaoGetAllRequestsSortsAndRejectsUnsafeInput(): void
    {
        $dao = new DataRequestDao();
        $this->insertDataRequest($dao, 'access', 'pending');
        $this->insertDataRequest($dao, 'erasure', 'pending');

        $sorted = $dao->getAllRequests('ID');
        $this->assertIsArray($sorted);
        $this->assertNotEmpty($sorted);
        $this->assertDescendingIds($sorted);

        $malicious = $dao->getAllRequests('ID DESC; DROP TABLE tbl_users');
        $this->assertIsArray($malicious);
        $this->assertNotEmpty($malicious);
        $this->assertDescendingIds($malicious);
    }

    /**
     * DataRequestDao: getPendingCount() grows by one when a pending request is
     * added and returns a count after completion.
     *
     * @return void
     */
    public function testDataRequestDaoPendingCountIsRelative(): void
    {
        $dao = new DataRequestDao();

        $before = $dao->getPendingCount();
        $id = $this->insertDataRequest($dao, 'access', 'pending');
        $this->assertSame($before + 1, $dao->getPendingCount());

        $dao->updateRequestStatus($id, 'completed', 'closed');
        $this->assertSame($before, $dao->getPendingCount());
    }

    /**
     * DataRequestDao: totalRequestRecords() grows by one when a request is
     * added.
     *
     * @return void
     */
    public function testDataRequestDaoTotalRecordsIsRelative(): void
    {
        $dao = new DataRequestDao();

        $before = $dao->totalRequestRecords();
        $this->insertDataRequest($dao, 'access', 'pending');

        $this->assertSame($before + 1, $dao->totalRequestRecords());
    }

    /**
     * DataRequestDao: deleteRequest() removes the row so lookups return false.
     *
     * @return void
     */
    public function testDataRequestDaoDeleteRequest(): void
    {
        $dao = new DataRequestDao();
        $id = $this->insertDataRequest($dao, 'access', 'pending');

        $this->assertTrue($dao->deleteRequest($id));
        $this->assertFalse($dao->getRequestById($id));
    }

    /**
     * PrivacyLogDao: createLog() persists an audit entry retrievable by id.
     *
     * @return void
     */
    public function testPrivacyLogDaoCreateAndFetchLog(): void
    {
        $dao = new PrivacyLogDao();
        $id = $this->insertPrivacyLog($dao, 'data_request_created');

        $row = $dao->getLogById($id);

        $this->assertIsArray($row);
        $this->assertSame('data_request_created', $row['log_action']);
        $this->assertSame('access', $row['log_type']);
        $this->assertSame($this->testEmail, $row['log_email']);
        $this->assertSame('1', (string)$row['log_user_id']);
    }

    /**
     * PrivacyLogDao: getLogsByUserId() returns logs belonging to a user.
     *
     * @return void
     */
    public function testPrivacyLogDaoFindLogsByUserId(): void
    {
        $dao = new PrivacyLogDao();
        $this->insertPrivacyLog($dao, 'data_exported');

        $rows = $dao->getLogsByUserId(1);

        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
    }

    /**
     * PrivacyLogDao: getLogsByEmail() returns the fixture log.
     *
     * @return void
     */
    public function testPrivacyLogDaoFindLogsByEmail(): void
    {
        $dao = new PrivacyLogDao();
        $id = $this->insertPrivacyLog($dao, 'data_request_created');

        $rows = $dao->getLogsByEmail($this->testEmail);

        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
        $this->assertSame($id, (int)$rows[0]['ID']);
    }

    /**
     * PrivacyLogDao: getLogsByAction() filters by the action performed.
     *
     * @return void
     */
    public function testPrivacyLogDaoFindLogsByAction(): void
    {
        $dao = new PrivacyLogDao();
        $this->insertPrivacyLog($dao, 'consent_given');

        $rows = $dao->getLogsByAction('consent_given');

        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);
    }

    /**
     * PrivacyLogDao: getAllLogs() and getRecentLogs() return audit entries.
     *
     * @return void
     */
    public function testPrivacyLogDaoGetAllAndRecentLogs(): void
    {
        $dao = new PrivacyLogDao();
        $this->insertPrivacyLog($dao, 'data_exported');

        $all = $dao->getAllLogs();
        $this->assertIsArray($all);
        $this->assertNotEmpty($all);

        $recent = $dao->getRecentLogs(10);
        $this->assertIsArray($recent);
        $this->assertNotEmpty($recent);
    }

    /**
     * PrivacyLogDao: getAllLogs() honours an allow-listed sort column
     * (verified by descending ID order) and falls back to the safe default
     * when handed untrusted input.
     *
     * @return void
     */
    public function testPrivacyLogDaoGetAllLogsSortsAndRejectsUnsafeInput(): void
    {
        $dao = new PrivacyLogDao();
        $this->insertPrivacyLog($dao, 'data_exported');
        $this->insertPrivacyLog($dao, 'data_request_created');

        $sorted = $dao->getAllLogs('ID');
        $this->assertIsArray($sorted);
        $this->assertNotEmpty($sorted);
        $this->assertDescendingIds($sorted);

        $malicious = $dao->getAllLogs("ID DESC' OR '1'='1");
        $this->assertIsArray($malicious);
        $this->assertNotEmpty($malicious);
        $this->assertDescendingIds($malicious);
    }

    /**
     * PrivacyLogDao: totalLogRecords() grows by one when a log is added.
     *
     * @return void
     */
    public function testPrivacyLogDaoTotalRecordsIsRelative(): void
    {
        $dao = new PrivacyLogDao();

        $before = $dao->totalLogRecords();
        $this->insertPrivacyLog($dao, 'data_request_created');

        $this->assertSame($before + 1, $dao->totalLogRecords());
    }

    /**
     * PrivacyLogDao: deleteOldLogs() returns a boolean verdict (no rows older
     * than the retention window in a freshly seeded fixture).
     *
     * @return void
     */
    public function testPrivacyLogDaoDeleteOldLogsReturnsBoolean(): void
    {
        $dao = new PrivacyLogDao();
        $this->insertPrivacyLog($dao, 'data_request_created');

        $this->assertIsBool($dao->deleteOldLogs(365));
    }

    /**
     * ConsentDao: recordConsent() persists a consent and getLatestConsent()
     * returns it as the most recent record for the type.
     *
     * @return void
     */
    public function testConsentDaoRecordAndFetchLatestConsent(): void
    {
        $dao = new ConsentDao();
        $id = $this->insertConsent($dao, 'accepted');

        $latest = $dao->getLatestConsent('cookie');

        $this->assertIsArray($latest);
        $this->assertSame($id, (int)$latest['ID']);
        $this->assertSame('accepted', $latest['consent_status']);
    }

    /**
     * ConsentDao: updateConsent() flips the status of the latest record.
     *
     * @return void
     */
    public function testConsentDaoUpdateConsent(): void
    {
        $dao = new ConsentDao();
        $id = $this->insertConsent($dao, 'accepted');

        $this->assertTrue($dao->updateConsent($id, 'rejected'));

        $latest = $dao->getLatestConsent('cookie');
        $this->assertSame('rejected', $latest['consent_status']);
    }

    /**
     * ConsentDao: hasConsented() is false before any record exists for a type
     * and true once at least one accepted consent is recorded (it counts
     * accepted rows for the type, it does not inspect only the latest record).
     *
     * @return void
     */
    public function testConsentDaoHasConsented(): void
    {
        $dao = new ConsentDao();

        $this->assertFalse($dao->hasConsented('privacy-banner'));

        $this->insertConsent($dao, 'accepted');
        $this->assertTrue($dao->hasConsented('cookie'));

        $this->insertConsent($dao, 'rejected');
        $this->assertTrue($dao->hasConsented('cookie'));
    }

    /**
     * ConsentDao: getConsentsByIp() and getAllConsents() return records.
     *
     * @return void
     */
    public function testConsentDaoGetConsentsByIpAndAll(): void
    {
        $dao = new ConsentDao();
        $this->insertConsent($dao, 'accepted');

        $byIp = $dao->getConsentsByIp('127.0.0.1');
        $this->assertIsArray($byIp);
        $this->assertNotEmpty($byIp);

        $all = $dao->getAllConsents();
        $this->assertIsArray($all);
        $this->assertNotEmpty($all);
    }

    /**
     * ConsentDao: hasConsented() is evaluated per visitor when an IP address
     * is supplied — an accepted record for a different IP must not count.
     *
     * @return void
     */
    public function testConsentDaoHasConsentedScopesByIp(): void
    {
        $dao = new ConsentDao();
        $this->insertConsent($dao, 'accepted');

        $this->assertTrue($dao->hasConsented('cookie', '127.0.0.1'));
        $this->assertFalse($dao->hasConsented('cookie', '198.51.100.9'));
        $this->assertTrue($dao->hasConsented('cookie'));
    }

    /**
     * ConsentDao: getAllConsents() honours an allow-listed sort column
     * (verified by descending ID order) and falls back to the safe default
     * when handed untrusted input.
     *
     * @return void
     */
    public function testConsentDaoGetAllConsentsSortsAndRejectsUnsafeInput(): void
    {
        $dao = new ConsentDao();
        $this->insertConsent($dao, 'accepted');
        $this->insertConsent($dao, 'rejected');

        $sorted = $dao->getAllConsents('ID');
        $this->assertIsArray($sorted);
        $this->assertNotEmpty($sorted);
        $this->assertDescendingIds($sorted);

        $malicious = $dao->getAllConsents('consent_type DESC; DROP TABLE tbl_consents');
        $this->assertIsArray($malicious);
        $this->assertNotEmpty($malicious);
        $this->assertDescendingIds($malicious);
    }

    /**
     * ConsentDao: getAllConsents() returns an empty array (never false) when no
     * consent records exist — consistent with getAllRequests/getAllLogs (N14).
     *
     * @return void
     */
    public function testConsentDaoGetAllConsentsReturnsEmptyArrayWhenNoneExist(): void
    {
        $dao = new ConsentDao();

        foreach ($this->createdConsentIds as $id) {
            self::$pdo->exec('DELETE FROM tbl_consents WHERE ID = ' . (int)$id);
        }
        $this->createdConsentIds = [];
        self::$pdo->exec("DELETE FROM tbl_consents WHERE consent_ip = '127.0.0.1'");

        $all = $dao->getAllConsents();
        $this->assertIsArray($all);
        $this->assertSame([], $all);
    }

    /**
     * ConsentDao: totalConsentRecords() grows by one when a record is added.
     *
     * @return void
     */
    public function testConsentDaoTotalRecordsIsRelative(): void
    {
        $dao = new ConsentDao();

        $before = $dao->totalConsentRecords();
        $this->insertConsent($dao, 'accepted');

        $this->assertSame($before + 1, $dao->totalConsentRecords());
    }

    /**
     * ConsentDao: deleteOldConsents() returns a boolean verdict.
     *
     * @return void
     */
    public function testConsentDaoDeleteOldConsentsReturnsBoolean(): void
    {
        $dao = new ConsentDao();
        $this->insertConsent($dao, 'accepted');

        $this->assertIsBool($dao->deleteOldConsents(365));
    }

    /**
     * ConsentDao: deleteOldConsents() purges records older than the retention
     * window but keeps records that are still within it.
     *
     * @return void
     */
    public function testConsentDaoDeleteOldConsentsPurgesOnlyExpired(): void
    {
        $dao = new ConsentDao();
        $expiredId = $this->insertConsent($dao, 'accepted');
        $freshId = $this->insertConsent($dao, 'rejected');

        self::$pdo->exec(
            "UPDATE tbl_consents SET consent_date = DATE_SUB(NOW(), INTERVAL 60 DAY) WHERE ID = " . (int)$expiredId
        );

        $deleted = $dao->deleteOldConsents(30);

        $this->assertTrue($deleted);
        $this->assertSame(0, (int)self::$pdo->query(
            "SELECT COUNT(*) FROM tbl_consents WHERE ID = " . (int)$expiredId
        )->fetchColumn());
        $this->assertNotSame(0, (int)self::$pdo->query(
            "SELECT COUNT(*) FROM tbl_consents WHERE ID = " . (int)$freshId
        )->fetchColumn());
    }

    /**
     * PrivacyLogDao: deleteOldLogs() purges logs older than the retention
     * window but keeps logs that are still within it.
     *
     * @return void
     */
    public function testPrivacyLogDaoDeleteOldLogsPurgesOnlyExpired(): void
    {
        $dao = new PrivacyLogDao();
        $expiredId = $this->insertPrivacyLog($dao, 'data_request_created');
        $freshId = $this->insertPrivacyLog($dao, 'data_exported');

        self::$pdo->exec(
            "UPDATE tbl_privacy_logs SET log_date = DATE_SUB(NOW(), INTERVAL 60 DAY) WHERE ID = " . (int)$expiredId
        );

        $deleted = $dao->deleteOldLogs(30);

        $this->assertTrue($deleted);
        $this->assertFalse($dao->getLogById($expiredId));
        $this->assertNotFalse($dao->getLogById($freshId));
    }

    /**
     * CommentDao: findComments('ID', $email) returns only the comments authored
     * by the supplied email, never the whole table.
     *
     * @return void
     */
    public function testCommentDaoFindCommentsFiltersByEmail(): void
    {
        $commentIds = $this->seedCommentsForEmails([$this->testEmail, 'other-' . $this->testEmail]);

        $comments = (new CommentDao())->findComments('ID', $this->testEmail);

        $this->assertIsArray($comments);
        $this->assertCount(1, $comments);
        $this->assertSame($this->testEmail, $comments[0]['comment_author_email']);
        $this->assertContains((int)$comments[0]['ID'], $commentIds);
    }

    /**
     * DataRequestService: exportUserData() with export_comments enabled returns
     * only the requesting user's comments (filtered in SQL, not in PHP).
     *
     * @return void
     */
    public function testDataRequestServiceExportFiltersCommentsByEmail(): void
    {
        $service = $this->buildDataRequestService();

        $userId = $this->seedUser($this->testEmail);
        $postId = $this->seedPost();
        $ownCommentId = $this->seedComment($postId, $this->testEmail);
        $this->seedComment($postId, 'other-' . $this->testEmail);

        $export = $service->exportUserData($this->testEmail, ['export_comments' => true]);

        $this->assertIsArray($export);
        $this->assertIsArray($export['comments']);
        $this->assertCount(1, $export['comments']);
        $this->assertSame($this->testEmail, $export['comments'][0]['comment_author_email']);
        $this->assertSame((int)$ownCommentId, (int)$export['comments'][0]['ID']);
        $this->assertSame((int)$userId, (int)$export['user_id']);
    }

    /**
     * DataRequestService: exportUserData() for a user with no comments returns
     * an empty array for the comments key rather than a full-table result.
     *
     * @return void
     */
    public function testDataRequestServiceExportNoCommentsReturnsEmptyArray(): void
    {
        $service = $this->buildDataRequestService();

        $this->seedUser($this->testEmail);
        $postId = $this->seedPost();
        $this->seedComment($postId, 'other-' . $this->testEmail);

        $export = $service->exportUserData($this->testEmail, ['export_comments' => true]);

        $this->assertIsArray($export['comments']);
        $this->assertSame([], $export['comments']);
    }

    /**
     * DataRequestService: createRequest() persists a request AND an audit log
     * entry, then returns the request id.
     *
     * @return void
     */
    public function testDataRequestServiceCreateRequestPersistsRequestAndAuditLog(): void
    {
        $service = $this->buildDataRequestService();

        $id = $service->createRequest('access', $this->testEmail);
        $this->createdDataRequestIds[] = (int)$id;

        $request = (new DataRequestDao())->getRequestById((int)$id);
        $this->assertIsArray($request);
        $this->assertSame('pending', $request['request_status']);

        $logs = (new PrivacyLogDao())->getLogsByEmail($this->testEmail);
        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);
        $this->assertSame('data_request_created', $logs[0]['log_action']);
    }

    /**
     * DataRequestService: createRequest() rejects an invalid email with
     * AppException and persists nothing.
     *
     * @return void
     */
    public function testDataRequestServiceCreateRequestRejectsInvalidEmail(): void
    {
        $service = $this->buildDataRequestService();

        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Invalid email address');

        $service->createRequest('access', 'not-an-email');
    }

    /**
     * DataRequestService: exportUserData() for an unknown account returns an
     * export structure with an empty profile and writes a data_exported audit
     * log.
     *
     * @return void
     */
    public function testDataRequestServiceExportUserDataForUnknownUser(): void
    {
        $service = $this->buildDataRequestService();

        $export = $service->exportUserData($this->testEmail);

        $this->assertIsArray($export);
        $this->assertSame($this->testEmail, $export['email']);
        $this->assertSame([], $export['profile']);
        $this->assertArrayHasKey('comments', $export);
        $this->assertArrayHasKey('posts', $export);

        $logs = (new PrivacyLogDao())->getLogsByEmail($this->testEmail);
        $this->assertNotEmpty($logs);
        $this->assertSame('data_exported', $logs[0]['log_action']);
    }

    /**
     * DataRequestService: deleteUserData() raises AppException when no account
     * matches the email.
     *
     * @return void
     */
    public function testDataRequestServiceDeleteUserDataThrowsForUnknownUser(): void
    {
        $service = $this->buildDataRequestService();

        $this->expectException(AppException::class);
        $this->expectExceptionMessage('User not found');

        $service->deleteUserData($this->testEmail);
    }

    /**
     * UserService: removeUserWithAnonymization() anonymizes comments, reassigns
     * the erased author's posts to a validated fallback author and deletes the
     * account inside a single transaction.
     *
     * @return void
     */
    public function testRemoveUserWithAnonymizationIsTransactional(): void
    {
        $userId = $this->seedUser($this->testEmail);

        $postStmt = self::$pdo->prepare(
            "INSERT INTO tbl_posts (post_author, post_title, post_slug, post_content, post_status, post_type)
             VALUES (?, ?, ?, ?, 'publish', 'blog')"
        );
        $postSlug = 'erasure-' . uniqid('', false);
        $postStmt->execute([$userId, $postSlug, $postSlug, 'Erasure fixture']);
        $postId = (int)self::$pdo->lastInsertId();
        $this->createdPostIds[] = $postId;

        $commentId = $this->seedComment($postId, $this->testEmail);

        $service = new UserService(
            new UserDao(),
            new FormValidator(),
            new UserTokenDao(),
            new Sanitize(),
            new CommentDao(),
            new PostDao()
        );

        $result = $service->removeUserWithAnonymization($userId, $this->testEmail);

        $this->assertTrue($result);

        $userRow = self::$pdo->query("SELECT ID FROM tbl_users WHERE ID = " . (int)$userId)->fetch();
        $this->assertFalse($userRow, 'User account must be deleted');

        $postRow = self::$pdo->query("SELECT post_author FROM tbl_posts WHERE ID = " . (int)$postId)->fetch();
        $this->assertNotEquals($userId, (int)$postRow['post_author'], 'Posts must be reassigned away from the erased author');

        $commentRow = self::$pdo->query(
            "SELECT comment_author_email, comment_author_name FROM tbl_comments WHERE ID = " . (int)$commentId
        )->fetch();
        $this->assertSame('deleted@user.local', $commentRow['comment_author_email']);
        $this->assertSame('Deleted User', $commentRow['comment_author_name']);
    }

    /**
     * UserService: removeUserWithAnonymization() reassigns posts to a validated
     * fallback author that exists in tbl_users and is not the erased user.
     *
     * @return void
     */
    public function testRemoveUserWithAnonymizationUsesFallbackAuthorNotBeingErased(): void
    {
        $erasedId = $this->seedUser($this->testEmail);

        $postStmt = self::$pdo->prepare(
            "INSERT INTO tbl_posts (post_author, post_title, post_slug, post_content, post_status, post_type)
             VALUES (?, ?, ?, ?, 'publish', 'blog')"
        );
        $postSlug = 'fallback-' . uniqid('', false);
        $postStmt->execute([$erasedId, $postSlug, $postSlug, 'Fallback fixture']);
        $postId = (int)self::$pdo->lastInsertId();
        $this->createdPostIds[] = $postId;

        $service = new UserService(
            new UserDao(),
            new FormValidator(),
            new UserTokenDao(),
            new Sanitize(),
            new CommentDao(),
            new PostDao()
        );

        $result = $service->removeUserWithAnonymization($erasedId, $this->testEmail);

        $this->assertTrue($result);

        $postRow = self::$pdo->query("SELECT post_author FROM tbl_posts WHERE ID = " . (int)$postId)->fetch();
        $fallbackAuthorId = (int)$postRow['post_author'];
        $this->assertNotEquals($erasedId, $fallbackAuthorId, 'Post must not be reassigned to the erased author');

        $fallbackRow = self::$pdo->query("SELECT ID FROM tbl_users WHERE ID = " . $fallbackAuthorId)->fetch();
        $this->assertNotFalse($fallbackRow, 'Fallback author must still exist in tbl_users');
    }

    /**
     * Assert that the rows returned by a query are ordered by descending ID.
     *
     * @param array $rows
     * @return void
     */
    private function assertDescendingIds(array $rows): void
    {
        $ids = array_map(static function ($row) {
            return (int)$row['ID'];
        }, $rows);

        $sorted = $ids;
        rsort($sorted);

        $this->assertSame($sorted, $ids);
    }

    /**
     * Build a DataRequestService wired to the real DAOs with notifications
     * neutralised so no email is dispatched.
     *
     * @return DataRequestService
     */
    private function buildDataRequestService()
    {
        $service = new DataRequestService(
            new DataRequestDao(),
            new PrivacyLogDao(),
            new Sanitize(),
            null,
            null,
            new UserDao(),
            new CommentDao(),
            new PostDao()
        );

        $reflection = new ReflectionClass(DataRequestService::class);
        $property = $reflection->getProperty('notificationService');
        $property->setAccessible(true);
        $property->setValue($service, null);

        return $service;
    }
}
