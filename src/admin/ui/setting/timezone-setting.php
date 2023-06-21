<?php if (!defined('SCRIPTLOG')) { exit();} ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            <?= (isset($pageTitle)) ? $pageTitle : ""; ?>
            <small>Control Panel</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home </a></li>
            <li class="active"><a href="index.php?load=option-timezone"><?=(isset($pageTitle)) ? $pageTitle : ""; ?></a></li>   
        </ol>
    </section>

    <!-- Main Content -->
    <section class="content">
        <div class="row">
            <div class="col-md-6">
                <div class="box box-primary">
                    <div class="box-header with-border"></div>
                    <!-- /.box-header -->

                    <?php
                    if (isset($errors)) :
                    ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h4><i class="icon fa fa-warning"></i> Invalid Form Data!</h4>
                            <?php
                            foreach ($errors as $e) :
                                echo '<p>' . $e . '</p>';
                            endforeach;
                            ?>
                        </div>

                    <?php
                    endif;

                    if (isset($status)) :
                    ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <h4><i class="icon fa fa-check"></i> Success!</h4>
                            <?php
                            foreach ($status as $s) :
                                echo $s;
                            endforeach;
                            ?>
                        </div>

                    <?php

                    endif;

                    $action = (isset($formAction)) ? $formAction : null;
                    $paramId = (isset($timezoneData['ID'])) ? abs((int)$timezoneData['ID']) : 0;

                    ?>

                    <div class="box-body">
                        <form method="post" action="<?= generate_request('index.php', 'get', ['option-timezone', $action, 0])['link']; ?>" >
                            <input type="hidden" name="setting_id" value="<?= $paramId; ?>">
                            <input type="hidden" name="setting_name" value="<?= (!isset($timezoneData['setting_name']) ?: safe_html($timezoneData['setting_name'])); ?>">

                           <?= (isset($timezoneIdentifier)) ? $timezoneIdentifier : ""; ?>

                            <div class="box-footer">
                                <input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? $csrfToken : ""; ?>">
                                <input type="submit" name="configFormSubmit" class="btn btn-primary" value="Update">
                            </div>
                        </form>
                    </div>
                    <!-- /.box-body -->
                </div>
            </div>
        </div>
    </section>
</div>
<script>
let data = Intl.DateTimeFormat().resolvedOptions()
$("#timezone").val(data.timeZone);
</script>