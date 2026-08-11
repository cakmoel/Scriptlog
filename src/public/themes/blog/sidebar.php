<aside class="col-lg-4">
  
  <?php
  $sidebar = function_exists('prepare_sidebar') ? prepare_sidebar() : null;
  $search_action = ($sidebar !== null && $sidebar->searchAction() !== '') ? $sidebar->searchAction() : (function_exists('theme_search_url') ? theme_search_url() : (string)app_url() . '/search');
  ?>

  <!-- Widget [Search Bar Widget]-->
  <div class="widget search">
    <header>
      <h3 class="h6"><?= t('sidebar.search.title'); ?></h3>
    </header>
    <form action="<?= theme_escape_html($search_action); ?>" method="get" class="search-form" id="ajax-search-form" role="search" aria-label="<?= t('sidebar.search.title'); ?>">
      <div class="search-input-group">
        <i class="icon-search search-icon" aria-hidden="true"></i>
        <label for="search-keyword" class="sr-only"><?= t('sidebar.search.placeholder'); ?></label>
        <input type="search" id="search-keyword" name="q" class="search-input" placeholder="<?= t('sidebar.search.placeholder'); ?>" autocomplete="off" aria-describedby="search-hint search-results" aria-controls="search-results" aria-expanded="false">
        <button type="submit" class="search-submit" aria-label="<?= t('sidebar.search.submit'); ?>"><i class="icon-search" aria-hidden="true"></i></button>
        <button type="button" class="search-clear" id="search-clear" aria-label="<?= t('sidebar.search.clear'); ?>" hidden><i class="fa fa-times" aria-hidden="true"></i></button>
      </div>
      <p class="sr-only" id="search-hint"><?= t('sidebar.search.hint'); ?></p>
      <div id="search-results" class="search-results" aria-live="polite" aria-atomic="true"></div>
      <div id="search-error" class="search-error" aria-live="assertive"></div>
      <input type="hidden" id="search-csrf" name="csrf" value="<?= block_csrf(); ?>">
    </form>
  </div>

  <!-- Widget [Latest Posts Widget] -->
  <div class="widget latest-posts">

    <?php
    $latestPosts = ($sidebar !== null) ? $sidebar->latestPosts() : [];
    if (!empty($latestPosts)) :
        ?>

      <header>
        <h3 class="h6"><?= t('sidebar.latest_posts.title'); ?></h3>
      </header>

      <div class="blog-posts">

        <?php
        foreach ($latestPosts as $latest_post) :
            ?>

          <a href="<?= $latest_post->url(); ?>">
            <div class="item d-flex align-items-center">
              <div class="title"><strong><?= $latest_post->title(); ?></strong>
                <div class="d-flex align-items-center">
                  <div class="views"><i class="fa fa-user-circle" aria-hidden="true"></i> <?= $latest_post->author(); ?></div>
                  <div class="comments"><i class="icon-comment" aria-hidden="true"></i> <?= $latest_post->comments(); ?> </div>
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
    $categories = ($sidebar !== null) ? $sidebar->categories() : [];
    if (!empty($categories)) :
        foreach ($categories as $category) :
            ?>

      <div class="item d-flex justify-content-between"><a href="<?= $category['url']; ?>"><?= $category['title']; ?></a><span><?= $category['count']; ?></span></div>

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
    $archives = ($sidebar !== null) ? $sidebar->archives() : [];
    if (!empty($archives)) :
        foreach ($archives as $archive) :
            ?>

      <div class="item d-flex justify-content-between"><a href="<?= $archive['url']; ?>" title="<?= $archive['label']; ?>"><?= $archive['label']; ?></a><span><?= $archive['count']; ?></span></div>

        <?php
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
        $tags = ($sidebar !== null) ? $sidebar->tags() : [];
        if (!empty($tags)) :
            foreach ($tags as $tag) :
                ?>
      <li class="list-inline-item"><a href="<?= $tag['url']; ?>" class="tag" title="<?= $tag['label']; ?>">#<?= $tag['label']; ?></a></li>
                <?php
            endforeach;
        endif;
        ?>
    </ul>
  </div>
</aside>