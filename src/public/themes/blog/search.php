<?php

defined('SCRIPTLOG') || die('Direct access not permitted');

$searchResults = isset($GLOBALS['search_results']) ? $GLOBALS['search_results'] : [];
$searchKeyword = isset($GLOBALS['search_keyword']) ? theme_escape_html($GLOBALS['search_keyword']) : '';
$searchPagination = isset($GLOBALS['search_pagination']) ? $GLOBALS['search_pagination'] : [];
$searchRateLimited = isset($GLOBALS['search_rate_limited']) ? (bool)$GLOBALS['search_rate_limited'] : false;

$results = isset($searchResults['results']) ? $searchResults['results'] : [];
$totalRows = isset($searchResults['totalRows']) ? (int)$searchResults['totalRows'] : 0;
$hasError = isset($searchResults['error']);
$currentPage = isset($searchPagination['page']) ? (int)$searchPagination['page'] : 1;
$totalPages = isset($searchPagination['totalPages']) ? (int)$searchPagination['totalPages'] : 0;
$paginationHtml = isset($searchPagination['html']) ? $searchPagination['html'] : '';
?>
<main role="main" id="search-results-page" class="container">
  <div class="row">
    <div class="posts-listing col-lg-8">

      <section aria-labelledby="search-heading">
        <header class="archive-header mb-5">
          <h2 id="search-heading"><?= t('search.title'); ?></h2>
          <?php if (!empty($searchKeyword)) : ?>
            <p class="text-muted" aria-live="polite">
              <?php if ($totalRows > 0) : ?>
                <?= t('search.found_results', ['count' => $totalRows, 'keyword' => $searchKeyword]); ?>
              <?php else : ?>
                <?= t('search.no_results', ['keyword' => $searchKeyword]); ?>
              <?php endif; ?>
            </p>
          <?php endif; ?>
        </header>

        <?php if ($searchRateLimited) : ?>
          <div class="alert alert-warning" role="alert">
            <p class="mb-0"><?= t('search.rate_limited'); ?></p>
          </div>

        <?php elseif ($hasError) : ?>
          <div class="alert alert-warning" role="alert">
            <p class="mb-0"><?= t('search.error'); ?></p>
          </div>

        <?php elseif (!empty($searchKeyword) && !empty($results)) : ?>
          <?php if ($totalPages > 1) : ?>
            <p class="text-muted text-center small" role="status">
              <?= t('search.page_x_of_y', ['page' => $currentPage, 'total' => $totalPages]); ?>
            </p>
          <?php endif; ?>

          <div class="search-results-list" role="list" aria-label="<?= t('search.title'); ?>">
            <?php foreach ($results as $item) :
              $itemId = isset($item->ID) ? (int)$item->ID : 0;
              $itemTitle = isset($item->post_title) ? theme_escape_html($item->post_title) : '';
              $itemType = isset($item->post_type) ? theme_escape_html($item->post_type) : 'blog';
              $itemDate = isset($item->post_date) ? theme_escape_html(make_date($item->post_date)) : '';
              $itemDateTime = isset($item->post_date) ? theme_escape_html($item->post_date) : '';
              $itemExcerpt = isset($item->post_content) ? paragraph_l2br(safe_html(paragraph_trim($item->post_content))) : '';

              if ($itemType === 'page') {
                $itemUrl = theme_page_url(['ID' => $itemId, 'post_slug' => isset($item->post_slug) ? $item->post_slug : '']);
                $typeLabel = t('search.type.page');
              } else {
                $itemUrl = theme_post_url(['ID' => $itemId, 'post_slug' => isset($item->post_slug) ? $item->post_slug : '']);
                $typeLabel = t('search.type.post');
              }
            ?>
            <article class="search-result-item d-flex mb-4 pb-4 border-bottom" role="listitem">
              <div class="post-details w-100">
                <h3 class="h5 mt-0">
                  <a href="<?= $itemUrl; ?>"><?= $itemTitle; ?></a>
                </h3>
                <div class="text-muted small mb-2 d-flex flex-wrap align-items-center gap-2">
                  <span class="badge badge-<?= $itemType === 'page' ? 'secondary' : 'primary'; ?>"><?= $typeLabel; ?></span>
                  <time datetime="<?= $itemDateTime; ?>" class="ml-2"><?= $itemDate; ?></time>
                </div>
                <p class="text-muted"><?= $itemExcerpt; ?></p>
                  <a href="<?= $itemUrl; ?>" class="btn btn-outline-primary btn-sm" aria-label="<?= sprintf(t('search.read_more_aria'), $itemTitle); ?>">
                  <?= t('search.read_more'); ?><i class="fa fa-long-arrow-right ml-1" aria-hidden="true"></i>
                </a>
              </div>
            </article>
            <?php endforeach; ?>
          </div>

          <?php if (!empty($paginationHtml)) : ?>
            <?= $paginationHtml; ?>
            <p class="text-muted text-center small mt-2" role="status">
              <?= t('search.page_x_of_y', ['page' => $currentPage, 'total' => $totalPages]); ?>
            </p>
          <?php endif; ?>

        <?php elseif (!empty($searchKeyword)) : ?>
          <div class="text-center py-5 empty-state" role="status">
            <div class="mb-3">
              <i class="fa fa-search fa-3x text-muted" aria-hidden="true"></i>
            </div>
            <h3 class="h4 text-muted"><?= t('search.no_results_title'); ?></h3>
            <p class="text-muted"><?= t('search.try_different_keywords'); ?></p>
          </div>

        <?php else : ?>
          <div class="text-center py-5 empty-state" role="status">
            <div class="mb-3">
              <i class="fa fa-search fa-3x text-muted" aria-hidden="true"></i>
            </div>
            <h3 class="h4 text-muted"><?= t('search.enter_keyword_title'); ?></h3>
            <p class="text-muted"><?= t('search.enter_keyword'); ?></p>
          </div>
        <?php endif; ?>
      </section>

    </div>

    <?php
    include dirname(__FILE__) . '/sidebar.php';
    ?>

  </div>
</main>
