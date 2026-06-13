<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Note: header.php is loaded via call_theme_header() from HandleRequest.php
?>

<div class="row">
    <div class="col-12">
        <div class="not-found-section">
            <h1>404</h1>
            <h3><?= t('nav.404_title'); ?></h3>
            <p class="text-muted"><?= t('nav.404_message'); ?></p>
            <a href="<?= app_url(); ?>" class="btn btn-primary mt-3"><?= t('nav.404_home'); ?></a>
        </div>
    </div>
</div>

