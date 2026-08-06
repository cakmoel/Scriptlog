<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * Shared post card partial.
 *
 * Renders a single post card (thumbnail + meta + title + excerpt) used
 * by the home latest-posts grid and the category / blog / tag / archive
 * listings. It is the single code path for grid cards so the markup is
 * identical everywhere.
 *
 * Expected variables:
 *  - $post        PostViewModel  already-safe, normalized post (see
 *                                prepare_post_card()); never a raw array
 *  - $card_class  string         column wrapper classes (default 'col-xl-6');
 *                                escaped here, never pre-escaped by callers
 *
 * $post->content() and $post->topics() are trusted, sanitized HTML and
 * are printed as-is; all other fields are already escaped at the
 * normalization boundary.
 *
 * @category Theme Partial
 * @package  Scriptlog
 * @author   Theme Remediation
 * @license  MIT
 * @version  1.0
 */

$post = isset($post) && $post instanceof \Scriptlog\Core\Theme\PostViewModel ? $post : PostViewModel::fromPrepared([]);
$card_class = isset($card_class) ? $card_class : 'col-xl-6';

$card_id = $post->id() !== null ? (int)$post->id() : 0;
$card_title = $post->title() ?? '';
$card_url = $post->url() !== '' ? $post->url() : '#';
$card_content = $post->content() ?? '';
$card_img = $post->media() ?? '';
$card_img_caption = $post->mediaCaption() ?? $card_title;
$card_date = $post->date() ?? '';
?>
<div class="post <?= theme_escape_html($card_class); ?>">
    <div class="post-thumbnail">
        <a href="<?= $card_url; ?>" title="<?= $card_title; ?>">
            <?php if (!empty($card_img)) : ?>
                <?= invoke_responsive_image($card_img, 'thumbnail', true, $card_img_caption, 'img-fluid', false, 'lazy', 'lazy'); ?>
            <?php else : ?>
                <img src="<?= theme_dir(); ?>assets/img/placeholder.svg" alt="" width="640" height="450" class="img-fluid" loading="lazy" decoding="async">
            <?php endif; ?>
        </a>
    </div>
    <div class="post-details">
        <div class="post-meta d-flex justify-content-between">
            <div class="date meta-last"><?= $card_date; ?></div>
            <div class="category"><?= $post->topics() ?? ''; ?></div>
        </div>
        <a href="<?= $card_url; ?>" title="<?= $card_title; ?>">
            <h3 class="h4"><?= $card_title; ?></h3>
        </a>
        <p class="text-muted"><?= $card_content; ?></p>
        <?php
        $meta_author = $post->author() ?? '';
        $meta_date = $card_date;
        $meta_comments = $post->comments() !== null ? (int)$post->comments() : 0;
        include __DIR__ . '/meta.php';
        ?>
    </div>
</div>
