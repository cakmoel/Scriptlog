<div class="page-wrap d-flex flex-row align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12 text-center">
                <span class="display-1 d-block"><?= t('404.title'); ?></span>
                <div class="mb-4 lead"><?= t('404.message'); ?></div>
                <a href="<?= function_exists('retrieve_site_url') ? retrieve_site_url() : ""; ?>" class="btn btn-link"><?= t('404.back_home'); ?></a>
            </div>
        </div>
    </div>
</div>