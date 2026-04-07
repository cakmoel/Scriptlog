<?php
/**
 * Integration Tests for GDPR Data Requests and Privacy Logs
 * 
 * Tests database operations for GDPR compliance
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class GdprDataRequestIntegrationTest extends TestCase
{
    private static $pdo;
    private static $requestId;
    private static $testEmail = 'gdpr-test@example.com';
    private static $testIp = '127.0.0.1';
    
    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO(
            'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
            'blogwareuser',
            'userblogware',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        // Check if table exists
        $tables = self::$pdo->query("SHOW TABLES LIKE 'tbl_data_requests'")->fetchAll();
        if (empty($tables)) {
            self::$pdo->exec("
                CREATE TABLE IF NOT EXISTS tbl_data_requests (
                    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    request_type VARCHAR(50) NOT NULL,
                    request_email VARCHAR(100) NOT NULL,
                    request_ip VARCHAR(45) NOT NULL,
                    request_note TEXT,
                    request_status VARCHAR(20) DEFAULT 'pending',
                    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    request_completed_date DATETIME DEFAULT NULL,
                    PRIMARY KEY (ID),
                    KEY request_email (request_email),
                    KEY request_status (request_status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo && self::$requestId) {
            self::$pdo->exec("DELETE FROM tbl_data_requests WHERE ID = " . self::$requestId);
        }
    }
    
    public function testInsertDataRequest(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_data_requests (request_type, request_email, request_ip, request_note, request_status)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            'access',
            self::$testEmail,
            self::$testIp,
            'Test request for GDPR',
            'pending'
        ]);
        
        $this->assertTrue($result);
        
        self::$requestId = self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$requestId);
    }
    
    public function testSelectDataRequestById(): void
    {
        if (!self::$requestId) {
            $this->testInsertDataRequest();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_data_requests WHERE ID = ?");
        $stmt->execute([self::$requestId]);
        $request = $stmt->fetch();
        
        $this->assertIsArray($request);
        $this->assertEquals(self::$testEmail, $request['request_email']);
        $this->assertEquals('access', $request['request_type']);
        $this->assertEquals('pending', $request['request_status']);
    }
    
    public function testSelectDataRequestByEmail(): void
    {
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_data_requests WHERE request_email = ?");
        $stmt->execute([self::$testEmail]);
        $requests = $stmt->fetchAll();
        
        $this->assertIsArray($requests);
        $this->assertNotEmpty($requests);
    }
    
    public function testUpdateDataRequestStatus(): void
    {
        if (!self::$requestId) {
            $this->testInsertDataRequest();
        }
        
        $stmt = self::$pdo->prepare("
            UPDATE tbl_data_requests 
            SET request_status = ?, request_completed_date = NOW() 
            WHERE ID = ?
        ");
        
        $result = $stmt->execute(['completed', self::$requestId]);
        
        $this->assertTrue($result);
        
        // Verify update
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_data_requests WHERE ID = ?");
        $stmt->execute([self::$requestId]);
        $request = $stmt->fetch();
        
        $this->assertEquals('completed', $request['request_status']);
    }
    
    public function testSelectDataRequestsByStatus(): void
    {
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_data_requests WHERE request_status = ?");
        $stmt->execute(['pending']);
        $requests = $stmt->fetchAll();
        
        $this->assertIsArray($requests);
    }
    
    public function testCountDataRequests(): void
    {
        $stmt = self::$pdo->query("SELECT COUNT(*) as total FROM tbl_data_requests");
        $result = $stmt->fetch();
        
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['total']);
    }
    
    public function testDeleteDataRequest(): void
    {
        if (!self::$requestId) {
            $this->testInsertDataRequest();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_data_requests WHERE ID = ?");
        $result = $stmt->execute([self::$requestId]);
        
        $this->assertTrue($result);
        self::$requestId = null;
    }
}

class GdprPrivacyLogIntegrationTest extends TestCase
{
    private static $pdo;
    private static $logId;
    private static $testEmail = 'gdpr-log-test@example.com';
    private static $testIp = '127.0.0.1';
    private static $testUserId = 1;
    
    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO(
            'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
            'blogwareuser',
            'userblogware',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        // Check if table exists
        $tables = self::$pdo->query("SHOW TABLES LIKE 'tbl_privacy_logs'")->fetchAll();
        if (empty($tables)) {
            self::$pdo->exec("
                CREATE TABLE IF NOT EXISTS tbl_privacy_logs (
                    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    log_action VARCHAR(50) NOT NULL,
                    log_type VARCHAR(50) NOT NULL,
                    log_user_id BIGINT(20) DEFAULT NULL,
                    log_email VARCHAR(100) DEFAULT NULL,
                    log_details TEXT,
                    log_ip VARCHAR(45) DEFAULT NULL,
                    log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    KEY log_user_id (log_user_id),
                    KEY log_email (log_email),
                    KEY log_action (log_action)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo && self::$logId) {
            self::$pdo->exec("DELETE FROM tbl_privacy_logs WHERE ID = " . self::$logId);
        }
    }
    
    public function testInsertPrivacyLog(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_privacy_logs (log_action, log_type, log_user_id, log_email, log_details, log_ip)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            'consent_given',
            'consent',
            self::$testUserId,
            self::$testEmail,
            'User gave consent for cookies',
            self::$testIp
        ]);
        
        $this->assertTrue($result);
        
        self::$logId = self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$logId);
    }
    
    public function testSelectPrivacyLogById(): void
    {
        if (!self::$logId) {
            $this->testInsertPrivacyLog();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_privacy_logs WHERE ID = ?");
        $stmt->execute([self::$logId]);
        $log = $stmt->fetch();
        
        $this->assertIsArray($log);
        $this->assertEquals(self::$testEmail, $log['log_email']);
        $this->assertEquals('consent_given', $log['log_action']);
    }
    
    public function testSelectPrivacyLogsByUserId(): void
    {
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_privacy_logs WHERE log_user_id = ?");
        $stmt->execute([self::$testUserId]);
        $logs = $stmt->fetchAll();
        
        $this->assertIsArray($logs);
    }
    
    public function testSelectPrivacyLogsByEmail(): void
    {
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_privacy_logs WHERE log_email = ?");
        $stmt->execute([self::$testEmail]);
        $logs = $stmt->fetchAll();
        
        $this->assertIsArray($logs);
        $this->assertNotEmpty($logs);
    }
    
    public function testSelectPrivacyLogsByAction(): void
    {
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_privacy_logs WHERE log_action = ?");
        $stmt->execute(['consent_given']);
        $logs = $stmt->fetchAll();
        
        $this->assertIsArray($logs);
    }
    
    public function testSelectAllPrivacyLogs(): void
    {
        $stmt = self::$pdo->query("SELECT * FROM tbl_privacy_logs ORDER BY log_date DESC");
        $logs = $stmt->fetchAll();
        
        $this->assertIsArray($logs);
    }
    
    public function testCountPrivacyLogs(): void
    {
        $stmt = self::$pdo->query("SELECT COUNT(*) as total FROM tbl_privacy_logs");
        $result = $stmt->fetch();
        
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['total']);
    }
    
    public function testDeletePrivacyLog(): void
    {
        if (!self::$logId) {
            $this->testInsertPrivacyLog();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_privacy_logs WHERE ID = ?");
        $result = $stmt->execute([self::$logId]);
        
        $this->assertTrue($result);
        self::$logId = null;
    }
}

class GdprConsentIntegrationTest extends TestCase
{
    private static $pdo;
    private static $consentId;
    private static $testIp = '127.0.0.1';
    private static $testUserAgent = 'Mozilla/5.0 (Test)';
    
    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO(
            'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
            'blogwareuser',
            'userblogware',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        // Check if table exists
        $tables = self::$pdo->query("SHOW TABLES LIKE 'tbl_consents'")->fetchAll();
        if (empty($tables)) {
            self::$pdo->exec("
                CREATE TABLE IF NOT EXISTS tbl_consents (
                    ID BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    consent_type VARCHAR(50) NOT NULL,
                    consent_status ENUM('accepted','rejected') NOT NULL,
                    consent_ip VARCHAR(45) NOT NULL,
                    consent_user_agent VARCHAR(255) DEFAULT NULL,
                    consent_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    consent_updated TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (ID),
                    KEY consent_type(consent_type),
                    KEY consent_date(consent_date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
    }
    
    public static function tearDownAfterClass(): void
    {
        if (self::$pdo && self::$consentId) {
            self::$pdo->exec("DELETE FROM tbl_consents WHERE ID = " . self::$consentId);
        }
    }
    
    public function testInsertConsent(): void
    {
        $stmt = self::$pdo->prepare("
            INSERT INTO tbl_consents (consent_type, consent_status, consent_ip, consent_user_agent)
            VALUES (?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            'cookies',
            'accepted',
            self::$testIp,
            self::$testUserAgent
        ]);
        
        $this->assertTrue($result);
        
        self::$consentId = self::$pdo->lastInsertId();
        $this->assertGreaterThan(0, self::$consentId);
    }
    
    public function testSelectConsentById(): void
    {
        if (!self::$consentId) {
            $this->testInsertConsent();
        }
        
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_consents WHERE ID = ?");
        $stmt->execute([self::$consentId]);
        $consent = $stmt->fetch();
        
        $this->assertIsArray($consent);
        $this->assertEquals('cookies', $consent['consent_type']);
        $this->assertEquals('accepted', $consent['consent_status']);
    }
    
    public function testSelectConsentsByType(): void
    {
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_consents WHERE consent_type = ?");
        $stmt->execute(['cookies']);
        $consents = $stmt->fetchAll();
        
        $this->assertIsArray($consents);
    }
    
    public function testUpdateConsentStatus(): void
    {
        if (!self::$consentId) {
            $this->testInsertConsent();
        }
        
        $stmt = self::$pdo->prepare("
            UPDATE tbl_consents 
            SET consent_status = ? 
            WHERE ID = ?
        ");
        
        $result = $stmt->execute(['rejected', self::$consentId]);
        
        $this->assertTrue($result);
        
        // Verify update
        $stmt = self::$pdo->prepare("SELECT * FROM tbl_consents WHERE ID = ?");
        $stmt->execute([self::$consentId]);
        $consent = $stmt->fetch();
        
        $this->assertEquals('rejected', $consent['consent_status']);
    }
    
    public function testCountConsents(): void
    {
        $stmt = self::$pdo->query("SELECT COUNT(*) as total FROM tbl_consents");
        $result = $stmt->fetch();
        
        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['total']);
    }
    
    public function testDeleteConsent(): void
    {
        if (!self::$consentId) {
            $this->testInsertConsent();
        }
        
        $stmt = self::$pdo->prepare("DELETE FROM tbl_consents WHERE ID = ?");
        $result = $stmt->execute([self::$consentId]);
        
        $this->assertTrue($result);
        self::$consentId = null;
    }
}
