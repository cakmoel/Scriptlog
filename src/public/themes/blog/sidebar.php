<aside class="col-lg-4">
  <!-- Widget [Search Bar Widget]-->
  <div class="widget search">
    <header>
      <h3 class="h6">Search</h3>
    </header>
    <form action="#" class="search-form">
      <div class="form-group">
        <input type="search" placeholder="What are you looking for?">
        <button type="submit" class="submit"><i class="icon-search"></i></button>
      </div>
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
        <h3 class="h6">Latest Posts</h3>
      </header>

      <div class="blog-posts">

        <?php
        foreach ((array)$latest_posts['sidebarPosts'] as $latest_post) :
          $author = (isset($latest_post['user_login'])) ? escape_html($latest_post['user_login']) : escape_html($latest_post['user_fullname']);
          $latest_post_id = (isset($latest_post['ID'])) ? abs((int)$latest_post['ID']) : "";
          $total_comment = (total_comment($latest_post_id) > 0) ? total_comment($latest_post_id) : 0;
          
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
  <!-- Widget [Categories Widget]-->
  <div class="widget categories">
    <header>
      <h3 class="h6">Categories</h3>
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
      <h3 class="h6">Archives</h3>
    </header>

    <?php
    if (function_exists('retrieve_archives')) :
    foreach (retrieve_archives() as $archives) :
      $month_name = isset($archives['month_archive']) ? date("F Y", mktime(0, 0, 0, intval($archives['month_archive']), 7, intval($archives['year_archive']))) : "";
    ?>

      <div class="item d-flex justify-content-between"><a href="<?= permalinks($archives['month_archive'] . $archives['year_archive'])['archive']; ?>" title="<?= $month_name; ?>"><?= $month_name; ?></a><span><?= isset($archives['total']) ? $archives['total'] : ""; ?></span></div>
    
    <?php
    endforeach;
    endif;
    ?>
    
  </div>
  <!-- Widget [Tags Cloud Widget]-->

  <div class="widget tags">
    <header>
      <h3 class="h6">Tags</h3>
    </header>
    <ul class="list-inline">
      <?= function_exists('outputting_tags') ? outputting_tags() : ""; ?>
    </ul>
  </div>
</aside>