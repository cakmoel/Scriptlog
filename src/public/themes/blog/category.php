<?php

$topicProvider = class_exists('TopicProviderModel') ? new TopicProviderModel() : "";

if ($topicProvider instanceof TopicProviderModel) {

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

$entries = function_exists('posts_by_category') ? posts_by_category($topicId)['entries'] : "";
$pagination = function_exists('posts_by_category') ? posts_by_category($topicId) ['pagination'] : ""; 

?>

<div class="container">
    <div class="row">
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
                            $total_comment = (function_exists('total_comment') && total_comment($entry_id)['total'] > 0) ? total_comment($entry_id)['total'] : 0;

                    ?>

                            <div class="post col-xl-6">
                                <div class="post-thumbnail"><a href="<?= isset($entry_id) ? permalinks($entry_id)['post'] : "#" ?>"><img src="<?= isset($entry_img) ? invoke_frontimg($entry_img) : "https://via.placeholder.com/640x450" ?>" alt="<?= isset($entry_img_caption) ? $entry_img_caption : $entry_title; ?>" class="img-fluid"></a></div>
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
                <nav aria-label="Page navigation example">
                    <ul class="pagination pagination-template d-flex justify-content-center">
                        <?php
                        (isset($total_comment) && $total_comment > 0) ? $pagination : "";
                        ?>
                    </ul>
                </nav>
            </div>
        </main>

        <?php
          include dirname(__FILE__) . '/sidebar.php';
        ?>

    </div>
</div>
