<?php

use PHPUnit\Framework\TestCase;

class SearchFinderTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../lib/core/Registry.php';
        require_once __DIR__ . '/../../lib/core/SearchFinder.php';
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('SearchFinder'));
    }

    public function testConstructorWorks(): void
    {
        $finder = new SearchFinder();
        $this->assertInstanceOf(SearchFinder::class, $finder);
    }

    public function testGetErrorReturnsNullInitially(): void
    {
        $finder = new SearchFinder();
        $this->assertNull($finder->getError());
    }

    public function testSanitizeKeywordRejectsNonString(): void
    {
        $finder = new SearchFinder();
        $this->assertEquals('', $finder->sanitizeKeyword(null));
        $this->assertEquals('', $finder->sanitizeKeyword(123));
        $this->assertEquals('', $finder->sanitizeKeyword([]));
        $this->assertEquals('', $finder->sanitizeKeyword(false));
    }

    public function testSanitizeKeywordTrimsWhitespace(): void
    {
        $finder = new SearchFinder();
        $this->assertEquals('hello', $finder->sanitizeKeyword('  hello  '));
    }

    public function testSanitizeKeywordRejectsShortStrings(): void
    {
        $finder = new SearchFinder();
        $this->assertEquals('', $finder->sanitizeKeyword(''));
        $this->assertEquals('', $finder->sanitizeKeyword('a'));
        $this->assertEquals('', $finder->sanitizeKeyword(' '));
    }

    public function testSanitizeKeywordAcceptsValidKeyword(): void
    {
        $finder = new SearchFinder();
        $this->assertEquals('hello', $finder->sanitizeKeyword('hello'));
        $this->assertEquals('test keyword', $finder->sanitizeKeyword('test keyword'));
    }

    public function testSanitizeKeywordTruncatesLongStrings(): void
    {
        $finder = new SearchFinder();
        $longString = str_repeat('a', 200);
        $result = $finder->sanitizeKeyword($longString);
        $this->assertEquals(100, mb_strlen($result, 'UTF-8'));
    }

    public function testSanitizeKeywordStripsFulltextOperators(): void
    {
        $finder = new SearchFinder();
        $this->assertEquals('php tutorial', $finder->sanitizeKeyword('+php +tutorial'));
        $this->assertEquals('hello world', $finder->sanitizeKeyword('-hello +world'));
        $this->assertEquals('test', $finder->sanitizeKeyword('>test'));
        $this->assertEquals('foo bar', $finder->sanitizeKeyword('<foo ~bar'));
        $this->assertEquals('word', $finder->sanitizeKeyword('(word)'));
        $this->assertEquals('wild', $finder->sanitizeKeyword('wild*'));
        $this->assertEquals('exact phrase', $finder->sanitizeKeyword('"exact phrase"'));
        $this->assertEquals('term', $finder->sanitizeKeyword('term@'));
    }

    public function testSanitizeKeywordReturnsEmptyWhenOnlyOperators(): void
    {
        $finder = new SearchFinder();
        $this->assertEquals('', $finder->sanitizeKeyword('+++--'));
        $this->assertEquals('', $finder->sanitizeKeyword('><()~*'));
        $this->assertEquals('', $finder->sanitizeKeyword('@":'));
    }

    public function testSanitizeKeywordKeepsMixedOperatorsAndText(): void
    {
        $finder = new SearchFinder();
        $result = $finder->sanitizeKeyword('+php -mysql "exact phrase"');
        $this->assertEquals('php mysql exact phrase', $result);
        $this->assertStringNotContainsString('+', $result);
        $this->assertStringNotContainsString('"', $result);
        $this->assertStringNotContainsString('-', $result);
    }

    public function testSearchPostAcceptsPageAndPerPageParams(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchPost('php', 2, 5);
        $this->assertIsArray($result);
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(5, $result['perPage']);
        $this->assertEquals(0, $result['totalRows']);
        $this->assertEquals([], $result['results']);
    }

    public function testSearchPostReturnsPaginationMetadata(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchPost('', 1, 10);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('perPage', $result);
        $this->assertArrayHasKey('totalPages', $result);
        $this->assertArrayHasKey('keyword', $result);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['perPage']);
        $this->assertEquals(0, $result['totalPages']);
    }

    public function testSearchPostDefaultsToPage1PerPage10(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchPost('php');
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['perPage']);
    }

    public function testSearchPostReturnsKeyword(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchPost('php');
        $this->assertArrayHasKey('keyword', $result);
        $this->assertEquals('php', $result['keyword']);
    }

    public function testSearchPostSanitizesKeywordBeforeSearch(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchPost('+php +tutorial');
        $this->assertArrayHasKey('keyword', $result);
        $this->assertEquals('php tutorial', $result['keyword']);
        $this->assertStringNotContainsString('+', $result['keyword']);
    }

    public function testSearchPageAcceptsPageAndPerPageParams(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchPage('test', 3, 20);
        $this->assertIsArray($result);
        $this->assertEquals(3, $result['page']);
        $this->assertEquals(20, $result['perPage']);
        $this->assertEquals('test', $result['keyword']);
    }

    public function testSearchPageSanitizesKeyword(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchPage('"hello" -world');
        $this->assertEquals('hello world', $result['keyword']);
    }

    public function testSearchAllAcceptsPageAndPerPageParams(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchAll('keyword', 1, 15);
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(15, $result['perPage']);
        $this->assertEquals('keyword', $result['keyword']);
    }

    public function testSearchAllSanitizesKeyword(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchAll('>test (foo) @bar');
        $this->assertEquals('test foo bar', $result['keyword']);
    }

    public function testSearchPostReturnsEmptyWithoutKeyword(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchPost('');
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['totalRows']);
        $this->assertEquals([], $result['results']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['perPage']);
    }

    public function testSearchPageReturnsEmptyWithoutKeyword(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchPage('');
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['totalRows']);
        $this->assertEquals([], $result['results']);
    }

    public function testSearchAllReturnsEmptyWithoutKeyword(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchAll('');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('results', $result);
        $this->assertArrayHasKey('totalRows', $result);
    }

    public function testSearchAllReturnsEmptyWithShortKeyword(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchAll('x');
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['totalRows']);
    }

    public function testSearchAllReturnsEmptyWithOnlyOperators(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchAll('+++--');
        $this->assertEquals(0, $result['totalRows']);
        $this->assertEquals([], $result['results']);
    }

    public function testSearchAllReturnsPaginationDefaults(): void
    {
        $finder = new SearchFinder();
        $result = $finder->searchAll('test');
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(10, $result['perPage']);
    }
}
