<div class="page-wrap d-flex flex-row align-items-center min-vh-50" role="alert" aria-live="assertive">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 text-center">
                <span class="display-1 d-block mb-4" aria-hidden="true"><?= t('404.title'); ?></span>
                <h1 class="h4 mb-3 sr-only"><?= t('404.title'); ?></h1>
                <div class="mb-4 lead"><?= t('404.message'); ?></div>
                <a href="<?= function_exists('retrieve_site_url') ? retrieve_site_url() : ""; ?>" class="btn btn-outline-primary btn-lg">
                    <i class="fa fa-home" aria-hidden="true"></i>
                    <?= t('404.back_home'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
