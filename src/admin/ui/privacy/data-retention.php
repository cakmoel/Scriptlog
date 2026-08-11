<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : "Data Retention"; ?>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home </a></li>
        <li><a href="index.php?load=privacy">Privacy </a></li>
        <li class="active">Data Retention</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
         <div class="col-xs-12">

         <?php
            if (!empty($errors)) :
                ?>
         <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h4><i class="icon fa fa-ban" aria-hidden="true"></i> Error!</h4>
                <?php
                foreach ($errors as $e) :
                    echo safe_html($e);
                endforeach;
                ?>
          </div>
                <?php
            endif;
            ?>

         <?php
            if (!empty($status)) :
                ?>
         <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h4><i class="icon fa fa-check" aria-hidden="true"></i> Success!</h4>
                      <?php
                        foreach ($status as $s) :
                            echo safe_html($s);
                        endforeach;
                        ?>
           </div>
                <?php
            endif;
            ?>

             <div class="box box-primary">
                <div class="box-header with-border">
                  <h3 class="box-title">Data Retention Policy</h3>
                </div>
                <!-- /.box-header -->

                <div class="box-body">
                  <div class="alert alert-info">
                    <h4><i class="icon fa fa-info-circle"></i> What is this?</h4>
                    GDPR (Articles 5 and 25) requires that personal data is not kept
                    longer than necessary. This page lets you enforce a retention
                    window for consent records and privacy audit logs. Records older
                    than the configured number of days are removed when you run the
                    cleanup.
                  </div>

                  <p>The configured retention window is documented in the
                  <a href="<?= app_url(); ?>/privacy" target="_blank" rel="noopener">public privacy policy</a>.
                  Update that document whenever you change the values below.</p>

                  <form method="post" action="index.php?load=privacy&p=retention" class="form-horizontal">
                    <input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? $csrfToken : ""; ?>">
                    <div class="form-group">
                      <label for="consent_retention_days" class="col-sm-4 control-label">Consent records (days)</label>
                      <div class="col-sm-6">
                        <input type="number" class="form-control" id="consent_retention_days"
                               name="consent_retention_days" min="1" max="3650"
                               value="<?= isset($retentionConsentDays) ? (int)$retentionConsentDays : 365; ?>" required>
                      </div>
                    </div>

                    <div class="form-group">
                      <label for="privacy_log_retention_days" class="col-sm-4 control-label">Audit logs (days)</label>
                      <div class="col-sm-6">
                        <input type="number" class="form-control" id="privacy_log_retention_days"
                               name="privacy_log_retention_days" min="1" max="3650"
                               value="<?= isset($retentionLogDays) ? (int)$retentionLogDays : 365; ?>" required>
                      </div>
                    </div>

                    <div class="form-group">
                      <div class="col-sm-offset-4 col-sm-6">
                        <button type="submit" name="save_retention" value="1" class="btn btn-primary">Save Retention Windows</button>
                      </div>
                    </div>
                  </form>

                  <hr>

                  <div class="box box-danger">
                    <div class="box-header with-border">
                      <h3 class="box-title">Run Cleanup Now</h3>
                    </div>
                    <div class="box-body">
                      <p>Delete all consent records and privacy audit logs older than the
                      retention windows configured above. This action cannot be undone.</p>
                      <form method="post" action="index.php?load=privacy&p=retention">
                        <input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? $csrfToken : ""; ?>">
                        <input type="hidden" name="consent_retention_days" value="<?= isset($retentionConsentDays) ? (int)$retentionConsentDays : 365; ?>">
                        <input type="hidden" name="privacy_log_retention_days" value="<?= isset($retentionLogDays) ? (int)$retentionLogDays : 365; ?>">
                        <div class="checkbox">
                          <label>
                            <input type="checkbox" name="confirm_cleanup" value="1" required>
                            I understand that records older than the retention window will be permanently deleted.
                          </label>
                        </div>
                        <button type="submit" name="run_cleanup" value="1" class="btn btn-danger">Run Cleanup Now</button>
                      </form>
                    </div>
                    <!-- /.box-body -->
                  </div>
                  <!-- /.box -->
                </div>
                <!-- /.box-body -->
              </div>
              <!-- /.box -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
