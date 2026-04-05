<?php

/**
 * Render the HTML for the comments section with Bootstrap 4.6 styling.
 *
 * @param int $postId The ID of the post to load comments for.
 * @param int $offset Optional offset for paginated comment loading.
 * @return string HTML output for the comments section.
 */
function render_comments_section(int $postId, int $offset = 0): string
{
    $totalRecords = isset(total_comment($postId)['total']) ? (int) total_comment($postId)['total'] : 0;

    ob_start(); ?>

    <div id="comments-section" class="post-comments container-fluid px-0">
        <?php if ($offset === 0) : ?>
        <div class="row">
            <div class="col">
                <header class="mb-3">
                    <h3 class="h5 font-weight-bold">
                        Post Comments
                        <span class="badge badge-secondary"><?= htmlspecialchars($totalRecords) ?></span>
                    </h3>
                </header>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div id="comments" data-post-id="<?= $postId ?>"></div>
                <div class="text-center mt-3">
                    <button id="load-more" class="btn btn-outline-primary">Load More Comments</button>
                </div>
            </div>
        </div>
    </div>

    <?php
    return ob_get_clean();
}
