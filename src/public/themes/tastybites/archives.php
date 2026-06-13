<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Note: header.php is loaded via call_theme_header() from HandleRequest.php
?>

<div class="row">
    <div class="col-lg-8">
        <h2 class="mb-4"><?= t('nav.archives'); ?></h2>
        
        <?php
        $archives = archive_index();
        
        if (empty($archives)) :
        ?>
        <div class="alert alert-info"><?= t('archive.empty'); ?></div>
        <?php else : ?>
            <?php 
            $currentYear = 0;
            foreach ($archives as $archive) :
                $archiveYear = date('Y', strtotime($archive['post_date']));
                
                if ($currentYear != $archiveYear) :
                    if ($currentYear != 0) echo '</div>';
                    $currentYear = $archiveYear;
            ?>
            <h3 class="mt-4"><?= $archiveYear; ?></h3>
            <div class="archive-list">
            <?php endif; ?>
            
            <?php 
            $month = date('n', strtotime($archive['post_date']));
            $year = date('Y', strtotime($archive['post_date']));
            $monthName = date('F', strtotime($archive['post_date']));
            $archiveLink = app_url() . '/archive/' . sprintf('%02d', $month) . '/' . $year;
            ?>
            <div class="archive-item">
                <h6 class="archive-title">
                    <a href="<?= $archiveLink; ?>"><?= $monthName; ?></a>
                </h6>
                <div class="archive-date"><?= $archive['post_count']; ?> <?= t('blog.title'); ?></div>
            </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <?php require dirname(__FILE__) . '/sidebar.php'; ?>
    </div>
</div>

