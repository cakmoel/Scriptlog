<?php

$retrieve_post = (function_exists('rewrite_status') && rewrite_status() === 'yes') ? retrieve_detail_post(request_path()->param2) : retrieve_detail_post(HandleRequest::isQueryStringRequested()['value']);

$post_img = isset($retrieve_post['media_filename']) ? htmlout($retrieve_post['media_filename']) : "";
$post_id = isset($retrieve_post['ID']) ? intval((int)$retrieve_post['ID']) : "";
$post_title = isset($retrieve_post['post_title']) ? htmlout($retrieve_post['post_title']) : "";

if (isset($retrieve_post['user_fullname'])) {

  $post_author = htmlout($retrieve_post['user_fullname']);

}

if (isset($retrieve_post['user_login'])) {

  $post_author = htmlout($retrieve_post['user_login']);

}

$img_alt = isset($retrieve_post['media_caption']) ? htmlout($retrieve_post['media_caption']) : "";
$post_content = isset($retrieve_post['post_content']) ? html_entity_decode(htmLawed(html($retrieve_post['post_content']))) : "";

if (isset($retrieve_post['post_modified'])) {
  $post_created = htmlout(make_date($retrieve_post['post_modified']));
}

if (isset($retrieve_post['post_date'])) {
 $post_created = htmlout(make_date($retrieve_post['post_date']));
}

$comment_status = isset($retrieve_post['comment_status']) ? htmlout($retrieve_post['comment_status']) : "";
$total_comment = (total_comment($post_id) > 0) ? total_comment($post_id) : 0;

?>

<div class="container">

  <div class="row">

    <!-- Latest Posts -->
    <main class="post blog-post col-lg-8">
      <div class="container">
        <div class="post-single">
          <div class="post-thumbnail"><img src="<?= isset($post_img) ? invoke_frontimg($post_img) : "https://picsum.photos/730/486"; ?>" alt="<?= (isset($img_alt)) ? $img_alt : ""; ?>" class="img-fluid"></div>
          <div class="post-details">
            <div class="post-meta d-flex justify-content-between">
              <div class="category">
                <?= isset($post_id) ? link_topic((int)$post_id) : ""; ?>
              </div>
            </div>
            <h1><?= isset($post_title) ? $post_title : ""; ?><a href="<?= isset($post_id) ? permalinks($post_id)['post'] : "#"; ?>" title="<?= isset($post_title) ? $post_title : ""; ?>"><i class="fa fa-external-link" aria-hidden="true"></i></a></h1>
            <div class="post-footer d-flex align-items-center flex-column flex-sm-row">
              <a href="#" class="author d-flex align-items-center flex-wrap">
                <div class="title"><span><i class="fa fa-user-circle" aria-hidden="true"></i> <?= $post_author; ?> </span></div>
              </a>
              <div class="d-flex align-items-center flex-wrap">
                <div class="date"><i class="fa fa-calendar" aria-hidden="true"></i> <?= $post_created; ?> </div>
                <div class="comments meta-last"><i class="icon-comment" aria-hidden="true"></i><?= $total_comment; ?></div>
              </div>
            </div>
            <div class="post-body">
              <?= $post_content; ?>
            </div>

            <div class="post-tags">
              <?= isset($post_id) ? link_tag($post_id) : ""; ?>
            </div>

            <div class="posts-nav d-flex justify-content-between align-items-stretch flex-column flex-md-row">
              <?= previous_post($post_id); ?>
              <?= next_post($post_id); ?>
            </div>

<?php 
if ( $comment_status !== 'closed') :
?>

            <div class="post-comments">
              <header>
                <h3 class="h6">Post Comments<span class="no-of-comments">(<?= $total_comment; ?>)</span></h3>
              </header>

              <?php
              foreach (comments_by_post($post_id) as $comment) :

                $comment_id = isset($comment['ID']) ? intval((int)$comment['ID']) : 0;
                $comment_author_name = isset($comment['comment_author_name']) ? htmlout($comment['comment_author_name']) : "";
                $comment_content = isset($comment['comment_content']) ? html_entity_decode(htmlout($comment['comment_content'])) : "";
                $comment_at = isset($comment['comment_date']) ? htmlout(make_date($comment['comment_date'])) : "";

              ?>

                <div class="comment">
                  <div class="comment-header d-flex justify-content-between">
                    <div class="user d-flex align-items-center">
                      <div class="image"><img src="<?= theme_dir(); ?>assets/img/user.svg" alt="<?= $comment_author_name; ?>" class="img-fluid rounded-circle"></div>
                      <div class="title"><strong><?= $comment_author_name; ?></strong><span class="date"><?= $comment_at; ?></span></div>
                    </div>
                  </div>
                  <div class="comment-body">
                    <p><?= $comment_content; ?></p>
                  </div>
                </div>
              <?php
              endforeach;
              ?>

            </div>

            <div class="comment-form-wrap pt-5">
              <h3 class="mb-5">Leave a comment</h3>

              <form role="form" method="post" action="<?= app_url().DS.basename('comments-post.php'); ?>" id="commentForm" class="p-5 bg-light" >
                
                <div class="form-group">
                  <label for="comment">Type your comment*</label>
                  <textarea cols="30" rows="10" id="comment" name="comment" class="form-control" placeholder="Enter your comment" maxlength="320" required></textarea>
                </div>

                <div class="form-group">
                  <label for="name">Name*</label>
                  <input type="text" class="form-control" id="name" name="name" maxlength="90" placeholder="Enter name" required>
                  <div class="help-block with-errors"></div>
                </div>

                <div class="form-group">
                  <label for="email">Email (will not be published)*</label>
                  <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" maxlength="180" required>
                  <div class="help-block with-errors"></div>
                </div>

                <div class="form-group">
                  <input type="hidden" id="csrf" class="form-control" name="csrf" value="<?= block_csrf(); ?>">
                  <input type="hidden" id="post_id" class="form-control" name="post_id" value="<?= abs((int)$post_id); ?>">
                  <button type="submit" class="btn btn-primary">Submit Comment</button>
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
      include __DIR__ . '/sidebar.php'; 
    ?>

  </div>

</div>