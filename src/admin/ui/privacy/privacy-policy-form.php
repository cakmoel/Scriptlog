<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>
<style>
    .policy-form-container {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        padding: 25px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
        display: block;
    }
    
    .checkbox-group {
        margin-top: 10px;
    }
    
    .checkbox-group label {
        font-weight: 400;
    }
    
    .btn-group-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }
    
    .note-editor {
        border-radius: 8px;
    }
    
    .note-editor.note-frame {
        border: 1px solid #e2e8f0;
    }
</style>
<div class="content-wrapper">

    <section class="content-header">
        <h1><?= (isset($pageTitle) ? safe_html($pageTitle) : ""); ?>
            <small>Control Panel</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="index.php?load=privacy-policy"><?= admin_translate('nav.privacy_policy'); ?></a></li>
            <li class="active">
                <?= (isset($action) && $action === 'edit-policy') ? 'Edit Policy' : 'New Policy'; ?>
            </li>
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

        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">
                            <?= (isset($action) && $action === 'edit-policy') ? 'Edit Privacy Policy' : 'Create New Privacy Policy'; ?>
                        </h3>
                    </div>

                    <div class="box-body">
                        <form method="post" action="index.php?load=privacy-policy&action=<?= (isset($action) && $action === 'edit-policy') ? 'edit-policy' : 'new-policy'; ?><?= ($policyId > 0) ? '&Id=' . (int)$policyId : ''; ?>" id="policyForm">
                            
                            <?php if (!isset($action) || $action !== 'edit-policy') : ?>
                            <div class="form-group">
                                <label for="locale">Language <span class="text-red">*</span></label>
                                <select name="locale" id="locale" class="form-control select2" required>
                                    <?php if (isset($languages) && is_array($languages)) : ?>
                                        <?php foreach ($languages as $lang) : ?>
                                            <option value="<?= safe_html($lang['lang_code']); ?>" 
                                                <?= ($lang['lang_code'] === 'en') ? 'selected' : ''; ?>>
                                                <?= safe_html($lang['lang_name']); ?> (<?= safe_html($lang['lang_code']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <small class="help-block">Select the language for this privacy policy</small>
                            </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label for="policy_title">Policy Title <span class="text-red">*</span></label>
                                <input type="text" name="policy_title" id="policy_title" class="form-control" 
                                    value="<?= (isset($policy) && isset($policy['policy_title'])) ? safe_html($policy['policy_title']) : ''; ?>"
                                    placeholder="e.g., Privacy Policy" required>
                            </div>

                            <div class="form-group">
                                <label for="policy_content">Policy Content <span class="text-red">*</span></label>
                                <textarea name="policy_content" id="policy_content" class="form-control" rows="20" required placeholder="Enter HTML content..."><?= (isset($policy) && isset($policy['policy_content'])) ? $policy['policy_content'] : ''; ?></textarea>
                                <small class="help-block">Enter HTML content for the privacy policy. You can use HTML tags like &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;li&gt;, &lt;strong&gt;, etc.</small>
                            </div>

                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" name="is_default" value="1" 
                                        <?= (isset($policy) && isset($policy['is_default']) && $policy['is_default'] == 1) ? 'checked' : ''; ?>>
                                    Set as default policy (used when no policy matches the user's language)
                                </label>
                            </div>

                            <div class="btn-group-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save"></i> 
                                    <?= (isset($action) && $action === 'edit-policy') ? 'Update Policy' : 'Create Policy'; ?>
                                </button>
                                <a href="index.php?load=privacy-policy" class="btn btn-default">
                                    <i class="fa fa-times"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    // Initialize Select2 for dropdowns
    $('.select2').select2();
});
</script>
