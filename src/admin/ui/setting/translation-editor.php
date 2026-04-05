<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>
<style>
    /* Premium Table Styling */
    .translation-container {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        margin-top: 15px;
        overflow: hidden;
    }
    
    #translationTable {
        table-layout: fixed;
        width: 100%;
        margin-bottom: 0;
        border: none;
    }

    #translationTable thead th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.05em;
        padding: 15px 20px;
        border-bottom: 2px solid #f1f5f9;
        border-top: none;
    }

    #translationTable tbody td {
        padding: 15px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        border-top: none;
    }

    #translationTable tbody tr:hover {
        background-color: #fbfcfe;
    }

    /* Column Widths */
    .col-key { width: 25%; }
    .col-lang { width: 10%; }
    .col-value { width: 50%; }
    .col-actions { width: 15%; }

    /* Key Styling */
    .translation-key-wrapper {
        display: block;
    }
    
    .translation-key-wrapper code {
        background: #eff6ff;
        color: #2563eb;
        border: 1px solid #dbeafe;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.85em;
        word-break: break-all;
    }

    /* Value Styling */
    .translation-value-cell {
        position: relative;
    }

    .translation-value {
        display: block;
        max-height: 100px;
        overflow-y: auto;
        word-wrap: break-word;
        white-space: pre-wrap;
        font-size: 0.95em;
        color: #334155;
        line-height: 1.6;
        padding-right: 10px;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }

    .translation-value::-webkit-scrollbar {
        width: 4px;
    }
    
    .translation-value::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    
    .translation-value::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }

    /* Action Buttons */
    .action-btns {
        display: flex;
        gap: 6px;
    }
    
    .btn-premium {
        border-radius: 8px;
        padding: 6px 12px;
        font-weight: 500;
        font-size: 12px;
        transition: all 0.2s ease;
        border: 1px solid transparent;
    }
    
    .btn-premium-edit {
        background-color: #f0fdf4;
        color: #166534;
        border-color: #dcfce7;
    }
    
    .btn-premium-edit:hover {
        background-color: #dcfce7;
        color: #14532d;
    }
    
    .btn-premium-delete {
        background-color: #fef2f2;
        color: #991b1b;
        border-color: #fee2e2;
    }
    
    .btn-premium-delete:hover {
        background-color: #fee2e2;
        color: #7f1d1d;
    }

    /* Context Labels */
    .context-tag {
        display: inline-block;
        margin-top: 4px;
        padding: 2px 8px;
        background: #f1f5f9;
        color: #64748b;
        border-radius: 4px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
    }

    /* Mobile First Adjustments */
    @media (max-width: 991px) {
        .col-key { width: 35%; }
        .col-value { width: 45%; }
        .col-actions { width: 20%; }
    }

    @media (max-width: 767px) {
        .box-header {
            display: flex !important;
            flex-direction: column;
            gap: 15px;
            padding: 20px !important;
        }
        
        .box-header .col-md-4 {
            width: 100% !important;
            padding: 0 !important;
        }
        
        .box-tools {
            position: static !important;
            width: 100%;
            margin-top: 10px;
        }

        .box-tools .btn-group {
            display: flex;
            width: 100%;
            flex-wrap: wrap;
            gap: 5px;
        }
        
        .box-tools .btn {
            flex: 1 1 48%; /* Two buttons per row on small mobile */
            font-size: 11px;
            padding: 10px 4px;
            border-radius: 6px !important;
        }

        #translationTable, 
        #translationTable thead, 
        #translationTable tbody, 
        #translationTable th, 
        #translationTable td, 
        #translationTable tr {
            display: block;
            width: 100% !important;
        }
        
        #translationTable thead {
            display: none;
        }
        
        #translationTable tbody tr {
            margin-bottom: 25px;
            border: 1px solid #f1f5f9;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            padding: 20px;
            background: #fff;
        }
        
        #translationTable td {
            padding: 10px 0 !important;
            border: none !important;
        }
        
        #translationTable td::before {
            content: attr(data-label);
            display: block;
            font-size: 10px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 6px;
            letter-spacing: 0.05em;
        }
        
        .translation-value {
            max-height: none;
            padding-right: 0;
            font-size: 1.05em;
        }
        
        .action-btns {
            width: 100%;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f1f5f9;
        }
        
        .action-btns .btn, .action-btns form {
            flex: 1;
        }
        
        .action-btns .btn {
            width: 100%;
            padding: 12px;
            font-size: 14px;
        }
    }
</style>
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
                    <div class="box-header with-border" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 15px;">
                        <div class="header-filters" style="display: flex; flex-wrap: wrap; gap: 10px; flex: 1;">
                            <div style="min-width: 200px; flex: 1; max-width: 300px;">
                                <select id="langSelector" class="form-control select2">
                                    <option value="all" <?= ($currentLang === 'all') ? 'selected' : ''; ?>>All Languages</option>
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
                            <div style="min-width: 250px; flex: 2; max-width: 400px;">
                                <form method="get" action="index.php" class="form-inline" style="width: 100%;">
                                    <input type="hidden" name="load" value="translations">
                                    <input type="hidden" name="lang" id="langInput" value="<?= safe_html($currentLang ?? 'en'); ?>">
                                    <input type="hidden" name="page" value="1">
                                    <div class="input-group" style="width: 100%;">
                                        <input type="text" name="search" class="form-control" 
                                            placeholder="Search translations..." 
                                            style="border-radius: 6px 0 0 6px !important;"
                                            value="<?= safe_html($searchQuery ?? ''); ?>">
                                        <span class="input-group-btn">
                                            <button type="submit" class="btn btn-default" style="border-radius: 0 6px 6px 0 !important;">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </span>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="box-tools" style="position: static !important; float: none !important;">
                            <div class="btn-group">
                                <button type="button" class="btn btn-success btn-sm btn-premium-action" data-toggle="modal" data-target="#addModal" style="border-radius: 6px !important; margin-right: 5px;">
                                    <i class="fa fa-plus"></i> Add New
                                </button>
                                <a href="index.php?load=translations&action=regenerate-cache&lang=<?= safe_html($currentLang ?? 'en'); ?>" class="btn btn-warning btn-sm" style="border-radius: 6px !important; margin-right: 5px;">
                                    <i class="fa fa-refresh"></i> Cache
                                </a>
                                <div class="dropdown" style="display:inline-block;">
                                    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-toggle="dropdown" style="border-radius: 6px !important;">
                                        Tools <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <li><a href="index.php?load=translations&action=export&lang=<?= safe_html($currentLang ?? 'en'); ?>"><i class="fa fa-download"></i> Export</a></li>
                                        <li><a href="#" data-toggle="modal" data-target="#importModal"><i class="fa fa-upload"></i> Import</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="box-body">
                        <?php if (isset($contexts) && !empty($contexts)) : ?>
                        <div class="margin-bottom">
                            <strong>Filter by context:</strong>
                            <a href="index.php?load=translations&lang=<?= safe_html($currentLang ?? 'en'); ?>&page=1" 
                                class="btn btn-xs <?= !isset($currentContext) ? 'btn-primary' : 'btn-default'; ?>">All</a>
                            <?php foreach ($contexts as $ctx) : ?>
                                <a href="index.php?load=translations&lang=<?= safe_html($currentLang ?? 'en'); ?>&context=<?= safe_html($ctx['translation_context']); ?>&page=1" 
                                    class="btn btn-xs <?= ($currentContext === $ctx['translation_context']) ? 'btn-primary' : 'btn-default'; ?>">
                                    <?= safe_html($ctx['translation_context']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <div class="translation-container">
                            <div class="table-responsive">
                                <table class="table" id="translationTable">
                                    <thead>
                                        <tr>
                                            <th class="col-key">Key</th>
                                            <?php if ($currentLang === 'all') : ?>
                                            <th class="col-lang">Language</th>
                                            <?php endif; ?>
                                            <th class="col-value">Value</th>
                                            <th class="col-actions">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (isset($translations) && is_array($translations)) : ?>
                                            <?php foreach ($translations as $trans) : ?>
                                                <tr data-id="<?= (int)$trans['ID']; ?>">
                                                    <td data-label="Key" class="col-key">
                                                        <div class="translation-key-wrapper">
                                                            <code><?= safe_html($trans['translation_key']); ?></code>
                                                            <?php if (!empty($trans['translation_context'])) : ?>
                                                                <span class="context-tag"><?= safe_html($trans['translation_context']); ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <?php if ($currentLang === 'all') : ?>
                                                    <td data-label="Language" class="col-lang">
                                                        <span class="label label-info"><?= safe_html($trans['lang_code'] ?? 'unknown'); ?></span>
                                                    </td>
                                                    <?php endif; ?>
                                                    <td data-label="Translation Value" class="col-value">
                                                        <span class="translation-value" data-id="<?= (int)$trans['ID']; ?>">
                                                            <?= escape_html($trans['translation_value']); ?>
                                                        </span>
                                                    </td>
                                                    <td data-label="Actions" class="col-actions">
                                                        <div class="action-btns">
                                                            <button type="button" class="btn btn-xs btn-premium btn-premium-edit edit-translation" 
                                                                data-id="<?= (int)$trans['ID']; ?>"
                                                                data-key="<?= safe_html($trans['translation_key']); ?>"
                                                                data-value="<?= escape_html($trans['translation_value']); ?>">
                                                                <i class="fa fa-edit"></i> Edit
                                                            </button>
                                                            <form method="post" action="index.php?load=translations&action=deleteTranslation&Id=<?= (int)$trans['ID']; ?>" style="display:inline;">
                                                                <input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? safe_html($csrfToken) : ''; ?>">
                                                                <button type="submit" class="btn btn-xs btn-premium btn-premium-delete" onclick="return confirm('Are you sure you want to delete this translation?');">
                                                                    <i class="fa fa-trash"></i> Delete
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr>
                                                <td colspan="<?= ($currentLang === 'all') ? '4' : '3'; ?>" class="text-center">No translations found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if (isset($pagination) && $pagination['totalPages'] > 1) : ?>
                            <div class="box-footer clearfix">
                                <ul class="pagination pagination-sm pull-right">
                                    <?php
                                    $queryParams = [];
                                    if (!empty($currentLang)) {
                                        $queryParams[] = 'lang=' . $currentLang;
                                    }
                                    if (!empty($searchQuery)) {
                                        $queryParams[] = 'search=' . urlencode($searchQuery);
                                    }
                                    if (!empty($currentContext)) {
                                        $queryParams[] = 'context=' . $currentContext;
                                    }
                                    $baseUrl = 'index.php?load=translations&' . implode('&', $queryParams) . '&page=';
                                    ?>
                                    
                                    <?php if ($pagination['page'] > 1) : ?>
                                        <li><a href="<?= $baseUrl ?><?= $pagination['page'] - 1 ?>">&laquo; Previous</a></li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $pagination['totalPages']; $i++) : ?>
                                        <?php if ($i <= 3 || $i > $pagination['totalPages'] - 3 || abs($i - $pagination['page']) <= 1) : ?>
                                            <li class="<?= ($i === $pagination['page']) ? 'active' : '' ?>">
                                                <a href="<?= $baseUrl ?><?= $i ?>"><?= $i ?></a>
                                            </li>
                                        <?php elseif ($i == 4 || $i == $pagination['totalPages'] - 3) : ?>
                                            <li class="disabled"><a href="#">...</a></li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    
                                    <?php if ($pagination['page'] < $pagination['totalPages']) : ?>
                                        <li><a href="<?= $baseUrl ?><?= $pagination['page'] + 1 ?>">Next &raquo;</a></li>
                                    <?php endif; ?>
                                </ul>
                                <p class="text-muted pull-left" style="font-size: 12px; margin: 0; padding: 8px 0;">
                                    Showing <?= $pagination['startIndex'] ?> to <?= $pagination['endIndex'] ?> of <?= $pagination['totalCount'] ?> translations
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="modal fade" id="addModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="index.php?load=translations&action=new-translation" id="addForm">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Add New Translation</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? safe_html($csrfToken) : ''; ?>">
                    <input type="hidden" name="lang_code" value="<?= safe_html($currentLang ?? 'en'); ?>">
                    <div class="form-group">
                        <label for="addKey">Translation Key <span class="text-red">*</span></label>
                        <input type="text" name="translation_key" id="addKey" class="form-control" 
                            placeholder="e.g., nav.dashboard" required pattern="^[a-zA-Z0-9._-]+$">
                        <small class="help-block">Use dot notation: section.element (e.g., nav.dashboard, form.save)</small>
                    </div>
                    <div class="form-group">
                        <label for="addValue">Translation Value <span class="text-red">*</span></label>
                        <textarea name="translation_value" id="addValue" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="addContext">Context (Optional)</label>
                        <select name="translation_context" id="addContext" class="form-control">
                            <option value="">-- Select Context --</option>
                            <option value="nav">Navigation</option>
                            <option value="form">Form</option>
                            <option value="button">Button</option>
                            <option value="status">Status</option>
                            <option value="error">Error</option>
                            <option value="visibility">Visibility</option>
                            <option value="footer">Footer</option>
                            <option value="admin">Admin</option>
                            <option value="message">Message</option>
                            <option value="validation">Validation</option>
                        </select>
                        <small class="help-block">Group related translations together</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Translation</button>
                </div>
            </form>
        </div>
    </div>
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
        
        if (lang === 'all') {
            window.location.href = 'index.php?load=translations&lang=all';
        } else {
            // Use switch-lang like the main navigation does
            window.location.href = 'index.php?switch-lang=' + lang + '&redirect=' + encodeURIComponent(window.location.href);
        }
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
