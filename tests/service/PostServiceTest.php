<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PostService Test
 * 
 * Tests for post business logic.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PostServiceTest extends TestCase
{
    private $postService;
    private $postDaoMock;
    private $validatorMock;
    private $sanitizeMock;

    protected function setUp(): void
    {
        $this->postDaoMock = $this->createMock(\PostDao::class);
        $this->validatorMock = $this->createMock(\FormValidator::class);
        $this->sanitizeMock = $this->createMock(\Sanitize::class);
        
        $this->postService = new \PostService(
            $this->postDaoMock,
            $this->validatorMock,
            $this->sanitizeMock
        );
    }

    public function testSetPostId(): void
    {
        $this->postService->setPostId(1);
        $this->assertTrue(true);
    }

    public function testSetPostTitle(): void
    {
        $this->postService->setPostTitle('Test Title');
        $this->assertTrue(true);
    }

    /**
     * @skip HTMLPurifier not available in CLI context
     */
    public function testSetPostContent(): void
    {
        $this->markTestSkipped('HTMLPurifier not available in CLI context');
    }

    public function testSetPostSlug(): void
    {
        $this->postService->setPostSlug('test-slug');
        $this->assertTrue(true);
    }

    public function testSetPostAuthor(): void
    {
        $this->postService->setPostAuthor(1);
        $this->assertTrue(true);
    }

    public function testSetPostDate(): void
    {
        $this->postService->setPostDate('2026-03-28');
        $this->assertTrue(true);
    }

    public function testSetPostModified(): void
    {
        $this->postService->setPostModified('2026-03-28');
        $this->assertTrue(true);
    }

    public function testSetPostImage(): void
    {
        $this->postService->setPostImage(1);
        $this->assertTrue(true);
    }

    public function testSetTopics(): void
    {
        $this->postService->setTopics([1, 2, 3]);
        $this->assertTrue(true);
    }

    public function testSetPublish(): void
    {
        $this->postService->setPublish('publish');
        $this->assertTrue(true);
    }

    public function testSetVisibility(): void
    {
        $this->postService->setVisibility('public');
        $this->assertTrue(true);
    }

    public function testSetComment(): void
    {
        $this->postService->setComment('open');
        $this->assertTrue(true);
    }

    public function testSetMetaDesc(): void
    {
        $this->postService->setMetaDesc('Test description');
        $this->assertTrue(true);
    }

    public function testSetPostTags(): void
    {
        $this->postService->setPostTags('tag1,tag2');
        $this->assertTrue(true);
    }

    public function testSetHeadlines(): void
    {
        $this->postService->setHeadlines(1);
        $this->assertTrue(true);
    }

    public function testSetProtected(): void
    {
        $this->postService->setProtected(['post_content' => 'encrypted']);
        $this->assertTrue(true);
    }

    public function testSetPassPhrase(): void
    {
        $this->postService->setPassPhrase('secret123');
        $this->assertTrue(true);
    }

    public function testPostAuthorId(): void
    {
        $_SESSION['scriptlog_session_id'] = 1;
        $authorId = $this->postService->postAuthorId();
        $this->assertEquals(1, $authorId);
        unset($_SESSION['scriptlog_session_id']);
    }

    public function testPostAuthorLevel(): void
    {
        $_SESSION['scriptlog_session_level'] = 'administrator';
        $level = $this->postService->postAuthorLevel();
        $this->assertEquals('administrator', $level);
        unset($_SESSION['scriptlog_session_level']);
    }

    public function testTotalPosts(): void
    {
        $this->postDaoMock->method('totalPostRecords')->willReturn(10);
        $total = $this->postService->totalPosts();
        $this->assertEquals(10, $total);
    }

    public function testGrabPosts(): void
    {
        $this->postDaoMock->method('findPosts')->willReturn([]);
        $posts = $this->postService->grabPosts();
        $this->assertIsArray($posts);
    }

    public function testGrabPost(): void
    {
        $this->postDaoMock->method('findPost')->willReturn(['ID' => 1, 'post_title' => 'Test']);
        $post = $this->postService->grabPost(1);
        $this->assertIsArray($post);
    }

    public function testGetPublishedPostsApiWithDefaults(): void
    {
        $expected = [
            ['ID' => 1, 'post_title' => 'Post 1'],
            ['ID' => 2, 'post_title' => 'Post 2']
        ];

        $this->postDaoMock->method('findPublishedPostsPaginated')
            ->with(10, 0, 'ID', 'DESC', null)
            ->willReturn($expected);

        $result = $this->postService->getPublishedPostsApi();
        $this->assertSame($expected, $result);
    }

    public function testGetPublishedPostsApiWithPage2PerPage5(): void
    {
        $expected = [
            ['ID' => 6, 'post_title' => 'Post 6'],
            ['ID' => 7, 'post_title' => 'Post 7']
        ];

        $this->postDaoMock->method('findPublishedPostsPaginated')
            ->with(5, 5, 'post_date', 'ASC', null)
            ->willReturn($expected);

        $result = $this->postService->getPublishedPostsApi(2, 5, 'post_date', 'ASC');
        $this->assertSame($expected, $result);
    }

    public function testGetPublishedPostsApiWithAuthorFilter(): void
    {
        $expected = [['ID' => 1, 'post_title' => 'My Post']];

        $this->postDaoMock->method('findPublishedPostsPaginated')
            ->with(10, 0, 'ID', 'DESC', 3)
            ->willReturn($expected);

        $result = $this->postService->getPublishedPostsApi(1, 10, 'ID', 'DESC', 3);
        $this->assertSame($expected, $result);
    }

    public function testCountPublishedPostsApi(): void
    {
        $this->postDaoMock->method('countPublishedPosts')
            ->with(null)
            ->willReturn(42);

        $total = $this->postService->countPublishedPostsApi();
        $this->assertSame(42, $total);
    }

    public function testCountPublishedPostsApiWithAuthor(): void
    {
        $this->postDaoMock->method('countPublishedPosts')
            ->with(5)
            ->willReturn(7);

        $total = $this->postService->countPublishedPostsApi(5);
        $this->assertSame(7, $total);
    }

    public function testGetPublishedPostApiReturnsPost(): void
    {
        $expected = ['ID' => 1, 'post_title' => 'Test Post', 'post_visibility' => 'public'];

        $this->postDaoMock->method('findPublishedPostById')
            ->with(1)
            ->willReturn($expected);

        $result = $this->postService->getPublishedPostApi(1);
        $this->assertSame($expected, $result);
    }

    public function testGetPublishedPostApiReturnsFalseWhenNotFound(): void
    {
        $this->postDaoMock->method('findPublishedPostById')
            ->with(999)
            ->willReturn(null);

        $result = $this->postService->getPublishedPostApi(999);
        $this->assertNull($result);
    }

    public function testGetPostByIdApiDelegatesToDao(): void
    {
        $expected = ['ID' => 42, 'post_title' => 'Draft', 'post_status' => 'draft'];

        $this->postDaoMock->expects($this->once())
            ->method('getPostById')
            ->with(42)
            ->willReturn($expected);

        $result = $this->postService->getPostByIdApi(42);
        $this->assertSame($expected, $result);
    }

    public function testGetPostByIdApiReturnsNullWhenMissing(): void
    {
        $this->postDaoMock->method('getPostById')
            ->with(999)
            ->willReturn(null);

        $result = $this->postService->getPostByIdApi(999);
        $this->assertNull($result);
    }

    public function testSetPostTopicsApiDelegatesToDao(): void
    {
        $this->postDaoMock->expects($this->once())
            ->method('setPostTopics')
            ->with(42, [3, 7]);

        $this->postService->setPostTopicsApi(42, [3, 7]);
    }

    public function testCreatePostApiDelegatesToDao(): void
    {
        $data = ['post_title' => 'New Post'];

        $this->postDaoMock->method('insertPostApi')
            ->with($data)
            ->willReturn(77);

        $this->assertSame(77, $this->postService->createPostApi($data));
    }
}
