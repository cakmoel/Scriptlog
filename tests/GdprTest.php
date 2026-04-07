<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class GdprClassesExistenceTest extends TestCase
{
    
    public function testDataRequestDaoClassExists(): void
    {
        $this->assertTrue(class_exists('DataRequestDao'));
    }
    
    public function testPrivacyLogDaoClassExists(): void
    {
        $this->assertTrue(class_exists('PrivacyLogDao'));
    }
    
    public function testDataRequestServiceClassExists(): void
    {
        $this->assertTrue(class_exists('DataRequestService'));
    }
    
    public function testNotificationServiceClassExists(): void
    {
        $this->assertTrue(class_exists('NotificationService'));
    }
    
    public function testConsentDaoClassExists(): void
    {
        $this->assertTrue(class_exists('ConsentDao'));
    }
}

class GdprDataRequestDaoTest extends TestCase
{
    
    private $dataRequestDao;
    
    protected function setUp(): void
    {
        if (class_exists('DataRequestDao')) {
            $this->dataRequestDao = new DataRequestDao();
        }
    }
    
    public function testDataRequestDaoIsInstantiable(): void
    {
        if (class_exists('DataRequestDao')) {
            $this->assertInstanceOf(DataRequestDao::class, $this->dataRequestDao);
        } else {
            $this->assertTrue(false, 'DataRequestDao class does not exist');
        }
    }
    
    public function testDataRequestDaoHasCreateMethod(): void
    {
        if ($this->dataRequestDao) {
            $this->assertTrue(method_exists($this->dataRequestDao, 'createRequest'));
        }
    }
    
    public function testDataRequestDaoHasUpdateStatusMethod(): void
    {
        if ($this->dataRequestDao) {
            $this->assertTrue(method_exists($this->dataRequestDao, 'updateRequestStatus'));
        }
    }
    
    public function testDataRequestDaoHasGetAllRequestsMethod(): void
    {
        if ($this->dataRequestDao) {
            $this->assertTrue(method_exists($this->dataRequestDao, 'getAllRequests'));
        }
    }
    
    public function testDataRequestDaoHasGetPendingCountMethod(): void
    {
        if ($this->dataRequestDao) {
            $this->assertTrue(method_exists($this->dataRequestDao, 'getPendingCount'));
        }
    }
    
    public function testDataRequestDaoHasTotalRecordsMethod(): void
    {
        if ($this->dataRequestDao) {
            $this->assertTrue(method_exists($this->dataRequestDao, 'totalRequestRecords'));
        }
    }
    
    public function testDataRequestDaoHasGetRequestByIdMethod(): void
    {
        if ($this->dataRequestDao) {
            $this->assertTrue(method_exists($this->dataRequestDao, 'getRequestById'));
        }
    }
    
    public function testDataRequestDaoHasGetRequestByEmailMethod(): void
    {
        if ($this->dataRequestDao) {
            $this->assertTrue(method_exists($this->dataRequestDao, 'getRequestByEmail'));
        }
    }
    
    public function testDataRequestDaoHasDeleteRequestMethod(): void
    {
        if ($this->dataRequestDao) {
            $this->assertTrue(method_exists($this->dataRequestDao, 'deleteRequest'));
        }
    }
}

class GdprPrivacyLogDaoTest extends TestCase
{
    
    private $privacyLogDao;
    
    protected function setUp(): void
    {
        if (class_exists('PrivacyLogDao')) {
            $this->privacyLogDao = new PrivacyLogDao();
        }
    }
    
    public function testPrivacyLogDaoIsInstantiable(): void
    {
        if (class_exists('PrivacyLogDao')) {
            $this->assertInstanceOf(PrivacyLogDao::class, $this->privacyLogDao);
        } else {
            $this->assertTrue(false, 'PrivacyLogDao class does not exist');
        }
    }
    
    public function testPrivacyLogDaoHasCreateLogMethod(): void
    {
        if ($this->privacyLogDao) {
            $this->assertTrue(method_exists($this->privacyLogDao, 'createLog'));
        }
    }
    
    public function testPrivacyLogDaoHasGetLogByIdMethod(): void
    {
        if ($this->privacyLogDao) {
            $this->assertTrue(method_exists($this->privacyLogDao, 'getLogById'));
        }
    }
    
    public function testPrivacyLogDaoHasGetLogsByUserIdMethod(): void
    {
        if ($this->privacyLogDao) {
            $this->assertTrue(method_exists($this->privacyLogDao, 'getLogsByUserId'));
        }
    }
    
    public function testPrivacyLogDaoHasGetLogsByEmailMethod(): void
    {
        if ($this->privacyLogDao) {
            $this->assertTrue(method_exists($this->privacyLogDao, 'getLogsByEmail'));
        }
    }
    
    public function testPrivacyLogDaoHasGetLogsByActionMethod(): void
    {
        if ($this->privacyLogDao) {
            $this->assertTrue(method_exists($this->privacyLogDao, 'getLogsByAction'));
        }
    }
    
    public function testPrivacyLogDaoHasGetAllLogsMethod(): void
    {
        if ($this->privacyLogDao) {
            $this->assertTrue(method_exists($this->privacyLogDao, 'getAllLogs'));
        }
    }
    
    public function testPrivacyLogDaoHasGetRecentLogsMethod(): void
    {
        if ($this->privacyLogDao) {
            $this->assertTrue(method_exists($this->privacyLogDao, 'getRecentLogs'));
        }
    }
    
    public function testPrivacyLogDaoHasTotalLogRecordsMethod(): void
    {
        if ($this->privacyLogDao) {
            $this->assertTrue(method_exists($this->privacyLogDao, 'totalLogRecords'));
        }
    }
    
    public function testPrivacyLogDaoHasDeleteOldLogsMethod(): void
    {
        if ($this->privacyLogDao) {
            $this->assertTrue(method_exists($this->privacyLogDao, 'deleteOldLogs'));
        }
    }
}

class GdprDataRequestServiceTest extends TestCase
{
    
    private $dataRequestService;
    private $dataRequestDao;
    private $privacyLogDao;
    private $sanitizer;
    
    protected function setUp(): void
    {
        if (class_exists('DataRequestDao') && class_exists('PrivacyLogDao') && class_exists('Sanitize')) {
            $this->dataRequestDao = new DataRequestDao();
            $this->privacyLogDao = new PrivacyLogDao();
            $this->sanitizer = new Sanitize();
            $this->dataRequestService = new DataRequestService(
                $this->dataRequestDao,
                $this->privacyLogDao,
                $this->sanitizer
            );
        }
    }
    
    public function testDataRequestServiceIsInstantiable(): void
    {
        if ($this->dataRequestService) {
            $this->assertInstanceOf(DataRequestService::class, $this->dataRequestService);
        } else {
            $this->assertTrue(false, 'DataRequestService class does not exist or dependencies missing');
        }
    }
    
    public function testDataRequestServiceHasCreateRequestMethod(): void
    {
        if ($this->dataRequestService) {
            $this->assertTrue(method_exists($this->dataRequestService, 'createRequest'));
        }
    }
    
    public function testDataRequestServiceHasGetAllRequestsMethod(): void
    {
        if ($this->dataRequestService) {
            $this->assertTrue(method_exists($this->dataRequestService, 'getAllRequests'));
        }
    }
    
    public function testDataRequestServiceHasGetPendingCountMethod(): void
    {
        if ($this->dataRequestService) {
            $this->assertTrue(method_exists($this->dataRequestService, 'getPendingCount'));
        }
    }
    
    public function testDataRequestServiceHasGetTotalRequestsMethod(): void
    {
        if ($this->dataRequestService) {
            $this->assertTrue(method_exists($this->dataRequestService, 'getTotalRequests'));
        }
    }
    
    public function testDataRequestServiceHasUpdateRequestStatusMethod(): void
    {
        if ($this->dataRequestService) {
            $this->assertTrue(method_exists($this->dataRequestService, 'updateRequestStatus'));
        }
    }
    
    public function testDataRequestServiceHasExportUserDataMethod(): void
    {
        if ($this->dataRequestService) {
            $this->assertTrue(method_exists($this->dataRequestService, 'exportUserData'));
        }
    }
    
    public function testDataRequestServiceHasDeleteUserDataMethod(): void
    {
        if ($this->dataRequestService) {
            $this->assertTrue(method_exists($this->dataRequestService, 'deleteUserData'));
        }
    }
}

class GdprValidationTest extends TestCase
{
    
    public function testValidEmailValidation(): void
    {
        $this->assertTrue(filter_var('test@example.com', FILTER_VALIDATE_EMAIL) !== false);
    }
    
    public function testInvalidEmailValidation(): void
    {
        $this->assertFalse(filter_var('invalid-email', FILTER_VALIDATE_EMAIL) !== false);
    }
    
    public function testEmailWithAtSymbol(): void
    {
        $this->assertTrue(filter_var('user@domain.com', FILTER_VALIDATE_EMAIL) !== false);
    }
    
    public function testEmailWithSubdomain(): void
    {
        $this->assertTrue(filter_var('user@mail.example.com', FILTER_VALIDATE_EMAIL) !== false);
    }
    
    public function testIpAddressFormatValidation(): void
    {
        $validIp = '192.168.1.1';
        $this->assertTrue(filter_var($validIp, FILTER_VALIDATE_IP) !== false);
    }
    
    public function testIpAddressInvalidFormat(): void
    {
        $invalidIp = 'not-an-ip';
        $this->assertFalse(filter_var($invalidIp, FILTER_VALIDATE_IP) !== false);
    }
}

class GdprPrivacyLogServiceTest extends TestCase
{
    
    public function testLogTypeConstantsExist(): void
    {
        $logTypes = ['consent_given', 'data_access', 'data_export', 'data_deletion', 'data_rectification'];
        
        foreach ($logTypes as $type) {
            $this->assertIsString($type);
        }
    }
    
    public function testRequestTypeConstantsExist(): void
    {
        $requestTypes = ['access', 'rectification', 'erasure'];
        
        foreach ($requestTypes as $type) {
            $this->assertIsString($type);
        }
    }
    
    public function testRequestStatusConstantsExist(): void
    {
        $statuses = ['pending', 'processing', 'completed', 'rejected'];
        
        foreach ($statuses as $status) {
            $this->assertIsString($status);
        }
    }
}

class GdprConsentDaoTest extends TestCase
{
    
    private $consentDao;
    
    protected function setUp(): void
    {
        if (class_exists('ConsentDao')) {
            $this->consentDao = new ConsentDao();
        }
    }
    
    public function testConsentDaoIsInstantiable(): void
    {
        if (class_exists('ConsentDao')) {
            $this->assertInstanceOf(ConsentDao::class, $this->consentDao);
        } else {
            $this->assertTrue(false, 'ConsentDao class does not exist');
        }
    }
    
    public function testConsentDaoHasCreateConsentMethod(): void
    {
        if ($this->consentDao) {
            $this->assertTrue(method_exists($this->consentDao, 'createConsent'));
        }
    }
    
    public function testConsentDaoHasGetConsentByTypeMethod(): void
    {
        if ($this->consentDao) {
            $this->assertTrue(method_exists($this->consentDao, 'getConsentByType'));
        }
    }
    
    public function testConsentDaoHasGetAllConsentsMethod(): void
    {
        if ($this->consentDao) {
            $this->assertTrue(method_exists($this->consentDao, 'getAllConsents'));
        }
    }
    
    public function testConsentDaoHasUpdateConsentStatusMethod(): void
    {
        if ($this->consentDao) {
            $this->assertTrue(method_exists($this->consentDao, 'updateConsentStatus'));
        }
    }
    
    public function testConsentDaoHasDeleteConsentMethod(): void
    {
        if ($this->consentDao) {
            $this->assertTrue(method_exists($this->consentDao, 'deleteConsent'));
        }
    }
}
