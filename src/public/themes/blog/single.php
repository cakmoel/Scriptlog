<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Retrieve post based on permalink settings
// SEO-friendly URLs: /post/{id}/slug -> request_path()->param1
// Query string URLs: ?p={id} -> HandleRequest::isQueryStringRequested()['value']
$retrieve_post = (rewrite_status() == 'yes') ? retrieve_detail_post(request_path()->param1) : 
retrieve_detail_post(HandleRequest::isQueryStringRequested()['value']);

// Content-existence validation is owned by the Dispatcher (validateContentExists).
// When the resolved post is empty we render an empty body WITHOUT setting HTTP
// status codes or terminating the request, so the header/footer pipeline is never
// corrupted. This template never calls http_response_code() or exit().
$has_post = is_array($retrieve_post)
    && isset($retrieve_post['ID'])
    && (int)$retrieve_post['ID'] > 0;

if (!$has_post) {
    echo '<div class="container"><p>Post not found.</p></div>';
    return;
}

// Set default values for all post variables
$post_author = '';
$post_created = '';
$post_content = '';
$post_title = '';
$post_img = '';
$img_alt = '';
$total_comment = 0;
$post_visibility = 'public';
$comment_permit = 'closed';


$post_id = isset($retrieve_post['ID']) ? intval((int)$retrieve_post['ID']) : 0;
$post_img = isset($retrieve_post['media_filename']) ? theme_escape_html($retrieve_post['media_filename']) : "";
$img_alt = isset($retrieve_post['media_caption']) ? theme_escape_html($retrieve_post['media_caption']) : "";
$post_title = isset($retrieve_post['post_title']) ? theme_escape_html($retrieve_post['post_title']) : "";
$post_slug = isset($retrieve_post['post_slug']) ? theme_escape_html($retrieve_post['post_slug']) : "";
$post_content = '';
$post_visibility = isset($retrieve_post['post_visibility']) ? $retrieve_post['post_visibility'] : 'public';
$comment_permit = isset($retrieve_post['comment_permit']) ? theme_escape_html($retrieve_post['comment_permit']) : "";
$comment_data = total_comment($post_id);
$total_comment = (!empty($post_id) && !empty($comment_data['total'])) ? (int)$comment_data['total'] : 0;

// Protected/public content resolution is owned by ProtectedPostService
// (decryption, double html_entity_decode, style strip, htmLawed sanitize).
$protectedPostService = class_exists('ProtectedPostService')
    ? new ProtectedPostService()
    : null;
$post_render = ($protectedPostService instanceof ProtectedPostService)
    ? $protectedPostService->resolve($retrieve_post, isset($_SESSION['unlocked_posts']) && is_array($_SESSION['unlocked_posts']) ? $_SESSION['unlocked_posts'] : [])
    : ['id' => $post_id, 'is_protected' => ($post_visibility === 'protected'), 'is_unlocked' => false, 'show_password_form' => ($post_visibility === 'protected'), 'content' => ''];

$post_content = $post_render['content'];
$show_password_form = $post_render['show_password_form'];

if (isset($retrieve_post['user_fullname'])) {
    $post_author = theme_escape_html($retrieve_post['user_fullname']);
}

if (isset($retrieve_post['user_login'])) {
    $post_author = theme_escape_html($retrieve_post['user_login']);
}

if (isset($retrieve_post['post_date'])) {
    $post_created = theme_escape_html(make_date($retrieve_post['post_date']));
}

if (isset($retrieve_post['post_modified'])) {
    $post_created = theme_escape_html(make_date($retrieve_post['post_modified']));
}

?>

<div class="container">

    <div class="row">
        <div class="post blog-post col-lg-8">
            <div class="container">
                <div class="post-single">
                    <div class="post-thumbnal">
                        <?= get_post_thumbnail($post_img, $post_title, $img_alt); ?>
                    </div>

                    <div class="post-details">
                        <div class="post-meta d-flex justify-content-between">
                            <div class="category">
                                 <?= !empty($post_id) ? link_topic((int)$post_id) : ""; ?> 
                            </div>
                        </div>
                        <h1><?= isset($post_title) ? $post_title : ""; ?><a href="<?= isset($post_id) ? permalinks($post_id)['post'] : "#"; ?>" title="<?= isset($post_title) ? $post_title : ""; ?>"><i class="fa fa-external-link" aria-hidden="true"></i></a></h1>
                        <div class="post-footer d-flex align-items-center flex-column flex-sm-row">
                            <div class="author d-flex align-items-center flex-wrap">
                                <div class="title"><span><i class="fa fa-user-circle" aria-hidden="true"></i> <?= $post_author; ?> </span></div>
                            </div>
                            <div class="d-flex align-items-center flex-wrap">
                                <div class="date"><i class="fa fa-calendar" aria-hidden="true"></i> <?= $post_created; ?> </div>
                                <div class="comments meta-last"><i class="icon-comment" aria-hidden="true"></i><?= $total_comment; ?></div>
                            </div>
                        </div>
                        <div class="post-body">
                            <?php if ($show_password_form) : ?>
                                <div class="password-protected-post text-center py-5" id="password-protected-<?= $post_id; ?>">
                                    <div class="lock-icon mb-3">
                                        <i class="fa fa-lock fa-3x text-muted" aria-hidden="true"></i>
                                    </div>
                                    <h3 class="h4 mb-3"><?= t('visibility.password'); ?></h3>
                                    <p class="text-muted mb-4"><?= t('protected.post.description'); ?></p>
                                    <form method="post" class="password-form-inline d-inline-flex align-items-start gap-2 unlock-post-form" data-post-id="<?= $post_id; ?>">
                                        <div class="form-group">
                                            <input type="password" class="form-control post-password-input" name="post_password" placeholder="<?= t('form.password'); ?>" autocomplete="current-password" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary unlock-post-btn"><?= t('button.unlock'); ?></button>
                                    </form>
                                    <div class="unlock-post-error text-danger mt-2" style="display: none;"></div>
                                    <div class="unlock-post-loading" style="display: none;">
                                        <i class="fa fa-spinner fa-spin"></i> <?= t('status.loading'); ?>
                                    </div>
                                </div>
                                <div class="password-protected-content" id="unlocked-content-<?= $post_id; ?>" style="display: none;"></div>
                            <?php else : ?>
                                <?= $post_content; ?>
                            <?php endif; ?>
                        </div>

                        <div class="post-tags">
                            <?= link_tag($post_id) ?? ""; ?>
                        </div>

                        <div class="posts-nav d-flex justify-content-between align-items-stretch flex-column flex-md-row">
                            <?= previous_post($post_id); ?>
                            <?= next_post($post_id); ?>
                        </div>

                        <?php
                        if ($comment_permit == 'open') :
                            echo render_comments_section($post_id);

                        ?>

                            <div class="comment-form-wrap pt-5">
                                <h3 class="h6 mb-5"><?= t('single.comment.leave_reply'); ?></h3>
                                <form method="post" action="<?= retrieve_site_url() . DS . basename('comments-post.php'); ?>" id="commentForm" class="p-5 bg-light">

                                    <div class="form-group">
                                        <label for="comment"><?= t('single.comment.label'); ?>*</label>
                                        <textarea cols="30" rows="10" id="comment" name="comment" class="form-control" placeholder="<?= t('single.comment.placeholder'); ?>" maxlength="320" required></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="name"><?= t('form.name.label'); ?>*</label>
                                        <input type="text" class="form-control" id="name" name="name" maxlength="90" placeholder="<?= t('form.name.placeholder'); ?>" autocomplete="name" required>
                                        <div class="help-block with-errors"></div>
                                    </div>

                                    <div class="form-group">
                                        <label for="email"><?= t('form.email.label'); ?>*</label>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="<?= t('form.email.placeholder'); ?>" maxlength="180" autocomplete="email" required>
                                        <div class="help-block with-errors"></div>
                                    </div>

                                    <div class="form-group">
                                        <input type="hidden" id="csrf" class="form-control" name="csrf" value="<?= block_csrf(); ?>">
                                        <input type="hidden" id="post_id" class="form-control" name="post_id" value="<?= abs((int)$post_id); ?>">
                                        <input type="hidden" id="parent_id" class="form-control" name="parent_id" value="0">
                                        <button type="submit" class="btn btn-secondary"><?= t('single.comment.submit'); ?></button>
                                    </div>
                                    <div id="error_message" class="ajax_response"></div>
                                    <div id="success_message" class="ajax_response"></div>
                                </form>
                            </div>
                        <?php
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
        include dirname(__FILE__) . '/sidebar.php';
        ?>
    </div>
</div>