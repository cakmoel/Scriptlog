<?php

$grab_month = "";
$grab_year = "";

if (function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes') {
    if (function_exists('request_path')) {
        $requestPath = request_path();
        if (isset($requestPath->param1) && isset($requestPath->param2)) {
            $grab_month = $requestPath->param1;
            $grab_year = $requestPath->param2;
        }
    }
} else {
    $query_param = class_exists('HandleRequest') ? HandleRequest::isQueryStringRequested()['value'] : "";

    if (!empty($query_param)) {
        $archive_requested = preg_split("//", $query_param, -1, PREG_SPLIT_NO_EMPTY);

        // Extract year (first 4 digits) - indices 0-3
        $yearPart = "";
        for ($i = 0; $i < 4; $i++) {
            if (isset($archive_requested[$i])) {
                $yearPart .= $archive_requested[$i];
            }
        }
        $grab_year = $yearPart;

        // Extract month (next 2 digits) - indices 4-5, pad with leading zero if needed
        $monthPart = "";
        for ($i = 4; $i < 6; $i++) {
            if (isset($archive_requested[$i])) {
                $monthPart .= $archive_requested[$i];
            }
        }
        $grab_month = str_pad($monthPart, 2, '0', STR_PAD_LEFT);
    }
}

$values = ['month_archive' => $grab_month, 'year_archive' => $grab_year];

$archives = function_exists('posts_by_archive') ? posts_by_archive($values) : [];
$entries = !empty($archives) && isset($archives['archivesPublished']) ? $archives['archivesPublished'] : [];
$pagination = !empty($archives) && isset($archives['paginationLink']) ? $archives['paginationLink'] : "";

?>

<div class="container">
    <div class="row">
        <!-- Latest Posts -->
        <main class="posts-listing col-lg-8">
            <div class="container">
                <div class="row">
                    <!-- post -->

                    <?php

                    if (!empty($entries)) :
                        foreach ($entries as $entry) :
                            $entry_id = isset($entry['ID']) ? (int)$entry['ID'] : "";
                            $entry_title = isset($entry['post_title']) ? htmlout($entry['post_title']) : "";
                            $entry_content = isset($entry['post_content']) ? paragraph_l2br(htmlout(paragraph_trim($entry['post_content']))) : "";
                            $entry_img = ((isset($entry['media_filename'])) && ($entry['media_filename'] !== '') ? htmlout($entry['media_filename']) : "");
                            $entry_img_caption = isset($entry['media_caption']) ? htmlout($entry['media_caption']) : "";
                            $entry_created = isset($entry['modified_at']) ? htmlout(make_date($entry['modified_at'])) : htmlout(make_date($entry['created_at']));
                            $entry_author = (isset($entry['user_login']) || isset($entry['user_fullname']) ? htmlout($entry['user_login']) : htmlout($entry['user_fullname']));
                            $total_comment = isset($entry['total_comments']) ? (int)$entry['total_comments'] : 0;

                            ?>

                        <div class="post col-xl-6">
                            <div class="post-thumbnail"><a href="<?= isset($entry_id) ? permalinks($entry_id)['post'] : "#"; ?>"><?= isset($entry_img) ? invoke_responsive_image($entry_img, 'thumbnail', true, isset($entry_img_caption) ? $entry_img_caption : $entry_title, 'img-fluid') : '<img src="https://via.placeholder.com/640x450" alt="" width="640" height="450" class="img-fluid" loading="lazy" decoding="async">' ?></a></div>
                            <div class="post-details">
                                <div class="post-meta d-flex justify-content-between">
                                    <div class="date meta-last"> <?= isset($entry_created) ? $entry_created : ""; ?> </div>
                                    <div class="category"><?= isset($entry['topics_data']) ? format_topics($entry['topics_data']) : ""; ?></div>
                                </div><a href="<?= isset($entry_id) ? permalinks($entry_id)['post'] : "javascript:void(0)"; ?>" title="<?= isset($entry_title) ? $entry_title : ""; ?>">
                                    <h3 class="h4"> <?= isset($entry_title) ? $entry_title : ""; ?> </h3>
                                </a>
                                <p class="text-muted"><?= isset($entry_content) ? $entry_content : ""; ?></p>
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

                <!-- Pagination -->
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