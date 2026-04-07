<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>
<style>
    .policy-container {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        margin-top: 15px;
        overflow: hidden;
    }
    
    #policyTable {
        table-layout: fixed;
        width: 100%;
        margin-bottom: 0;
        border: none;
    }

    #policyTable thead th {
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

    #policyTable tbody td {
        padding: 15px 20px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        border-top: none;
    }

    #policyTable tbody tr:hover {
        background-color: #fbfcfe;
    }

    .col-locale { width: 15%; }
    .col-title { width: 30%; }
    .col-status { width: 15%; }
    .col-actions { width: 20%; }

    .default-badge {
        background: #10b981;
        color: #fff;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    .policy-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .policy-actions .btn {
        padding: 6px 12px;
        font-size: 13px;
    }

    @media (max-width: 768px) {
        #policyTable thead {
            display: none;
        }
        
        #policyTable, #policyTable tbody, #policyTable tr, #policyTable td {
            display: block;
            width: 100%;
        }
        
        #policyTable tbody tr {
            margin-bottom: 25px;
            border: 1px solid #f1f5f9;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            padding: 20px;
            background: #fff;
        }
        
        #policyTable td {
            padding: 10px 0 !important;
            border: none !important;
        }
        
        #policyTable td::before {
            content: attr(data-label);
            display: block;
            font-size: 10px;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 6px;
            letter-spacing: 0.05em;
        }

        .policy-actions {
            width: 100%;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f1f5f9;
        }
        
        .policy-actions .btn {
            flex: 1;
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
            <li class="active"><a href="index.php?load=privacy-policy"><?= (isset($pageTitle) ? safe_html($pageTitle) : ""); ?></a></li>
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

        <?php if (isset($_SESSION['status'])) : ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-check"></i> Success!</h4>
            <p>
            <?php 
                $statusMsg = $_SESSION['status'];
                unset($_SESSION['status']);
                if ($statusMsg === 'policyCreated') echo "Privacy policy created successfully.";
                elseif ($statusMsg === 'policyUpdated') echo "Privacy policy updated successfully.";
                elseif ($statusMsg === 'policyDeleted') echo "Privacy policy deleted successfully.";
                elseif ($statusMsg === 'policyDefaultSet') echo "Default policy set successfully.";
                else echo "Operation completed successfully.";
            ?>
            </p>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border" style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding: 15px;">
                        <div class="header-filters">
                            <h3 class="box-title" style="margin: 0; font-weight: 600; color: #333;">
                                <?= admin_translate('nav.privacy_policy'); ?>
                            </h3>
                        </div>
                        <div class="header-actions">
                            <a href="<?= $createLink; ?>" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Add New Policy
                            </a>
                        </div>
                    </div>

                    <div class="box-body table-responsive">
                        <table id="policyTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th class="col-locale">Language</th>
                                    <th class="col-title">Policy Title</th>
                                    <th class="col-status">Default</th>
                                    <th class="col-actions">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($policies) && is_array($policies) && count($policies) > 0) : ?>
                                    <?php foreach ($policies as $policy) : ?>
                                        <tr>
                                            <td data-label="Language">
                                                <strong><?= strtoupper(safe_html($policy['locale'])); ?></strong>
                                            </td>
                                            <td data-label="Policy Title">
                                                <?= safe_html($policy['policy_title']); ?>
                                            </td>
                                            <td data-label="Default">
                                                <?php if (isset($policy['is_default']) && $policy['is_default'] == 1) : ?>
                                                    <span class="default-badge"><i class="fa fa-check"></i> Default</span>
                                                <?php else : ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="Actions">
                                                <div class="policy-actions">
                                                    <a href="index.php?load=privacy-policy&action=edit-policy&Id=<?= (int)$policy['ID']; ?>" class="btn btn-xs btn-primary">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                    <?php if (!isset($policy['is_default']) || $policy['is_default'] != 1) : ?>
                                                    <a href="index.php?load=privacy-policy&action=setDefault&Id=<?= (int)$policy['ID']; ?>" class="btn btn-xs btn-success" onclick="return confirm('Set this as the default privacy policy?');">
                                                        <i class="fa fa-check"></i> Set Default
                                                    </a>
                                                    <?php endif; ?>
                                                    <form method="post" action="index.php?load=privacy-policy&action=delete-policy&Id=<?= (int)$policy['ID']; ?>" style="display: inline;">
                                                        <button type="submit" class="btn btn-xs btn-danger" onclick="return confirm('Are you sure you want to delete this policy?');">
                                                            <i class="fa fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No privacy policies found. Click "Add New Policy" to create one.</td>
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
