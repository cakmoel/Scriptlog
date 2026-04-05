<?php

$tagSlug = "";

if (function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes') {
    if (function_exists('request_path')) {
        $requestPath = request_path();
        if (isset($requestPath->tag) && $requestPath->tag !== '') {
            $tagSlug = $requestPath->tag;
        } elseif (isset($requestPath->param1) && $requestPath->param1 !== '') {
            $tagSlug = $requestPath->param1;
        }
    }
} else {
    $query_param = class_exists('HandleRequest') ? HandleRequest::isQueryStringRequested()['value'] : "";
    $tagSlug = $query_param;
}

$sanitizedTagSlug = !empty($tagSlug) ? trim($tagSlug) : "";

// Get posts by tag - posts_by_tag returns ['postsByTag' => [], 'paginationLink' => '']
$entries = function_exists('posts_by_tag') ? posts_by_tag($sanitizedTagSlug) : [];
$posts = !empty($entries) && isset($entries['postsByTag']) ? $entries['postsByTag'] : [];
$pagination = !empty($entries) && isset($entries['paginationLink']) ? $entries['paginationLink'] : "";

?>

<div class="container">
    <div class="row">
        <main class="posts-listing col-lg-8">
            <div class="container">
                <div class="row">
                    <?php

                    if (!empty($posts)) :
                        foreach ($posts as $entry) :
                            $entry_id = isset($entry['ID']) ? (int)$entry['ID'] : "";
                            $entry_title = isset($entry['post_title']) ? htmlout($entry['post_title']) : "";
                            $entry_content = isset($entry['post_content']) ? paragraph_l2br(htmlout(paragraph_trim($entry['post_content']))) : "";
                            $entry_img = ((isset($entry['media_filename'])) && ($entry['media_filename'] !== '') ? htmlout($entry['media_filename']) : "");
                            $entry_img_caption = isset($entry['media_caption']) ? htmlout($entry['media_caption']) : "";
                            $entry_created = isset($entry['modified_at']) ? htmlout(make_date($entry['modified_at'])) : htmlout(make_date($entry['created_at']));
                            $entry_author = (isset($entry['user_login']) || isset($entry['user_fullname']) ? htmlout($entry['user_login']) : htmlout($entry['user_fullname']));
                            $total_comment = (function_exists('total_comment') && !empty($entry_id) && total_comment($entry_id)['total'] > 0) ? total_comment($entry_id)['total'] : 0;

                            ?>

                            <div class="post col-xl-6">
                                <div class="post-thumbnail"><a href="<?= isset($entry_id) ? permalinks($entry_id)['post'] : "#" ?>"><?= isset($entry_img) ? invoke_responsive_image($entry_img, 'thumbnail', true, isset($entry_img_caption) ? $entry_img_caption : $entry_title, 'img-fluid') : '<img src="https://via.placeholder.com/640x450" alt="" width="640" height="450" class="img-fluid" loading="lazy" decoding="async">' ?></a></div>
                                <div class="post-details">
                                    <div class="post-meta d-flex justify-content-between">
                                        <div class="date meta-last"> <?= isset($entry_created) ? $entry_created : ""; ?> </div>
                                        <div class="category"><?= retrieves_topic_simple($entry_id); ?></div>
                                    </div>
                                    <a href="<?= isset($entry_id) ? permalinks($entry_id)['post'] : "javascript:void(0)"; ?>" title="<?= isset($entry_title) ? $entry_title : ""; ?>">
                                        <h3 class="h4"><?= isset($entry_title) ? $entry_title : ""; ?></h3>
                                    </a>
                                    <p class="text-muted"><?= isset($entry_content) ? html_entity_decode($entry_content) : "";  ?> </p>
                                    <footer class="post-footer d-flex align-items-center">
                                        <a href="javascript:void(0)" class="author d-flex align-items-center flex-wrap">
                                            <div class="title"><span><i class="fa fa-user-circle" aria-hidden="true"></i> <?= isset($entry_author) ? $entry_author : ""; ?></span></div>
                                        </a>
                                        <div class="date"><i class="fa fa-calendar" aria-hidden="true"></i> <?= isset($entry_created) ? $entry_created : ""; ?></div>
                                        <div class="comments meta-last"><i class="icon-comment" aria-hidden="true"></i><?= $total_comment; ?></div>
                                    </footer>
                                </div>
                            </div>

                            <?php
                        endforeach;
                    endif;
                    ?>

                </div>

                <!-- navigation -->
                <?php if (!empty($pagination)) : ?>
                <nav aria-label="Page navigation example">
                    <ul class="pagination pagination-template d-flex justify-content-center">
                        <?= $pagination; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </main>

        <?php
          include dirname(__FILE__) . '/sidebar.php';
        ?>

    </div>
</div>
