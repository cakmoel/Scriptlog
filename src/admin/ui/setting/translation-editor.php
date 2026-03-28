<?php if (!defined('SCRIPTLOG')) { exit(); } ?>
<div class="content-wrapper">
    <section class="content-header">
        <h1><?= (isset($pageTitle) ? safe_html($pageTitle) : ""); ?>
            <small>Control Panel</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><a href="index.php?load=translations"><?= (isset($pageTitle) ? safe_html($pageTitle) : ""); ?></a></li>
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
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <div class="col-md-4">
                            <select id="langSelector" class="form-control">
                                <?php if (isset($languages) && is_array($languages)) : ?>
                                    <?php foreach ($languages as $lang) : ?>
                                        <option value="<?= safe_html($lang['lang_code']); ?>" 
                                            <?= ($lang['lang_code'] === ($currentLang ?? 'en')) ? 'selected' : ''; ?>>
                                            <?= safe_html($lang['lang_name']); ?> (<?= safe_html($lang['lang_code']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <form method="get" action="index.php" class="form-inline">
                                <input type="hidden" name="load" value="translations">
                                <input type="hidden" name="lang" id="langInput" value="<?= safe_html($currentLang ?? 'en'); ?>">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" 
                                        placeholder="Search translations..." 
                                        value="<?= safe_html($searchQuery ?? ''); ?>">
                                    <span class="input-group-btn">
                                        <button type="submit" class="btn btn-default">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </span>
                                </div>
                            </form>
                        </div>
                        <div class="box-tools pull-right">
                            <div class="btn-group">
                                <a href="index.php?load=translations&action=regenerate-cache&lang=<?= safe_html($currentLang ?? 'en'); ?>" class="btn btn-warning btn-sm">
                                    <i class="fa fa-refresh"></i> Regenerate Cache
                                </a>
                                <a href="index.php?load=translations&action=export&lang=<?= safe_html($currentLang ?? 'en'); ?>" class="btn btn-info btn-sm">
                                    <i class="fa fa-download"></i> Export
                                </a>
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#importModal">
                                    <i class="fa fa-upload"></i> Import
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <?php if (isset($contexts) && !empty($contexts)) : ?>
                        <div class="margin-bottom">
                            <strong>Filter by context:</strong>
                            <a href="index.php?load=translations&lang=<?= safe_html($currentLang ?? 'en'); ?>" 
                                class="btn btn-xs <?= !isset($currentContext) ? 'btn-primary' : 'btn-default'; ?>">All</a>
                            <?php foreach ($contexts as $ctx) : ?>
                                <a href="index.php?load=translations&lang=<?= safe_html($currentLang ?? 'en'); ?>&context=<?= safe_html($ctx['translation_context']); ?>" 
                                    class="btn btn-xs <?= ($currentContext === $ctx['translation_context']) ? 'btn-primary' : 'btn-default'; ?>">
                                    <?= safe_html($ctx['translation_context']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <table class="table table-bordered table-striped" id="translationTable">
                            <thead>
                                <tr>
                                    <th width="30%">Key</th>
                                    <th width="55%">Value</th>
                                    <th width="15%">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($translations) && is_array($translations)) : ?>
                                    <?php foreach ($translations as $trans) : ?>
                                        <tr data-id="<?= (int)$trans['ID']; ?>">
                                            <td>
                                                <code><?= safe_html($trans['translation_key']); ?></code>
                                                <?php if (!empty($trans['translation_context'])) : ?>
                                                    <span class="label label-default"><?= safe_html($trans['translation_context']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="translation-value" data-id="<?= (int)$trans['ID']; ?>">
                                                    <?= escape_html($trans['translation_value']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-xs btn-info edit-translation" 
                                                    data-id="<?= (int)$trans['ID']; ?>"
                                                    data-key="<?= safe_html($trans['translation_key']); ?>"
                                                    data-value="<?= escape_html($trans['translation_value']); ?>">
                                                    <i class="fa fa-edit"></i> Edit
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No translations found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="importModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="index.php?load=translations&action=import" enctype="multipart/form-data">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Import Translations</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="lang_code" value="<?= safe_html($currentLang ?? 'en'); ?>">
                    <input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? safe_html($csrfToken) : ''; ?>">
                    <div class="form-group">
                        <label for="import_file">JSON File</label>
                        <input type="file" name="import_file" id="import_file" class="form-control" accept=".json" required>
                        <small class="help-block">Upload a JSON file with translation key-value pairs</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="index.php?load=translations&action=update" id="editForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Edit Translation</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="editId">
                    <input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? safe_html($csrfToken) : ''; ?>">
                    <div class="form-group">
                        <label for="editKey">Key</label>
                        <input type="text" id="editKey" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label for="editValue">Value</label>
                        <textarea name="value" id="editValue" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('langSelector').addEventListener('change', function() {
        var lang = this.value;
        document.getElementById('langInput').value = lang;
        window.location.href = 'index.php?load=translations&lang=' + lang;
    });

    document.querySelectorAll('.edit-translation').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('editId').value = this.dataset.id;
            document.getElementById('editKey').value = this.dataset.key;
            document.getElementById('editValue').value = this.dataset.value;
            jQuery('#editModal').modal('show');
        });
    });
});
</script>
