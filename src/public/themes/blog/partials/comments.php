<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * Shared comments section partial.
 *
 * Renders the comment list container (feed injected via JS) and the
 * "load more" control. It is the single source of truth for the comment
 * section markup; render_comments_section() includes it through the
 * ob_start()/ob_get_clean() capture in functions-comments.php.
 *
 * Expected variables:
 *  - $post_id         int  post id (data attributes)
 *  - $comment_limit   int  comments loaded per AJAX batch
 *  - $total_records   int  approved comment count (badge)
 *  - $offset          int  AJAX pagination offset
 *
 * @category Theme Partial
 * @package  Scriptlog
 * @author   Theme Remediation
 * @license  MIT
 * @version  1.0
 */

$post_id = isset($post_id) ? (int)$post_id : 0;
$comment_limit = isset($comment_limit) ? (int)$comment_limit : 3;
$total_records = isset($total_records) ? (int)$total_records : 0;
$offset = isset($offset) ? (int)$offset : 0;
?>
<div id="comments-section" class="post-comments container-fluid px-0">
    <script nonce="<?= defined('CSP_NONCE') ? theme_escape_html(CSP_NONCE) : ''; ?>">
        window.CommentSettings = {
            postId: <?= $post_id ?>,
            limit: <?= $comment_limit ?>
        };
    </script>

    <?php if ($offset === 0) : ?>
        <div class="row">
            <div class="col">
                <header class="mb-3">
                    <h3 class="h5 font-weight-bold">
                        Post Comments
                        <span class="badge badge-secondary"><?= $total_records ?></span>
                    </h3>
                </header>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div id="comments" data-post-id="<?= $post_id ?>"></div>
            <div class="text-center mt-3">
                <button id="load-more" class="btn btn-outline-primary">Load More Comments</button>
            </div>
        </div>
    </div>
</div>
