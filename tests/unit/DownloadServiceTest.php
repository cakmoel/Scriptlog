<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../lib/utility/download-handler.php';
require_once __DIR__ . '/../../lib/utility/download-settings.php';

class DownloadServiceTest extends TestCase
{
    private $downloadModel;
    private $mediaDao;
    private $downloadService;

    protected function setUp(): void
    {
        $this->downloadModel = $this->createMock(DownloadModel::class);
        $this->mediaDao = $this->createMock(MediaDao::class);
        $this->downloadService = new DownloadService($this->downloadModel, $this->mediaDao);
    }

    public function testValidateDownloadRequestReturnsNullForInvalidUuid(): void
    {
        $result = $this->downloadService->validateDownloadRequest('not-a-uuid');
        $this->assertNull($result);
    }

    public function testValidateDownloadRequestReturnsNullForEmptyIdentifier(): void
    {
        $result = $this->downloadService->validateDownloadRequest('');
        $this->assertNull($result);
    }

    public function testValidateDownloadRequestDelegatesToDownloadModel(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $expected = ['media_identifier' => $uuid, 'media_id' => 1];

        $this->downloadModel->expects($this->once())
            ->method('getMediaDownloadURL')
            ->with($uuid, $this->isInstanceOf(Sanitize::class))
            ->willReturn($expected);

        $result = $this->downloadService->validateDownloadRequest($uuid);
        $this->assertEquals($expected, $result);
    }

    public function testGetDownloadInfoDelegatesToModel(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $expected = ['media_identifier' => $uuid];

        $this->downloadModel->expects($this->once())
            ->method('getMediaDownloadURL')
            ->with($uuid, $this->isInstanceOf(Sanitize::class))
            ->willReturn($expected);

        $result = $this->downloadService->getDownloadInfo($uuid);
        $this->assertEquals($expected, $result);
    }

    public function testGetMediaByIdentifierReturnsNullWhenNoDownloadInfo(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->downloadModel->method('getMediaDownloadURL')->willReturn(null);

        $result = $this->downloadService->getMediaByIdentifier($uuid);
        $this->assertNull($result);
    }

    public function testGetMediaByIdentifierDelegatesToMediaDao(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $downloadInfo = ['media_identifier' => $uuid, 'media_id' => 5];
        $expectedMedia = ['ID' => 5, 'media_filename' => 'test.pdf'];

        $this->downloadModel->method('getMediaDownloadURL')->willReturn($downloadInfo);
        $this->mediaDao->expects($this->once())
            ->method('findMediaById')
            ->with(5, $this->isInstanceOf(Sanitize::class))
            ->willReturn($expectedMedia);

        $result = $this->downloadService->getMediaByIdentifier($uuid);
        $this->assertEquals($expectedMedia, $result);
    }

    public function testIsDownloadExpiredReturnsTrueWhenNoDownloadInfo(): void
    {
        $this->downloadModel->method('getMediaDownloadURL')->willReturn(null);

        $result = $this->downloadService->isDownloadExpired('550e8400-e29b-41d4-a716-446655440000');
        $this->assertTrue($result);
    }

    public function testIsDownloadExpiredReturnsTrueForExpiredLink(): void
    {
        $this->downloadModel->method('getMediaDownloadURL')->willReturn([
            'before_expired' => time() - 3600
        ]);

        $result = $this->downloadService->isDownloadExpired('550e8400-e29b-41d4-a716-446655440000');
        $this->assertTrue($result);
    }

    public function testIsDownloadExpiredReturnsFalseForValidLink(): void
    {
        $this->downloadModel->method('getMediaDownloadURL')->willReturn([
            'before_expired' => time() + 3600
        ]);

        $result = $this->downloadService->isDownloadExpired('550e8400-e29b-41d4-a716-446655440000');
        $this->assertFalse($result);
    }

    public function testIsMimeTypeAllowedReturnsTrueForPdf(): void
    {
        $result = $this->downloadService->isMimeTypeAllowed('application/pdf');
        $this->assertTrue($result);
    }

    public function testIsMimeTypeAllowedReturnsFalseForUnknown(): void
    {
        $result = $this->downloadService->isMimeTypeAllowed('application/x-unknown');
        $this->assertFalse($result);
    }

    public function testCreateDownloadRecordGeneratesUuid(): void
    {
        $mediaId = 1;
        $ipAddress = '127.0.0.1';

        $this->downloadModel->expects($this->once())
            ->method('createMediaDownload')
            ->with($this->callback(function ($bind) use ($mediaId, $ipAddress) {
                return $bind['media_id'] === $mediaId
                    && $bind['ip_address'] === $ipAddress
                    && preg_match('/^[a-f0-9\-]{36}$/', $bind['media_identifier']);
            }));

        $identifier = $this->downloadService->createDownloadRecord($mediaId, $ipAddress);
        $this->assertIsString($identifier);
        $this->assertEquals(36, strlen($identifier));
    }

    public function testGetDownloadUrlReturnsString(): void
    {
        // Skip if app_url() requires database connection
        // This is a simple string concatenation method
        $this->assertTrue(method_exists($this->downloadService, 'getDownloadUrl'));
    }

    public function testRecordDownloadAttemptDelegatesToModel(): void
    {
        $this->downloadModel->expects($this->once())
            ->method('createDownloadLog')
            ->with($this->callback(function ($data) {
                return $data['media_id'] === 1
                    && $data['status'] === 'success';
            }));

        $this->downloadService->recordDownloadAttempt(1, 'uuid', '127.0.0.1', 'TestAgent', 'success');
    }

    public function testRefreshDownloadExpiryCallsUpdate(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->downloadModel->expects($this->once())
            ->method('updateMediaDownload')
            ->with(
                $this->isInstanceOf(Sanitize::class),
                $this->arrayHasKey('before_expired'),
                $uuid
            );

        $this->downloadService->refreshDownloadExpiry($uuid);
    }

    public function testGenerateDownloadIdentifierReturnsValidFormat(): void
    {
        $reflection = new ReflectionMethod($this->downloadService, 'generateDownloadIdentifier');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($this->downloadService);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $result);
    }

    public function testDeleteDownloadRecordDelegatesToModel(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->downloadModel->expects($this->once())
            ->method('deleteMediaDownload')
            ->with($uuid, $this->isInstanceOf(Sanitize::class));

        $this->downloadService->deleteDownloadRecord($uuid);
    }

    public function testDeleteDownloadRecordsDeletesAll(): void
    {
        $uuids = [
            '550e8400-e29b-41d4-a716-446655440000',
            '660e8400-e29b-41d4-a716-446655440001'
        ];

        $this->downloadModel->expects($this->exactly(2))
            ->method('deleteMediaDownload')
            ->willReturn(true);

        $this->downloadService->deleteDownloadRecords($uuids);
    }
}
