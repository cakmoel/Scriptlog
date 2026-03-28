<?php if (!defined('SCRIPTLOG')) { exit(); } ?>
<div class="content-wrapper">
    <section class="content-header">
        <h1><?= (isset($pageTitle) ? safe_html($pageTitle) : ""); ?>
            <small>Control Panel</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><a href="index.php?load=option-language"><?= (isset($pageTitle) ? safe_html($pageTitle) : ""); ?></a></li>
        </ol>
    </section>

    <section class="content">
        <?php if (isset($errors) && !empty($errors)) : ?>
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-warning"></i> Error!</h4>
            <?php foreach ($errors as $error) : ?>
                <p><?= safe_html($error); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($status) && !empty($status)) : ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-check"></i> Success!</h4>
            <?php foreach ($status as $s) : ?>
                <p><?= safe_html($s); ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">Language Configuration</h3>
                    </div>
                    <div class="box-body">
                        <form method="post" action="index.php?load=option-language">
                            <div class="form-group">
                                <label for="lang_default">Default Language</label>
                                <select name="lang_default" id="lang_default" class="form-control">
                                    <?php if (isset($activeLanguages) && is_array($activeLanguages)) : ?>
                                        <?php foreach ($activeLanguages as $lang) : ?>
                                            <option value="<?= safe_html($lang['lang_code']); ?>" 
                                                <?= ($lang['lang_code'] === ($defaultLang['setting_value'] ?? 'en')) ? 'selected' : ''; ?>>
                                                <?= safe_html($lang['lang_name']); ?> (<?= safe_html($lang['lang_native']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="help-block">The default language is used when no other language is specified.</small>
                            </div>

                            <div class="form-group">
                                <label>Available Languages</label>
                                <div class="checkbox">
                                    <?php if (isset($activeLanguages) && is_array($activeLanguages)) : ?>
                                        <?php foreach ($activeLanguages as $lang) : ?>
                                            <label>
                                                <input type="checkbox" name="lang_available[]" value="<?= safe_html($lang['lang_code']); ?>"
                                                    <?= in_array($lang['lang_code'], $selectedLangs ?? ['en']) ? 'checked' : ''; ?>>
                                                <?= safe_html($lang['lang_name']); ?> (<?= safe_html($lang['lang_code']); ?>)
                                            </label><br>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <small class="help-block">Select which languages should be available on the site.</small>
                            </div>

                            <hr>

                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="lang_auto_detect" value="1" 
                                            <?= (($autoDetect['setting_value'] ?? '1') === '1') ? 'checked' : ''; ?>>
                                        Enable automatic language detection
                                    </label>
                                </div>
                                <small class="help-block">Automatically detect and switch to the user's browser language if available.</small>
                            </div>

                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="lang_prefix_required" value="1" 
                                            <?= (($prefixRequired['setting_value'] ?? '1') === '1') ? 'checked' : ''; ?>>
                                        Always show language prefix in URLs
                                    </label>
                                </div>
                                <small class="help-block">When enabled, URLs will always include the language code prefix (e.g., /en/blog).</small>
                            </div>

                            <div class="box-footer">
                                <input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? safe_html($csrfToken) : ''; ?>">
                                <button type="submit" class="btn btn-primary">Save Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="box box-info">
                    <div class="box-header with-border">
                        <h3 class="box-title">Quick Links</h3>
                    </div>
                    <div class="box-body">
                        <a href="index.php?load=languages" class="btn btn-default btn-block">
                            <i class="fa fa-language"></i> Manage Languages
                        </a>
                        <a href="index.php?load=translations" class="btn btn-default btn-block">
                            <i class="fa fa-file-text-o"></i> Manage Translations
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
