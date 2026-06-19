<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

$archives = function_exists('archive_index') ? archive_index() : [];

$monthNames = [
    '01' => 'January', '02' => 'February', '03' => 'March',
    '04' => 'April', '05' => 'May', '06' => 'June',
    '07' => 'July', '08' => 'August', '09' => 'September',
    '10' => 'October', '11' => 'November', '12' => 'December'
];

$archivesByYear = [];
if (!empty($archives)) {
    foreach ($archives as $archive) {
        $year = $archive['year_archive'];
        if (!isset($archivesByYear[$year])) {
            $archivesByYear[$year] = [];
        }
        $archivesByYear[$year][] = $archive;
    }
}

?>

<div class="container">
    <div class="row">
        <div class="posts-listing col-lg-8">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <h2 class="mb-4"><?= t('nav.archives'); ?></h2>
                    </div>

                    <?php if (!empty($archivesByYear)) : ?>
                        <?php foreach ($archivesByYear as $year => $yearArchives) : ?>
                            <div class="col-12 mb-4">
                                <h3 class="year-archive"><?= htmlout($year); ?></h3>
                                <div class="archive-list">
                                    <?php foreach ($yearArchives as $archive) :
                                        $month = str_pad($archive['month_archive'], 2, '0', STR_PAD_LEFT);
                                        $monthName = $monthNames[$month] ?? $month;
                                        $total = $archive['total_archive'];
                                        ?>
                                        <div class="archive-item mb-2">
                                            <a href="<?= app_url(); ?>/archive/<?= $month; ?>/<?= $year; ?>" class="archive-link">
                                                <span class="archive-month"><?= htmlout($monthName); ?></span>
                                                <span class="archive-count">(<?= $total; ?> <?= $total == 1 ? 'post' : 'posts'; ?>)</span>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="col-12">
                            <p class="text-muted"><?= t('archive.empty'); ?></p>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <?php
          include dirname(__FILE__) . '/sidebar.php';
        ?>

    </div>
</div>
