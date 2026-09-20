<?php
/**
 * ThemePostCardTest
 *
 * Unit tests for the shared post card pipeline introduced in the Phase 6
 * theme remediation: the prepare_post_card() normalization helper and the
 * blog/partials/card.php (plus meta.php) rendering path.
 *
 * Rows are crafted with ID=0 and empty topics_data so no database-backed
 * fallback (permalinks(), total_comment(), retrieves_topic_simple(),
 * format_topics()) is invoked - those paths are DB-bound and covered by
 * integration tests instead.
 *
 * @category Tests
 * @package  Scriptlog
 * @version  1.0
 */

use PHPUnit\Framework\TestCase;

if (!defined('HTMLPURIFIER_PREFIX')) {
    define('HTMLPURIFIER_PREFIX', dirname(__DIR__, 2) . '/lib/core');
}

require_once __DIR__ . '/../../public/themes/blog/functions.php';

class ThemePostCardTest extends TestCase
{
    /**
     * A fully-populated row (DB-free: ID=0, empty topics_data).
     *
     * @return array<string, mixed>
     */
    private function makeRow(array $overrides = []): array
    {
        $row = [
            'ID'             => 0,
            'post_title'     => 'Hello <World>',
            'post_content'   => "Line one.\nLine two & more",
            'media_filename' => 'hero.jpg',
            'media_caption'  => '',
            'created_at'     => '2024-03-01 10:00:00',
            'modified_at'    => '2024-03-02 10:00:00',
            'user_login'     => 'admin',
            'user_fullname'  => 'Admin User',
            'total_comments' => 7,
            'topics_data'    => '',
            'url'            => '/post/5/hello-world',
        ];

        return array_replace($row, $overrides);
    }

    public function testPreparePostCardEscapesTitleAndNormalizesFields(): void
    {
        $card = prepare_post_card($this->makeRow());

        $this->assertInstanceOf(PostViewModel::class, $card);
        $this->assertSame(0, (int)$card->id());
        $this->assertSame('Hello &lt;World&gt;', $card->title());
        $this->assertSame('/post/5/hello-world', $card->url());
        $this->assertSame('admin', $card->author());
        $this->assertSame(7, (int)$card->comments());
        $this->assertSame('', $card->topics());
        $this->assertSame('hero.jpg', $card->media());
        $this->assertStringContainsString('&amp;', $card->content());
    }

    public function testPreparePostCardFormatsDateFromModifiedAt(): void
    {
        $card = prepare_post_card($this->makeRow());

        $this->assertSame('March 02, 2024', $card->date());
    }

    public function testPreparePostCardFallsBackToCreatedAtWhenModifiedMissing(): void
    {
        $card = prepare_post_card($this->makeRow(['modified_at' => '']));

        $this->assertSame('March 01, 2024', $card->date());
    }

    public function testPreparePostCardPrefersUserFullnameWhenLoginMissing(): void
    {
        $card = prepare_post_card($this->makeRow(['user_login' => '']));

        $this->assertSame('Admin User', $card->author());
    }

    public function testPreparePostCardUsesMediaCaptionFallingBackToTitle(): void
    {
        $withCaption = prepare_post_card($this->makeRow(['media_caption' => 'The hero']));
        $this->assertSame('The hero', $withCaption->mediaCaption());

        $withoutCaption = prepare_post_card($this->makeRow());
        $this->assertSame('Hello &lt;World&gt;', $withoutCaption->mediaCaption());
    }

    public function testPreparePostCardHandlesMissingContentAndComments(): void
    {
        $card = prepare_post_card($this->makeRow(['post_content' => '', 'total_comments' => 0]));

        $this->assertSame('', $card->content());
        $this->assertSame(0, (int)$card->comments());
    }

    public function testPreparePostCardFallsBackToHashWithoutUrlOrId(): void
    {
        $card = prepare_post_card($this->makeRow(['url' => '', 'ID' => 0]));

        $this->assertSame('#', $card->url());
    }

    public function testCardPartialRendersGridCardWithMetaFooter(): void
    {
        $card = prepare_post_card($this->makeRow());

        $post = $card;
        $card_class = 'col-xl-6';

        ob_start();
        include dirname(__DIR__, 2) . '/public/themes/blog/partials/card.php';
        $html = ob_get_clean();

        $this->assertStringContainsString('class="post col-xl-6"', $html);
        $this->assertStringContainsString('Hello &lt;World&gt;', $html);
        $this->assertStringContainsString('post-footer', $html);
        $this->assertStringContainsString('icon-comment', $html);
        $this->assertStringContainsString('>7<', $html);
        $this->assertStringContainsString('fa-user-circle', $html);
    }

    public function testCardPartialRendersHomeGridColumnClass(): void
    {
        $post = prepare_post_card($this->makeRow());
        $card_class = 'col-md-4';

        ob_start();
        include dirname(__DIR__, 2) . '/public/themes/blog/partials/card.php';
        $html = ob_get_clean();

        $this->assertStringContainsString('class="post col-md-4"', $html);
    }
}
