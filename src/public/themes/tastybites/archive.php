<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Note: header.php is loaded via call_theme_header() from HandleRequest.php
?>

<div class="row">
    <div class="col-lg-8">
        <?php
        $req = request_path();
        $month = $req->param1 ?? date('m');
        $year = $req->param2 ?? date('Y');
        
        $posts = posts_by_archive(['month' => $month, 'year' => $year]);
        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        ?>
        
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= app_url(); ?>"><?= t('nav.home'); ?></a></li>
                <li class="breadcrumb-item"><a href="<?= app_url(); ?>/archives"><?= t('nav.archives'); ?></a></li>
                <li class="breadcrumb-item active"><?= $monthName; ?> <?= $year; ?></li>
            </ol>
        </nav>
        
        <h2 class="mb-4"><?= $monthName; ?> <?= $year; ?></h2>
        
        <?php if (empty($posts)) : ?>
        <div class="alert alert-info"><?= t('archive.empty'); ?></div>
        <?php else : ?>
            <?php foreach ($posts as $post) : 
                $postLink = permalinks($post['ID'])['post'];
            ?>
            <div class="archive-item">
                <h6 class="archive-title">
                    <a href="<?= $postLink; ?>"><?= tastybites_htmlout($post['post_title']); ?></a>
                </h6>
                <div class="archive-date"><?= tastybites_make_date($post['post_date']); ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <?php require dirname(__FILE__) . '/sidebar.php'; ?>
    </div>
</div>

