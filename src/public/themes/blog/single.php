<?php

// Retrieve post based on permalink settings
// SEO-friendly URLs: /post/{id}/slug -> request_path()->param1
// Query string URLs: ?p={id} -> HandleRequest::isQueryStringRequested()['value']
$retrieve_post = (rewrite_status() == 'yes') ? retrieve_detail_post(request_path()->param1) : retrieve_detail_post(HandleRequest::isQueryStringRequested()['value']);

$post_id = isset($retrieve_post['ID']) ? intval((int)$retrieve_post['ID']) : 0;
$post_img = isset($retrieve_post['media_filename']) ? htmlout($retrieve_post['media_filename']) : "";
$img_alt = isset($retrieve_post['media_caption']) ? htmlout($retrieve_post['media_caption']) : "";
$post_title = isset($retrieve_post['post_title']) ? htmlout($retrieve_post['post_title']) : "";
$post_slug = isset($retrieve_post['post_slug']) ? htmlout($retrieve_post['post_slug']) : "";
$post_content = '';
$post_visibility = isset($retrieve_post['post_visibility']) ? $retrieve_post['post_visibility'] : 'public';
$comment_permit = isset($retrieve_post['comment_permit']) ? htmlout($retrieve_post['comment_permit']) : "";
$total_comment = (!empty($post_id) && total_comment($post_id)['total'] > 0) ? total_comment($post_id)['total'] : 0;

$is_unlocked = false;

if ($post_visibility === 'protected' && !empty($post_id)) {
    if (isset($_SESSION['unlocked_posts']) && isset($_SESSION['unlocked_posts'][$post_id])) {
        $is_unlocked = true;
    }
    
    if ($is_unlocked && isset($_SESSION['unlocked_posts'][$post_id])) {
        $decrypted_content = decrypt_post($post_id, $_SESSION['unlocked_posts'][$post_id]);
        $decoded_content = html_entity_decode($decrypted_content['post_content'], ENT_QUOTES, 'UTF-8');
        $decoded_content = html_entity_decode($decoded_content, ENT_QUOTES, 'UTF-8');
        $clean_content = preg_replace('/\s*style="[^"]*"/', '', $decoded_content);
        $clean_content = preg_replace('/\s*style=[^>\s]*/', '', $clean_content);
        $post_content = isset($decrypted_content['post_content']) ? htmLawed($clean_content, array(
            'deny_attribute' => 'style,onclick,onerror,onload,onmouseover,onfocus,onblur,onchange,onsubmit,onkeydown,onkeyup,onkeypress',
            'keep_bad' => 0
        )) : "";
    }
}

if ($post_visibility !== 'protected') {
    $decoded_content = html_entity_decode($retrieve_post['post_content'], ENT_QUOTES, 'UTF-8');
    $decoded_content = html_entity_decode($decoded_content, ENT_QUOTES, 'UTF-8');
    $clean_content = preg_replace('/\s*style="[^"]*"/', '', $decoded_content);
    $clean_content = preg_replace('/\s*style=[^>\s]*/', '', $clean_content);
    $post_content = isset($retrieve_post['post_content']) ? htmLawed($clean_content, array(
        'deny_attribute' => 'style,onclick,onerror,onload,onmouseover,onfocus,onblur,onchange,onsubmit,onkeydown,onkeyup,onkeypress',
        'keep_bad' => 0
    )) : "";
}

if (isset($retrieve_post['user_fullname'])) {
    $post_author = htmlout($retrieve_post['user_fullname']);
}

if (isset($retrieve_post['user_login'])) {
    $post_author = htmlout($retrieve_post['user_login']);
}

if (isset($retrieve_post['post_date'])) {
    $post_created = htmlout(make_date($retrieve_post['post_date']));
}

if (isset($retrieve_post['post_modified'])) {
    $post_created = htmlout(make_date($retrieve_post['post_modified']));
}

?>

    <div class="container">

        <div class="row">
            <main class="post blog-post col-lg-8">
                <div class="container">
                    <div class="post-single">
                        <div class="post-thumbnal">
                            <?= isset($post_img) ? invoke_responsive_image($post_img, 'medium', true, isset($img_alt) ? $img_alt : "", 'img-fluid', true) : '<img src="https://picsum.photos/730/486" alt="" width="730" height="486" class="img-fluid" loading="lazy" decoding="async">' ?>
                        </div>

                        <div class="post-details">
                            <div class="post-meta d-flex justify-content-between">
                                <div class="category">
                                    <?= isset($post_id) ? link_topic((int)$post_id) : ""; ?>
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
                                <?php if ($post_visibility === 'protected' && !$is_unlocked) : ?>
                                <div class="password-protected-post text-center py-5" id="password-protected-<?= $post_id; ?>">
                                    <div class="lock-icon mb-3">
                                        <i class="fa fa-lock fa-3x text-muted" aria-hidden="true"></i>
                                    </div>
                                    <h3 class="h4 mb-3"><?= t('visibility.password'); ?></h3>
                                    <p class="text-muted mb-4"><?= t('protected.post.description'); ?></p>
                                    <form method="post" class="password-form-inline d-inline-flex align-items-start gap-2 unlock-post-form" data-post-id="<?= $post_id; ?>">
                                        <div class="form-group">
                                            <input type="password" class="form-control post-password-input" name="post_password" placeholder="<?= t('form.password'); ?>" required>
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
                                            <input type="text" class="form-control" id="name" name="name" maxlength="90" placeholder="<?= t('form.name.placeholder'); ?>" required>
                                            <div class="help-block with-errors"></div>
                                        </div>

                                        <div class="form-group">
                                            <label for="email"><?= t('form.email.label'); ?>*</label>
                                            <input type="email" class="form-control" id="email" name="email" placeholder="<?= t('form.email.placeholder'); ?>" maxlength="180" required>
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
            </main>

            <?php
              include dirname(__FILE__) . '/sidebar.php';
            ?>
        </div>
</div>