<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

$topicProvider = class_exists('TopicModel') ? new TopicModel() : "";

if ($topicProvider instanceof TopicModel) {
    if (function_exists('rewrite_status') && rewrite_status() == 'yes' && function_exists('request_path') && request_path()->param1 !== '') {
        $slug = request_path()->param1;
        $sanitizedSlug = class_exists('Sanitize') ? Sanitize::severeSanitizer($slug) : "";

        $topic = method_exists($topicProvider, 'getTopicBySlug') ? $topicProvider->getTopicBySlug($sanitizedSlug) : "";
        $topicId = isset($topic['ID']) ? (int)$topic['ID'] : "";
    } else {
        $query_param = class_exists('HandleRequest') ? HandleRequest::isQueryStringRequested()['value'] : "";
        $sanitizedQueryParameter = Sanitize::severeSanitizer($query_param);

        $topic = $topicProvider->getTopicById($sanitizedQueryParameter);
        $topicId = isset($topic['ID']) ? (int)$topic['ID'] : "";
    }
}

$category_result = function_exists('posts_by_category') ? posts_by_category($topicId) : [];
$entries = isset($category_result['entries']) ? $category_result['entries'] : "";
$pagination = isset($category_result['pagination']) ? $category_result['pagination'] : "";

$partial_dir = dirname(__FILE__) . '/partials/';

?>

<div class="container">
    <div class="row">
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
