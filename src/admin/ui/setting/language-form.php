<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>
<div class="content-wrapper">
    <section class="content-header">
        <h1><?= (isset($pageTitle) ? safe_html($pageTitle) : ""); ?>
            <small>Control Panel</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="index.php?load=languages">Languages</a></li>
            <li class="active"><?= (isset($pageTitle) ? safe_html($pageTitle) : ""); ?></li>
        </ol>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="box box-primary">
                    <div class="box-header with-border"></div>
                    <div class="box-body">
                        <form method="post" action="index.php?load=languages&action=<?= ($action === 'edit') ? 'edit&Id=' . (int)($language['ID'] ?? 0) : 'create'; ?>">
                            <div class="form-group">
                                <label for="lang_code">Language Code *</label>
                                <input type="text" name="lang_code" id="lang_code" class="form-control" 
                                    value="<?= safe_html($language['lang_code'] ?? ''); ?>" 
                                    placeholder="en" maxlength="10" required>
                                <small class="help-block">ISO 639-1 code (e.g., 'en', 'es', 'fr')</small>
                            </div>

                            <div class="form-group">
                                <label for="lang_name">Language Name *</label>
                                <input type="text" name="lang_name" id="lang_name" class="form-control" 
                                    value="<?= safe_html($language['lang_name'] ?? ''); ?>" 
                                    placeholder="English" maxlength="50" required>
                                <small class="help-block">English name (e.g., 'Spanish')</small>
                            </div>

                            <div class="form-group">
                                <label for="lang_native">Native Name *</label>
                                <input type="text" name="lang_native" id="lang_native" class="form-control" 
                                    value="<?= safe_html($language['lang_native'] ?? ''); ?>" 
                                    placeholder="English" maxlength="50" required>
                                <small class="help-block">Native name (e.g., 'Español')</small>
                            </div>

                            <div class="form-group">
                                <label for="lang_locale">Locale</label>
                                <input type="text" name="lang_locale" id="lang_locale" class="form-control" 
                                    value="<?= safe_html($language['lang_locale'] ?? ''); ?>" 
                                    placeholder="en_US" maxlength="10">
                                <small class="help-block">Full locale (e.g., 'en_US', 'es_ES')</small>
                            </div>

                            <div class="form-group">
                                <label for="lang_direction">Text Direction</label>
                                <select name="lang_direction" id="lang_direction" class="form-control">
                                    <option value="ltr" <?= (($language['lang_direction'] ?? 'ltr') === 'ltr') ? 'selected' : ''; ?>>LTR (Left to Right)</option>
                                    <option value="rtl" <?= (($language['lang_direction'] ?? '') === 'rtl') ? 'selected' : ''; ?>>RTL (Right to Left)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="lang_sort">Sort Order</label>
                                <input type="number" name="lang_sort" id="lang_sort" class="form-control" 
                                    value="<?= (int)($language['lang_sort'] ?? 0); ?>" min="0">
                            </div>

                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="lang_is_default" value="1" 
                                            <?= (($language['lang_is_default'] ?? 0) == 1) ? 'checked' : ''; ?>>
                                        Set as default language
                                    </label>
                                </div>
                            </div>

                            <?php if ($action === 'edit') : ?>
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="lang_is_active" value="1" 
                                            <?= (($language['lang_is_active'] ?? 1) == 1) ? 'checked' : ''; ?>>
                                        Active
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="box-footer">
                                <input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? safe_html($csrfToken) : ''; ?>">
                                <button type="submit" class="btn btn-primary">
                                    <?= ($action === 'edit') ? 'Update' : 'Create'; ?> Language
                                </button>
                                <a href="index.php?load=languages" class="btn btn-default">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
