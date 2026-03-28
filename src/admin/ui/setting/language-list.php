<?php if (!defined('SCRIPTLOG')) { exit(); } ?>
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            <?=(isset($pageTitle) ? safe_html($pageTitle) : ""); ?>
            <small>Control Panel</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><a href="index.php?load=languages"><?=(isset($pageTitle) ? safe_html($pageTitle) : ""); ?></a></li>
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
                        <h3 class="box-title">Languages</h3>
                        <div class="box-tools pull-right">
                            <a href="index.php?load=languages&action=<?= ActionConst::NEWLANGUAGE; ?>" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> <?= admin_translate('addLanguage'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="box-body">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Native Name</th>
                                    <th>Direction</th>
                                    <th>Default</th>
                                    <th>Active</th>
                                    <th>Sort</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($languages) && is_array($languages)) : ?>
                                    <?php foreach ($languages as $lang) : ?>
                                        <tr>
                                            <td><strong><?= safe_html($lang['lang_code']); ?></strong></td>
                                            <td><?= safe_html($lang['lang_name']); ?></td>
                                            <td><?= safe_html($lang['lang_native']); ?></td>
                                            <td><?= safe_html($lang['lang_direction']); ?></td>
                                            <td>
                                                <?php if ($lang['lang_is_default']) : ?>
                                                    <span class="label label-success">Default</span>
                                                <?php else : ?>
                                                    <a href="index.php?load=languages&action=setDefault&Id=<?= (int)$lang['ID']; ?>" class="btn btn-xs btn-default">Set Default</a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($lang['lang_is_active']) : ?>
                                                    <span class="label label-success">Active</span>
                                                <?php else : ?>
                                                    <span class="label label-default">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= (int)$lang['lang_sort']; ?></td>
                                            <td>
                                                <a href="index.php?load=languages&action=<?= ActionConst::EDITLANGUAGE; ?>&Id=<?= (int)$lang['ID']; ?>" class="btn btn-xs btn-info">
                                                    <i class="fa fa-edit"></i> Edit
                                                </a>
                                                <?php if (!$lang['lang_is_default']) : ?>
                                                    <a href="index.php?load=languages&action=<?= ActionConst::DELETELANGUAGE; ?>&Id=<?= (int)$lang['ID']; ?>" class="btn btn-xs btn-danger" onclick="return confirm('Are you sure you want to delete this language?');">
                                                        <i class="fa fa-trash"></i> Delete
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="8" class="text-center"><?= admin_translate('noLanguages'); ?></td>
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
