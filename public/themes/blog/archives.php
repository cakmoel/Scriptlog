<?php

$archives = function_exists('archive_index') ? archive_index() : [];

// Normalize each raw archive row into a typed, already-escaped ArchiveViewModel
$archiveVMs = [];
if (!empty($archives) && function_exists('prepare_archive')) {
    foreach ($archives as $archive) {
        $archiveVMs[] = prepare_archive($archive);
    }
}

// Group archives by year (year lives on the view model getter)
$archivesByYear = [];
if (!empty($archiveVMs)) {
    foreach ($archiveVMs as $archive) {
        $year = (string)$archive->year();
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
                        <h2 class="mb-4">Archives</h2>
                    </div>

                    <?php if (!empty($archivesByYear)) : ?>
                        <?php foreach ($archivesByYear as $year => $yearArchives) : ?>
                            <div class="col-12 mb-4">
                                <h3 class="year-archive"><?= $year; ?></h3>
                                <div class="archive-list">
                                    <?php foreach ($yearArchives as $archive) :
                                        $monthName = $archive->label();
                                        $total = (string)$archive->count();
                                        ?>
                                        <div class="archive-item mb-2">
                                            <a href="<?= $archive->url(); ?>" class="archive-link">
                                                <span class="archive-month"><?= $monthName; ?></span>
                                                <span class="archive-count">(<?= $total; ?> <?= $total == 1 ? 'post' : 'posts'; ?>)</span>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="col-12">
                            <p class="text-muted">No archives found.</p>
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
