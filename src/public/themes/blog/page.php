<?php

$retrieve_page = function_exists('rewrite_status') && rewrite_status() === 'yes' ? retrieve_page(request_path()->param1, 'yes') : retrieve_page(HandleRequest::isQueryStringRequested()['value'], 'no');

$page_img = isset($retrieve_page['media_filename']) ? htmlout($retrieve_page['media_filename']) : "";
$page_id = isset($retrieve_page['ID']) ? (int)$retrieve_page['ID'] : "";
$page_title = isset($retrieve_page['post_title']) ? htmlout($retrieve_page['post_title']) : "";

if (isset($retrieve_page['user_fullname'])) {
    $page_author = htmlout($retrieve_page['user_fullname']);
}

if (isset($retrieve_page['user_login'])) {
    $page_author = htmlout($retrieve_page['user_login']);
}

$img_alt = isset($retrieve_page['media_caption']) ? htmlout($retrieve_page['media_caption']) : "";
$page_content = isset($retrieve_page['post_content']) ? html_entity_decode(htmLawed(html($retrieve_page['post_content']))) : "";

if (isset($retrieve_page['post_modified'])) {
    $page_created = htmlout(make_date($retrieve_page['post_modified']));
}

if (isset($retrieve_page['post_date'])) {
    $page_created = htmlout(make_date($retrieve_page['post_date']));
}

$comment_status = isset($retrieve_page['comment_status']) ? htmlout($retrieve_page['comment_status']) : "";
$total_comment = (total_comment($page_id) > 0) ? $total_comment : 0;

?>

<div class="container">
    <div class="row">
        <main class="post blog-post col-lg-8">
            <div class="container">
                <div class="post-single">
                    <div class="post-thumbnail"><img src="<?= isset($page_img) ? invoke_frontimg($page_img) : "https://picsum.photos/730/486"; ?>" alt="<?= isset($img_alt) ? $img_alt : ""; ?>"></div>
                    <div class="post-details">
                        <div class="post-meta d-flex justify-content-between">
                            <div class="category">
                                <?= isset($page_id) ? link_topic((int)$page_id) : ""; ?>
                            </div>
                        </div>
                        <h1><?= isset($page_id) ? $page_title : ""; ?><a href="<?= isset($page_id) ? permalinks($page_id) : "#"; ?>"><i class="a fa-external-link" aria-hidden="true"></i></a></h1>
                        <div class="post-footer d-flex align-items-center flex-column flex-sm-row">
                            <a href="#" class="author d-flex align-items-center flex-wrap">
                                <div class="title"><span><i class="fa fa-user-circle" aria-hidden="true"></i> <?= $page_author; ?> </span></div>
                            </a>
                            <div class="d-flex align-items-center flex-wrap">
                                <div class="date"><i class="fa fa-calendar" aria-hidden="true"></i> <?= $page_created; ?> </div>
                                <div class="comments meta-last"><i class="icon-comment" aria-hidden="true"></i><?= $total_comment; ?></div>
                            </div>
                        </div>

                        <div class="post-body">
                            <?= $page_content; ?>
                        </div>

                        <div class="post-tags">
                            <?= isset($page_id) ? link_tag($page_id) : ""; ?>
                        </div>

                        <?php
                          if ($comment_status !== 'closed') :
                        ?>

                            <div class="post-comments">
                                <header>
                                    <h3 class="h6">Post Comments<span class="no-of-comments">(<?= $total_comment; ?>)</span></h3>
                                </header>

                                <?php
                                  foreach (comments_by_post($page_id) as $comment) :

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

                                <form role="form" method="post" action="<?= retrieve_site_url() . DS . basename('comments-post.php'); ?>" id="commentForm" class="p-5 bg-light">

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

<p class="text-big">
    <?php

    echo "<pre>";
    $requestPath = new RequestPath();
    echo "Request matched: {$requestPath->matched} <br>";
    echo "Request param1: {$requestPath->param1} <br>";
    echo "Request param2: {$requestPath->param2} <br>";
    echo "Request param3: {$requestPath->param3} <br>";
    echo "</pre>";
    echo "<br>";
    echo "<pre>";
    print_r($_SERVER);
    echo '</pre>';

    echo "<br>Page executed in: " . $time = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);

    ?>

</p>