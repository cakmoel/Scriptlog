<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

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

$partial_dir = dirname(__FILE__) . '/partials/';

?>

<div class="container">
    <div class="row">
        <div class="posts-listing col-lg-8">
            <div class="container">
                <div class="row">
                    <?php

                    if (!empty($posts)) :
                        foreach ($posts as $entry) :
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
