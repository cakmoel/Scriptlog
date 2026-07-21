<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class ExportServiceTest extends TestCase
{
    private $exportService;

    protected function setUp(): void
    {
        $this->exportService = new \Scriptlog\Service\ExportService();
    }

    public function testSetAuthorId(): void
    {
        $this->exportService->setAuthorId(5);
        $this->assertTrue(true);
    }

    public function testResetStatsClearsCounters(): void
    {
        $this->exportService->resetStats();
        $stats = $this->exportService->getStats();
        $this->assertEquals(0, $stats['posts_exported']);
        $this->assertEquals(0, $stats['pages_exported']);
        $this->assertEquals(0, $stats['categories_exported']);
        $this->assertEquals(0, $stats['comments_exported']);
    }

    public function testGetStatsReturnsArrayWithKeys(): void
    {
        $stats = $this->exportService->getStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('posts_exported', $stats);
        $this->assertArrayHasKey('pages_exported', $stats);
        $this->assertArrayHasKey('categories_exported', $stats);
        $this->assertArrayHasKey('comments_exported', $stats);
    }

    public function testExportToWordPressReturnStructure(): void
    {
        $this->markTestSkipped('Requires real DB connection for exporter classes');
    }

    public function testExportToGhostReturnStructure(): void
    {
        $this->markTestSkipped('Requires real DB connection for exporter classes');
    }

    public function testExportToBlogspotReturnStructure(): void
    {
        $this->markTestSkipped('Requires real DB connection for exporter classes');
    }

    public function testExportToScriptlogReturnStructure(): void
    {
        $this->markTestSkipped('Requires real DB connection for exporter classes');
    }

    public function testExportFilenameFormatEachDestination(): void
    {
        $this->markTestSkipped('Requires real DB connection for exporter classes');
    }

    public function testExporterConstantsAreDefined(): void
    {
        $this->assertEquals('wordpress', \Scriptlog\Service\ExportService::DESTINATION_WORDPRESS);
        $this->assertEquals('ghost', \Scriptlog\Service\ExportService::DESTINATION_GHOST);
        $this->assertEquals('blogspot', \Scriptlog\Service\ExportService::DESTINATION_BLOGSPOT);
        $this->assertEquals('scriptlog', \Scriptlog\Service\ExportService::DESTINATION_SCRIPTLOG);
    }
}
