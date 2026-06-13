<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Note: header.php is loaded via call_theme_header() from HandleRequest.php
?>

<div class="row">
    <div class="col-lg-8">
        <?php
        $req = request_path();
        $postId = $req->id ?? 0;
        $post = retrieve_detail_post($postId);
        
        if (empty($post)) :
            direct_page('index.php?load=404', 404);
        endif;
        
        $post = $post[0] ?? $post;
        $categories = retrieves_topic_simple($post['ID']);
        $tags = link_tag($post['ID']);
        $comments = total_comment($post['ID']);
        $featuredImage = invoke_frontimg($post['media_filename'] ?? '', false);
        
        // Check if password protected
        if ($post['post_visibility'] === 'password-protected' && empty($post['unlocked'])) :
        ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fa fa-lock fa-3x mb-3 text-muted"></i>
                <h3><?= t('nav.password_protected'); ?></h3>
                <p class="text-muted"><?= t('post.password_protected'); ?></p>
                <form id="unlock-form" class="UnlockForm" data-id="<?= $post['ID']; ?>">
                    <div class="form-group">
                        <input type="password" name="post_password" class="form-control" placeholder="<?= t('nav.enter_password'); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary"><?= t('nav.read_more'); ?></button>
                    <div class="unlock-error text-danger mt-2" style="display:none;"></div>
                </form>
            </div>
        </div>
        <script src="<?= theme_dir(); ?>assets/js/unlock-post.min.js"></script>
        <?php else : ?>
        
        <article class="single-post">
            <div class="post-header">
                <h1><?= tastybites_htmlout($post['post_title']); ?></h1>
                <div class="post-meta">
                    <?php if (!empty($categories)) : ?>
                    <span class="category-badge"><?= $categories; ?></span>
                    <?php endif; ?>
                    <span><?= tastybites_make_date($post['post_date']); ?> <?= t('nav.author'); ?> <?= tastybites_htmlout($post['user_login']); ?></span>
                    <span class="ml-2"><i class="fa fa-comments"></i> <?= $comments['total']; ?></span>
                </div>
            </div>
            
            <?php if (!empty($post['media_filename'])) : ?>
            <?= $featuredImage; ?>
            <?php endif; ?>
            
            <div class="post-content">
                <?= $post['post_content']; ?>
            </div>
            
            <?php if (!empty($tags)) : ?>
            <div class="tags mt-4">
                <strong><?= t('nav.tag'); ?>:</strong> <?= $tags; ?>
            </div>
            <?php endif; ?>
            
            <div class="post-navigation d-flex justify-content-between mt-4 pt-4 border-top">
                <?= previous_post($post['ID']); ?>
                <?= next_post($post['ID']); ?>
            </div>
            
            <?php if ($post['comment_status'] === 'open') : ?>
            <div class="comments-section">
                <h3><?= t('nav.comments'); ?> (<?= $comments['total']; ?>)</h3>
                
                <?php
                $commentsModel = initialize_comment();
                $commentsData = $commentsModel->getCommentsByPostId($post['ID']);
                
                if (!empty($commentsData)) :
                    foreach ($commentsData as $comment) :
                ?>
                <div class="comment" id="comment-<?= $comment['ID']; ?>">
                    <div class="comment-header">
                        <div class="comment-avatar"><?= strtoupper(substr($comment['comment_author_name'], 0, 1)); ?></div>
                        <span class="comment-author"><?= tastybites_htmlout($comment['comment_author_name']); ?></span>
                        <span class="comment-date"><?= tastybites_make_date($comment['comment_date']); ?></span>
                    </div>
                    <div class="comment-content"><?= tastybites_htmlout($comment['comment_content']); ?></div>
                </div>
                <?php 
                    endforeach;
                endif;
                ?>
                
                <div class="comment-form">
                    <h4><?= t('post.comment_form_title'); ?></h4>
                    <form id="commentForm" action="" method="POST">
                        <input type="hidden" name="csrf" value="<?= block_csrf(); ?>">
                        <input type="hidden" name="comment_post_id" value="<?= $post['ID']; ?>">
                        <input type="hidden" name="comment_parent_id" value="0">
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="comment_author_name"><?= t('post.comment_form_name'); ?></label>
                                <input type="text" class="form-control" id="comment_author_name" name="comment_author_name" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="comment_author_email"><?= t('post.comment_form_email'); ?></label>
                                <input type="email" class="form-control" id="comment_author_email" name="comment_author_email" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="comment_content"><?= t('post.comment_form_message'); ?></label>
                            <textarea class="form-control" id="comment_content" name="comment_content" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= t('post.comment_form_send'); ?></button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </article>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <?php require dirname(__FILE__) . '/sidebar.php'; ?>
    </div>
</div>

