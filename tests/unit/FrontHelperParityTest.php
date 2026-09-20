<?php

use PHPUnit\Framework\TestCase;

/**
 * Behavior-parity test between the deprecated FrontHelper facade and the
 * FrontService service it delegates to.
 *
 * Verifies that every FrontHelper::* static returns exactly what the matching
 * FrontService method returns, and that the facade fails safe (null / empty
 * array) when no service has been registered.
 *
 * @category Tests
 * @version  1.0
 * @since    1.0
 */
class FrontHelperParityTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../lib/service/FrontService.php';
        require_once __DIR__ . '/../../lib/core/FrontHelper.php';
    }

    protected function tearDown(): void
    {
        FrontHelper::setFrontService(null);
    }

    public function testGrabSimpleFrontPostDelegatesToGetSimplePost(): void
    {
        $expected = ['ID' => 1, 'type' => 'post'];
        $service = $this->createMock(FrontService::class);
        $service->method('getSimplePost')->willReturn($expected);
        FrontHelper::setFrontService($service);
        $this->assertSame($expected, FrontHelper::grabSimpleFrontPost(1));
    }

    public function testGrabSimpleFrontTopicDelegatesToGetSimpleTopic(): void
    {
        $expected = ['ID' => 2, 'type' => 'topic'];
        $service = $this->createMock(FrontService::class);
        $service->method('getSimpleTopic')->willReturn($expected);
        FrontHelper::setFrontService($service);
        $this->assertSame($expected, FrontHelper::grabSimpleFrontTopic(2));
    }

    public function testGrabSimpleFrontArchiveDelegatesToGetSimpleArchive(): void
    {
        $expected = [['year_archive' => 2026, 'month_archive' => 8]];
        $service = $this->createMock(FrontService::class);
        $service->method('getSimpleArchive')->willReturn($expected);
        FrontHelper::setFrontService($service);
        $this->assertSame($expected, FrontHelper::grabSimpleFrontArchive());
    }

    public function testGrabSimpleFrontPageDelegatesToGetSimplePage(): void
    {
        $expected = ['ID' => 3, 'type' => 'page'];
        $service = $this->createMock(FrontService::class);
        $service->method('getSimplePage')->willReturn($expected);
        FrontHelper::setFrontService($service);
        $this->assertSame($expected, FrontHelper::grabSimpleFrontPage(3));
    }

    public function testSimpleSearchingTagDelegatesToSearchTag(): void
    {
        $expected = ['ID' => 4, 'type' => 'tag'];
        $service = $this->createMock(FrontService::class);
        $service->method('searchTag')->willReturn($expected);
        FrontHelper::setFrontService($service);
        $this->assertSame($expected, FrontHelper::simpleSearchingTag('php'));
    }

    public function testGrabTagListsDelegatesToGetTagLists(): void
    {
        $expected = ['php', 'mysql'];
        $service = $this->createMock(FrontService::class);
        $service->method('getTagLists')->willReturn($expected);
        FrontHelper::setFrontService($service);
        $this->assertSame($expected, FrontHelper::grabTagLists());
    }

    public function testGrabPreparedFrontPostByIdDelegatesToGetPublishedPost(): void
    {
        $expected = ['ID' => 5, 'type' => 'published-post'];
        $service = $this->createMock(FrontService::class);
        $service->method('getPublishedPost')->willReturn($expected);
        FrontHelper::setFrontService($service);
        $this->assertSame($expected, FrontHelper::grabPreparedFrontPostById(5));
    }

    public function testGrabPreparedFrontPageBySlugDelegatesToGetPublishedPage(): void
    {
        $expected = ['ID' => 6, 'type' => 'published-page'];
        $service = $this->createMock(FrontService::class);
        $service->method('getPublishedPage')->willReturn($expected);
        FrontHelper::setFrontService($service);
        $this->assertSame($expected, FrontHelper::grabPreparedFrontPageBySlug('about'));
    }

    public function testGrabPreparedFrontTopicBySlugDelegatesToGetPublishedTopic(): void
    {
        $expected = ['ID' => 7, 'type' => 'published-topic'];
        $service = $this->createMock(FrontService::class);
        $service->method('getPublishedTopic')->willReturn($expected);
        FrontHelper::setFrontService($service);
        $this->assertSame($expected, FrontHelper::grabPreparedFrontTopicBySlug('news'));
    }

    public function testGrabPreparedFrontTopicByIdDelegatesToGetPublishedTopicById(): void
    {
        $expected = ['ID' => 8, 'type' => 'published-topic-id'];
        $service = $this->createMock(FrontService::class);
        $service->method('getPublishedTopicById')->willReturn($expected);
        FrontHelper::setFrontService($service);
        $this->assertSame($expected, FrontHelper::grabPreparedFrontTopicByID(8));
    }

    public function testGrabPreparedFrontArchiveDelegatesToGetArchivePosts(): void
    {
        $expected = ['ID' => 9, 'type' => 'archive'];
        $service = $this->createMock(FrontService::class);
        $service->method('getArchivePosts')->willReturn($expected);
        FrontHelper::setFrontService($service);
        $this->assertSame(
            $expected,
            FrontHelper::grabPreparedFrontArchive(['month' => 8, 'year' => 2026])
        );
    }

    public function testGrabPreparedFrontGalleriesDelegatesToGetGalleries(): void
    {
        $expected = ['media_id' => 10, 'type' => 'gallery'];
        $service = $this->createMock(FrontService::class);
        $service->method('getGalleries')->willReturn($expected);
        FrontHelper::setFrontService($service);
        $this->assertSame($expected, FrontHelper::grabPreparedFrontGalleries(0, 5));
    }

    public function testSimpleSearchingTagShortCircuitsOnEmptyTag(): void
    {
        $service = $this->createMock(FrontService::class);
        $service->expects($this->never())->method('searchTag');
        FrontHelper::setFrontService($service);
        $this->assertSame([], FrontHelper::simpleSearchingTag(''));
    }

    public function testFacadeFailsSafeWithoutRegisteredService(): void
    {
        FrontHelper::setFrontService(null);
        $this->assertNull(FrontHelper::grabSimpleFrontPost(1));
        $this->assertNull(FrontHelper::grabSimpleFrontTopic(1));
        $this->assertNull(FrontHelper::grabSimpleFrontPage(1));
        $this->assertNull(FrontHelper::grabPreparedFrontPostById(1));
        $this->assertNull(FrontHelper::grabPreparedFrontPageBySlug('about'));
        $this->assertNull(FrontHelper::grabPreparedFrontTopicBySlug('news'));
        $this->assertNull(FrontHelper::grabPreparedFrontTopicByID(1));
        $this->assertNull(FrontHelper::grabPreparedFrontArchive(['month' => 8, 'year' => 2026]));
        $this->assertNull(FrontHelper::grabPreparedFrontGalleries(0, 5));
        $this->assertSame([], FrontHelper::grabSimpleFrontArchive());
        $this->assertSame([], FrontHelper::grabTagLists());
        $this->assertSame([], FrontHelper::simpleSearchingTag('php'));
    }
}
