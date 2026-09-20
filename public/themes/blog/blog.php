<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

$blog_posts = function_exists('retrieve_blog_posts') ? retrieve_blog_posts() : [];
$entries = isset($blog_posts['blogPosts']) ? $blog_posts['blogPosts'] : "";
$entries_pagination = isset($blog_posts['paginationLink']) ? $blog_posts['paginationLink'] : "";

$partial_dir = dirname(__FILE__) . '/partials/';
?>

<div class="container">
  <div class="row">
    <!-- Latest Blog -->
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
        $pagination_html = $entries_pagination;
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