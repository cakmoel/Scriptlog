<aside class="col-lg-4">
  
  <!-- Widget [Search Bar Widget]-->
  <div class="widget search">
    <header>
      <h3 class="h6"><?= t('sidebar.search.title'); ?></h3>
    </header>
    <form action="#" class="search-form" id="ajax-search-form">
      <div class="form-group">
        <input type="search" id="search-keyword" name="search" placeholder="<?= t('sidebar.search.placeholder'); ?>" autocomplete="off">
        <button type="submit" class="submit"><i class="icon-search"></i></button>
      </div>
      <div id="search-results" class="search-results"></div>
      <div id="search-error" class="search-error"></div>
      <input type="hidden" id="search-csrf" name="csrf" value="<?= block_csrf(); ?>">
    </form>
  </div>
  
  <?php
  $latest_posts = function_exists('latest_posts') ? latest_posts(5, 'sidebar') : "";
    ?>

  <!-- Widget [Latest Posts Widget] -->
  <div class="widget latest-posts">

    <?php
    if ($latest_posts) :
        ?>

      <header>
        <h3 class="h6"><?= t('sidebar.latest_posts.title'); ?></h3>
      </header>

      <div class="blog-posts">

        <?php
        foreach ((array)$latest_posts['sidebarPosts'] as $latest_post) :
            $author = (isset($latest_post['user_login'])) ? escape_html($latest_post['user_login']) : escape_html($latest_post['user_fullname']);
            $latest_post_id = (isset($latest_post['ID'])) ? abs((int)$latest_post['ID']) : "";
            $total_comment = (total_comment($latest_post_id)['total'] > 0) ? total_comment($latest_post_id)['total'] : 0;

            ?>

          <a href="<?= isset($latest_post['ID']) ? permalinks($latest_post['ID'])['post'] : "#" ?>">
            <div class="item d-flex align-items-center">
              <div class="title"><strong><?= isset($latest_post['post_title']) ? escape_html($latest_post['post_title']) : ""; ?></strong>
                <div class="d-flex align-items-center">
                  <div class="views"><i class="fa fa-user-circle" aria-hidden="true"></i> <?= $author; ?></div>
                  <div class="comments"><i class="icon-comment" aria-hidden="true"></i> <?= $total_comment; ?> </div>
                </div>
              </div>
            </div>
          </a>

            <?php
        endforeach;
        ?>
      </div>
        <?php
    endif;
    ?>
  </div>
  <!-- Categories-->
  <div class="widget categories">
    <header>
      <h3 class="h6"><?= t('sidebar.categories.title'); ?></h3>
    </header>

   <?php
    if (function_exists('sidebar_topics')) :
        foreach (sidebar_topics() as $category) :
            ?>

      <div class="item d-flex justify-content-between"><a href="<?= isset($category['ID']) ? permalinks($category['ID'])['cat'] : "#"; ?>"><?= isset($category['topic_title']) ? escape_html($category['topic_title']) : ""; ?></a><span><?= isset($category['total_posts']) ? $category['total_posts'] : "" ?></span></div>

            <?php
        endforeach;
    endif;
    ?>

  </div>

  <!-- Widget [Archives Widget]-->
  <div class="widget categories">
    <header>
      <h3 class="h6"><?= t('sidebar.archives.title'); ?></h3>
    </header>

  <?php

    if (function_exists('retrieve_archives')) :
        foreach (retrieve_archives() as $archives) :
            $month_num = isset($archives['month_archive']) ? safe_html($archives['month_archive']) : "";
            $monthObj = class_exists('DateTime') ? DateTime::createFromFormat('!m', $month_num) : "";
            $month_name = method_exists($monthObj, 'format') ? $monthObj->format('F') : "";
            $monthDate = method_exists($monthObj, 'format') ? $monthObj->format('m') : "";
            $month_name = isset($month_name) ? $month_name : date('F', mktime(0, 0, 0, $archives['month_archive'], 10));
            $year = isset($archives['year_archive']) ? safe_html($archives['year_archive']) : "";
            $total = isset($archives['total_archive']) ? safe_html($archives['total_archive']) : "";

            if (rewrite_status() === 'yes') :
                ?>

      <div class="item d-flex justify-content-between"><a href="<?= permalinks($monthDate . DS . $year)['archive']; ?>" title="<?= $month_name; ?>"><?= $month_name . ' ' . $year; ?></a><span><?= $total; ?></span></div>
    
            <?php else :
                ?>
    <div class="item d-flex justify-content-between"><a href="<?= permalinks($archives['year_archive'] . $archives['month_archive'])['archive'] ?>" title="<?= $month_name; ?>"><?= $month_name . ' ' . $year; ?></a><span><?= $total; ?></span></div>
                   <?php
            endif;
        endforeach;
    endif;
    ?>
    
  </div>
  <!-- Widget [Tags Cloud Widget]-->

  <div class="widget tags">
    <header>
      <h3 class="h6"><?= t('sidebar.tags.title'); ?></h3>
    </header>
    <ul class="list-inline">
      <?php
        if (function_exists('retrieve_tags')) {
            echo retrieve_tags();
        }
        ?>
    </ul>
  </div>
</aside>