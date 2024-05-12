<?php

$retrieve_page = function_exists('rewrite_status') && rewrite_status() == 'yes' ? retrieve_page(request_path()->param1, 'yes') : retrieve_page(HandleRequest::isQueryStringRequested()['value'], 'no');

$page_img = isset($retrieve_page['media_filename']) ? htmlout($retrieve_page['media_filename']) : "";
$page_id = isset($retrieve_page['ID']) ? (int)$retrieve_page['ID'] : "";
$page_title = isset($retrieve_page['post_title']) ? htmlout($retrieve_page['post_title']) : "";

$retrieve_comments = comments_by_post($page_id)['comments'];

if (!empty($page_id) || $page_id !== '') {

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
                        <h1>
                            <?= isset($page_title) ? $page_title : ""; ?>
                            <a href="<?= isset($page_id) ? permalinks($page_id) : "#"; ?>" title="<?= isset($page_title) ? $page_title : ""; ?>">
                            <i class="fa fa-external-link" aria-hidden="true"></i></a>
                        </h1>
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

                    </div>
                </div>
            </div>
        </main>

        <?php
          include __DIR__ . '/sidebar.php';
        ?>

    </div>

</div>

<?php 

} else {

    http_response_code(400);
    include dirname(__FILE__) . '/404.php';
}

?>