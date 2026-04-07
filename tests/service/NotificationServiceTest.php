<?php

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Class NotificationServiceTest
 *
 * Tests for NotificationService class.
 *
 * @covers NotificationService
 */
class NotificationServiceTest extends TestCase
{
    private $configServiceMock;

    public static function setUpBeforeClass(): void
    {
        // Ensure constants required by the class are defined
        if (!defined('SCRIPTLOG')) {
            define('SCRIPTLOG', 'test');
        }
        if (!defined('APP_ROOT')) {
            define('APP_ROOT', sys_get_temp_dir());
        }
    }

    protected function setUp(): void
    {
        $this->configServiceMock = $this->createMock(ConfigurationService::class);
    }

    /**
     * Test that configuration is loaded correctly from ConfigurationService
     */
    public function testConfigurationLoadingFromService()
    {
        // Setup config mock to return specific SMTP settings
        $this->configServiceMock->method('grabSettingByName')
            ->willReturnMap([
                ['smtp_host', ['setting_value' => 'smtp.test.com']],
                ['smtp_port', ['setting_value' => '2525']],
                ['smtp_encryption', ['setting_value' => 'ssl']],
                ['smtp_username', ['setting_value' => 'user']],
                ['smtp_password', ['setting_value' => 'pass']],
                ['smtp_from_email', ['setting_value' => 'test@test.com']],
                ['smtp_from_name', ['setting_value' => 'Test Sender']],
            ]);

        $service = new NotificationService($this->configServiceMock);

        // Access private property via reflection to verify DSN
        $reflection = new ReflectionClass(NotificationService::class);
        $dsnProp = $reflection->getProperty('dsn');
        $dsnProp->setAccessible(true);
        $dsn = $dsnProp->getValue($service);

        $expectedDsn = 'smtp://user:pass@smtp.test.com:2525?encryption=ssl';
        $this->assertEquals($expectedDsn, $dsn);
    }

    /**
     * Test that configuration falls back to config.php defaults when no DB settings
     */
    public function testConfigurationDefaultsWithoutConfigService()
    {
        $service = new NotificationService(null);

        $reflection = new ReflectionClass(NotificationService::class);
        $dsnProp = $reflection->getProperty('dsn');
        $dsnProp->setAccessible(true);
        $dsn = $dsnProp->getValue($service);

        $this->assertStringContainsString('smtp://', $dsn);
    }

    /**
     * Test send() method success scenario
     */
    public function testSendSuccess()
    {
        $to = 'recipient@example.com';
        $subject = 'Test Subject';
        $body = '<p>Test Body</p>';

        $emailMock = $this->createMock(Email::class);

        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->once())
            ->method('send')
            ->with($emailMock);

        $service = $this->getMockBuilder(NotificationService::class)
            ->setConstructorArgs([$this->configServiceMock])
            ->onlyMethods(['getMailer', 'createEmail'])
            ->getMock();

        $service->method('getMailer')->willReturn($mailerMock);
        $service->method('createEmail')->willReturn($emailMock);

        $result = $service->send($to, $subject, $body);
        $this->assertTrue($result, 'Send should return true on success');
    }

    /**
     * Test send() with plain text body
     */
    public function testSendPlainText()
    {
        $emailMock = $this->createMock(Email::class);

        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->once())->method('send');

        $service = $this->getMockBuilder(NotificationService::class)
            ->setConstructorArgs([$this->configServiceMock])
            ->onlyMethods(['getMailer', 'createEmail'])
            ->getMock();

        $service->method('getMailer')->willReturn($mailerMock);
        $service->method('createEmail')->willReturn($emailMock);

        $result = $service->send('to@example.com', 'Subject', 'Body', ['is_html' => false]);
        $this->assertTrue($result);
    }

    /**
     * Test send() with Cc, Bcc, and Reply-To options
     */
    public function testSendWithOptions()
    {
        $emailMock = $this->createMock(Email::class);

        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->once())->method('send');

        $service = $this->getMockBuilder(NotificationService::class)
            ->setConstructorArgs([$this->configServiceMock])
            ->onlyMethods(['getMailer', 'createEmail'])
            ->getMock();

        $service->method('getMailer')->willReturn($mailerMock);
        $service->method('createEmail')->willReturn($emailMock);

        $result = $service->send('to@example.com', 'Subject', 'Body', [
            'Cc' => ['cc@example.com'],
            'Bcc' => ['bcc@example.com'],
            'reply_to' => 'reply@example.com',
        ]);
        $this->assertTrue($result);
    }

    /**
     * Test send() method handles exceptions gracefully
     */
    public function testSendFailure()
    {
        $emailMock = $this->createMock(Email::class);

        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->method('send')
            ->willThrowException(new \Exception('SMTP Error'));

        $service = $this->getMockBuilder(NotificationService::class)
            ->setConstructorArgs([$this->configServiceMock])
            ->onlyMethods(['getMailer', 'createEmail'])
            ->getMock();

        $service->method('getMailer')->willReturn($mailerMock);
        $service->method('createEmail')->willReturn($emailMock);

        $result = $service->send('test@example.com', 'Subject', 'Body');
        $this->assertFalse($result, 'Send should return false on exception');
    }

    /**
     * Test sendDataRequestConfirmation constructs correct message
     */
    public function testSendDataRequestConfirmation()
    {
        $email = 'user@example.com';
        $type = 'export';
        $id = 'REQ-123';

        $service = $this->getMockBuilder(NotificationService::class)
            ->setConstructorArgs([$this->configServiceMock])
            ->onlyMethods(['send'])
            ->getMock();

        $service->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo($email),
                $this->stringContains('Data Export Request Received'),
                $this->stringContains($id)
            )
            ->willReturn(true);

        $result = $service->sendDataRequestConfirmation($email, $type, $id);
        $this->assertTrue($result);
    }

    /**
     * Test sendDataRequestConfirmation for deletion type
     */
    public function testSendDataRequestConfirmationDeletion()
    {
        $email = 'user@example.com';
        $type = 'deletion';
        $id = 'REQ-456';

        $service = $this->getMockBuilder(NotificationService::class)
            ->setConstructorArgs([$this->configServiceMock])
            ->onlyMethods(['send'])
            ->getMock();

        $service->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo($email),
                $this->stringContains('Data Deletion Request Received'),
                $this->stringContains($id)
            )
            ->willReturn(true);

        $result = $service->sendDataRequestConfirmation($email, $type, $id);
        $this->assertTrue($result);
    }

    /**
     * Test sendAdminNotification
     */
    public function testSendAdminNotification()
    {
        $adminEmail = 'admin@example.com';
        $userEmail = 'user@example.com';
        $type = 'deletion';
        $id = 'REQ-999';

        $service = $this->getMockBuilder(NotificationService::class)
            ->setConstructorArgs([$this->configServiceMock])
            ->onlyMethods(['send'])
            ->getMock();

        $service->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo($adminEmail),
                $this->stringContains('New GDPR Data Request'),
                $this->logicalAnd(
                    $this->stringContains($userEmail),
                    $this->stringContains($type)
                )
            )
            ->willReturn(true);

        $result = $service->sendAdminNotification($adminEmail, $userEmail, $type, $id);
        $this->assertTrue($result);
    }

    /**
     * Test sendRequestCompleted for export type
     */
    public function testSendRequestCompletedExport()
    {
        $email = 'user@example.com';
        $type = 'export';

        $service = $this->getMockBuilder(NotificationService::class)
            ->setConstructorArgs([$this->configServiceMock])
            ->onlyMethods(['send'])
            ->getMock();

        $service->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo($email),
                $this->stringContains('Your Data Export Request Has Been Processed'),
                $this->stringContains('download')
            )
            ->willReturn(true);

        $result = $service->sendRequestCompleted($email, $type);
        $this->assertTrue($result);
    }

    /**
     * Test sendRequestCompleted for deletion type
     */
    public function testSendRequestCompletedDeletion()
    {
        $email = 'user@example.com';
        $type = 'deletion';

        $service = $this->getMockBuilder(NotificationService::class)
            ->setConstructorArgs([$this->configServiceMock])
            ->onlyMethods(['send'])
            ->getMock();

        $service->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo($email),
                $this->stringContains('Your Data Deletion Request Has Been Processed'),
                $this->stringContains('anonymized')
            )
            ->willReturn(true);

        $result = $service->sendRequestCompleted($email, $type);
        $this->assertTrue($result);
    }

    /**
     * Test sendProfileDeletionConfirmation
     */
    public function testSendProfileDeletionConfirmation()
    {
        $email = 'user@example.com';

        $service = $this->getMockBuilder(NotificationService::class)
            ->setConstructorArgs([$this->configServiceMock])
            ->onlyMethods(['send'])
            ->getMock();

        $service->expects($this->once())
            ->method('send')
            ->with(
                $this->equalTo($email),
                $this->stringContains('Profile Deletion Confirmation'),
                $this->stringContains('permanently deleted')
            )
            ->willReturn(true);

        $result = $service->sendProfileDeletionConfirmation($email);
        $this->assertTrue($result);
    }

    /**
     * Test getSmtpConfig returns array
     */
    public function testGetSmtpConfigReturnsArray()
    {
        $service = new NotificationService($this->configServiceMock);
        $config = $service->getSmtpConfig();
        $this->assertIsArray($config);
    }
}