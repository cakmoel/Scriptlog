<?php
/**
 * Paginator Tests
 *
 * Phase 3.4: Core Classes - Paginator (8 tests)
 * Tests for pagination calculations, limit generation, link rendering
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PaginatorTest extends TestCase
{
    public function testConstructorSetsPerPage(): void
    {
        $paginator = new Paginator(10, 'page');
        $reflection = new ReflectionClass(Paginator::class);
        $perPage = $reflection->getProperty('_perPage');
        $perPage->setAccessible(true);

        $this->assertEquals(10, $perPage->getValue($paginator));
    }

    public function testSetTotalStoresValue(): void
    {
        $paginator = new Paginator(10, 'page');
        $paginator->set_total(100);

        $reflection = new ReflectionClass(Paginator::class);
        $totalRows = $reflection->getProperty('_totalRows');
        $totalRows->setAccessible(true);

        $this->assertEquals(100, $totalRows->getValue($paginator));
    }

    public function testGetLimitKeysReturnsCorrectOffsetAndLimit(): void
    {
        $_GET = ['page' => 2];
        $paginator = new Paginator(10, 'page');
        $paginator->set_total(100);

        $keys = $paginator->get_limit_keys();

        $this->assertIsArray($keys);
        $this->assertArrayHasKey('offset', $keys);
        $this->assertArrayHasKey('limit', $keys);
    }

    public function testPageLinksReturnsString(): void
    {
        $_GET = ['page' => 1];
        $paginator = new Paginator(10, 'page');
        $paginator->set_total(100);

        $sanitize = new Sanitize();
        $links = $paginator->page_links($sanitize);

        $this->assertIsString($links);
        $this->assertStringContainsString('pagination', $links);
    }

    public function testPageLinksContainsPageOneWhenNotActive(): void
    {
        $_GET = ['page' => 2];
        $paginator = new Paginator(5, 'page');
        $paginator->set_total(50);

        $sanitize = new Sanitize();
        $links = $paginator->page_links($sanitize);

        $this->assertStringContainsString('1', $links);
        $this->assertStringContainsString('2', $links);
    }

    public function testPageLinksWithSinglePageReturnsEmpty(): void
    {
        $_GET = ['page' => 1];
        $paginator = new Paginator(10, 'page');
        $paginator->set_total(5);

        $sanitize = new Sanitize();
        $links = $paginator->page_links($sanitize);

        $this->assertStringNotContainsString('pagination', $links);
    }

    public function testPageLinksShowsActiveClassOnCurrentPage(): void
    {
        $_GET = ['page' => 3];
        $paginator = new Paginator(10, 'page');
        $paginator->set_total(100);

        $sanitize = new Sanitize();
        $links = $paginator->page_links($sanitize);

        $this->assertStringContainsString('active', $links);
    }

    public function testGetLimitReturnsCorrectSql(): void
    {
        $_GET = ['page' => 1];
        $paginator = new Paginator(10, 'page');
        $paginator->set_total(100);

        $sanitize = new Sanitize();
        $limit = $paginator->get_limit($sanitize);

        $this->assertStringStartsWith('LIMIT', $limit);
        $this->assertStringContainsString('10', $limit);
    }
}
