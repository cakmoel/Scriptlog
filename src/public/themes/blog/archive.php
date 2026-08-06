<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

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

$partial_dir = dirname(__FILE__) . '/partials/';

?>

<div class="container">
    <div class="row">
        <!-- Latest Posts -->
        <div class="posts-listing col-lg-8">
            <div class="container">
                <div class="row">
                    <!-- post -->

                    <?php

                    if (!empty($entries)) :
                        foreach ($entries as $entry) :
                            $post = function_exists('prepare_post_card') ? prepare_post_card($entry) : PostViewModel::fromPrepared([]);
                            $card_class = 'col-xl-6';
                            include $partial_dir . 'card.php';
                        endforeach;
                    endif;
                    ?>

                </div>

                <?php
                $pagination_html = $pagination;
                $pagination_aria_label = t('pagination.navigation');
                include $partial_dir . 'paginator.php';
                ?>
            </div>
        </div>

        <?php
          include dirname(__FILE__) . '/sidebar.php';
        ?>

    </div>
</div>