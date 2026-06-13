<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Note: header.php is loaded via call_theme_header() from HandleRequest.php
?>

<div class="row">
    <div class="col-lg-8">
        <article class="privacy-page">
            <div class="privacy-header">
                <h1><?= t('privacy.title'); ?></h1>
                <div class="privacy-meta">
                    <p class="text-muted"><?= t('privacy.intro'); ?></p>
                </div>
            </div>
            
            <div class="privacy-body">
                <h2><?= t('privacy.section1.title'); ?></h2>
                <p><?= t('privacy.section1.content'); ?></p>
                
                <h2><?= t('privacy.section2.title'); ?></h2>
                <p><?= t('privacy.section2.content'); ?></p>
                
                <h2><?= t('privacy.section3.title'); ?></h2>
                <p><?= t('privacy.section3.content'); ?></p>
                
                <h2><?= t('privacy.section4.title'); ?></h2>
                <p><?= t('privacy.section4.content'); ?></p>
                
                <h2><?= t('privacy.section5.title'); ?></h2>
                <p><?= t('privacy.section5.content'); ?></p>
                
                <h2><?= t('privacy.section6.title'); ?></h2>
                <p><?= t('privacy.section6.content'); ?></p>
            </div>
        </article>
    </div>
    
    <div class="col-lg-4">
        <?php require dirname(__FILE__) . '/sidebar.php'; ?>
    </div>
</div>

