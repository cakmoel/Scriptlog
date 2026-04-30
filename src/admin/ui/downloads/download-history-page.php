<?php
defined('SCRIPTLOG') || die("Direct access not permitted");
?>
<div class="content-wrapper">
    <section class="content-header">
        <h1><?= htmlout($pageTitle); ?> <small>for media: <?= htmlout($mediaFilename ?? 'Unknown'); ?></small></h1>
        <ol class="breadcrumb">
            <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="index.php?load=downloads">Downloads</a></li>
            <li class="active">Download History</li>
        </ol>
    </section>

    <section class="content">
        <?php if (isset($status) && !empty($status)): ?>
            <?php foreach ($status as $msg): ?>
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <?= htmlout($msg); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (isset($errors) && !empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <?= htmlout($error); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Download History Records</h3>
                <div class="box-tools pull-right">
                    <a href="index.php?load=downloads" class="btn btn-default btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to All Downloads
                    </a>
                </div>
            </div>
            <div class="box-body">
                <?php if (empty($history)): ?>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> No download history found for this media file.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Download ID</th>
                                    <th>IP Address</th>
                                    <th>Downloaded At</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $record): ?>
                                    <tr>
                                        <td><?= htmlout($record['ID']); ?></td>
                                        <td><?= htmlout($record['media_identifier']); ?></td>
                                        <td><?= htmlout($record['ip_address']); ?></td>
                                        <td><?= htmlout(date('Y-m-d H:i:s', strtotime($record['created_at']))); ?></td>
                                        <td>
                                            <?php if (strtotime($record['before_expired']) < time()): ?>
                                                <span class="label label-danger">Expired</span>
                                            <?php else: ?>
                                                <span class="label label-success">Active</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
