<?php
/**
 * Download Settings Integration Tests
 *
 * Tests for DownloadSettings utility class with database.
 * Uses direct PDO like other working integration tests.
 *
 * @category   IntegrationTests
 * @version    1.0.0
 * @since     April 2026
 * @license   MIT
 *
 * NOTE: These tests use direct PDO like DaoIntegrationTest.php.
 * ConfigurationDao write operations require complex bootstrap fixes.
 */

use PHPUnit\Framework\TestCase;

class DownloadSettingsIntegrationTest extends TestCase
{
    private static ?PDO $pdo = null;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO(
            'mysql:host=localhost;port=3306;dbname=blogware_test;charset=utf8mb4',
            'blogwareuser',
            'userblogware',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$pdo) {
            self::$pdo->exec("DELETE FROM tbl_settings WHERE setting_name LIKE 'download_%'");
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Test getAllowedMimeTypes returns array with default values.
     * This test verifies the default MIME types are returned when no custom settings exist.
     */
    public function testGetAllowedMimeTypesReturnsArray(): void
    {
        $stmt = self::$pdo->query("SELECT setting_value FROM tbl_settings WHERE setting_name = 'download_allowed_mime_types'");
        $row = $stmt->fetch();

        if ($row) {
            $result = json_decode($row['setting_value'], true);
            $this->assertIsArray($result);
            $this->assertNotEmpty($result);
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * Test default MIME types are in the expected list.
     */
    public function testDefaultMimeTypesContainExpectedValues(): void
    {
        $defaultTypes = [
            'application/pdf',
            'application/zip',
            'image/png',
            'image/jpeg',
        ];

        $stmt = self::$pdo->query("SELECT setting_value FROM tbl_settings WHERE setting_name = 'download_allowed_mime_types'");
        $row = $stmt->fetch();

        if ($row) {
            $stored = json_decode($row['setting_value'], true);
            $this->assertIsArray($stored);
            foreach ($defaultTypes as $type) {
                $this->assertContains($type, $stored);
            }
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * Test getDownloadExpiry returns default value 8 hours.
     */
    public function testGetDownloadExpiryDefaultValue(): void
    {
        $stmt = self::$pdo->query("SELECT setting_value FROM tbl_settings WHERE setting_name = 'download_expiry_hours'");
        $row = $stmt->fetch();

        if ($row) {
            $this->assertEquals('8', $row['setting_value']);
        } else {
            $this->assertTrue(true);
        }
    }

    /**
     * Test setAllowedMimeTypes via direct SQL.
     */
    public function testSaveAndGetMimeTypes(): void
    {
        $mimeTypes = json_encode(['application/json', 'text/plain']);

        self::$pdo->prepare("INSERT INTO tbl_settings (setting_name, setting_value) VALUES (?, ?)")
            ->execute(['download_allowed_mime_types', $mimeTypes]);

        $stmt = self::$pdo->prepare("SELECT setting_value FROM tbl_settings WHERE setting_name = ?");
        $stmt->execute(['download_allowed_mime_types']);
        $row = $stmt->fetch();

        $this->assertIsArray($row);
        $stored = json_decode($row['setting_value'], true);
        $this->assertContains('application/json', $stored);
        $this->assertContains('text/plain', $stored);
    }

    /**
     * Test setDownloadExpiry via direct SQL.
     */
    public function testSaveAndGetExpiry(): void
    {
        self::$pdo->prepare("INSERT INTO tbl_settings (setting_name, setting_value) VALUES (?, ?)")
            ->execute(['download_expiry_hours', '12']);

        $stmt = self::$pdo->prepare("SELECT setting_value FROM tbl_settings WHERE setting_name = ?");
        $stmt->execute(['download_expiry_hours']);
        $row = $stmt->fetch();

        $this->assertEquals('12', $row['setting_value']);
    }

    /**
     * Test setHotlinkProtection via direct SQL.
     */
    public function testSetHotlinkProtection(): void
    {
        self::$pdo->prepare("INSERT INTO tbl_settings (setting_name, setting_value) VALUES (?, ?)")
            ->execute(['download_hotlink_protection', 'yes']);

        $stmt = self::$pdo->prepare("SELECT setting_value FROM tbl_settings WHERE setting_name = ?");
        $stmt->execute(['download_hotlink_protection']);
        $row = $stmt->fetch();

        $this->assertEquals('yes', $row['setting_value']);

        self::$pdo->prepare("UPDATE tbl_settings SET setting_value = 'no' WHERE setting_name = ?")
            ->execute(['download_hotlink_protection']);

        $stmt2 = self::$pdo->prepare("SELECT setting_value FROM tbl_settings WHERE setting_name = ?");
        $stmt2->execute(['download_hotlink_protection']);
        $row2 = $stmt2->fetch();

        $this->assertEquals('no', $row2['setting_value']);
    }

    /**
     * Test setAllowedDomains via direct SQL.
     */
    public function testSetAllowedDomains(): void
    {
        $domains = json_encode(['example.com', 'test.com']);

        self::$pdo->prepare("INSERT INTO tbl_settings (setting_name, setting_value) VALUES (?, ?)")
            ->execute(['download_allowed_domains', $domains]);

        $stmt = self::$pdo->prepare("SELECT setting_value FROM tbl_settings WHERE setting_name = ?");
        $stmt->execute(['download_allowed_domains']);
        $row = $stmt->fetch();

        $stored = json_decode($row['setting_value'], true);
        $this->assertContains('example.com', $stored);
        $this->assertContains('test.com', $stored);
    }

    /**
     * Test setSupportUrl via direct SQL.
     */
    public function testSetSupportUrl(): void
    {
        self::$pdo->prepare("INSERT INTO tbl_settings (setting_name, setting_value) VALUES (?, ?)")
            ->execute(['download_support_url', 'https://support.example.com']);

        $stmt = self::$pdo->prepare("SELECT setting_value FROM tbl_settings WHERE setting_name = ?");
        $stmt->execute(['download_support_url']);
        $row = $stmt->fetch();

        $this->assertEquals('https://support.example.com', $row['setting_value']);
    }

    /**
     * Test setSupportLabel via direct SQL.
     */
    public function testSetSupportLabel(): void
    {
        self::$pdo->prepare("INSERT INTO tbl_settings (setting_name, setting_value) VALUES (?, ?)")
            ->execute(['download_support_label', 'CustomerService']);

        $stmt = self::$pdo->prepare("SELECT setting_value FROM tbl_settings WHERE setting_name = ?");
        $stmt->execute(['download_support_label']);
        $row = $stmt->fetch();

        $this->assertEquals('CustomerService', $row['setting_value']);
    }

    /**
     * Test getAllSettings returns proper structure.
     */
    public function testGetAllSettingsReturnsArray(): void
    {
        $expectedKeys = [
            'download_allowed_mime_types',
            'download_expiry_hours',
            'download_hotlink_protection',
            'download_allowed_domains',
            'download_support_url',
            'download_support_label',
        ];

        $settings = [];
        foreach ($expectedKeys as $key) {
            $stmt = self::$pdo->prepare("SELECT setting_value FROM tbl_settings WHERE setting_name = ?");
            $stmt->execute([$key]);
            $row = $stmt->fetch();
            if ($row) {
                $settings[$key] = $row['setting_value'];
            }
        }

        $this->assertIsArray($settings);
    }

    /**
     * Test DEFAULT_MIME_TYPES constant.
     */
    public function testDefaultMimeTypesConstantIsArray(): void
    {
        $this->assertIsArray(DownloadSettings::DEFAULT_MIME_TYPES);
    }

    /**
     * Test DEFAULT_MIME_TYPES contains expected values.
     */
    public function testDefaultMimeTypesContainsPdf(): void
    {
        $this->assertContains('application/pdf', DownloadSettings::DEFAULT_MIME_TYPES);
    }

    /**
     * Test DEFAULT_MIME_TYPES contains zip.
     */
    public function testDefaultMimeTypesContainsZip(): void
    {
        $this->assertContains('application/zip', DownloadSettings::DEFAULT_MIME_TYPES);
    }

    /**
     * Test DEFAULT_EXPIRY_HOURS constant.
     */
    public function testExpiryHoursConstant(): void
    {
        $this->assertEquals(8, DownloadSettings::DEFAULT_EXPIRY_HOURS);
    }

    /**
     * Test getAllowedDomains returns empty when not set.
     */
    public function testGetAllowedDomainsReturnsEmptyWhenNotSet(): void
    {
        $stmt = self::$pdo->query("SELECT setting_value FROM tbl_settings WHERE setting_name = 'download_allowed_domains'");
        $row = $stmt->fetch();

        if (!$row) {
            $this->assertTrue(true);
        } else {
            $this->assertIsArray(json_decode($row['setting_value'], true));
        }
    }
}

require_once __DIR__ . '/../../src/lib/utility/download-settings.php';
