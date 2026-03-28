<?php if (!defined('SCRIPTLOG')) { exit(); } ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : "Export Your Data"; ?>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home </a></li>
        <li><a href="index.php?load=privacy">Privacy </a></li>
        <li class="active">Export Data</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
         <div class="col-xs-12">
         
         <?php 
         if (isset($errors)) :
         ?>
         <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h4><i class="icon fa fa-ban" aria-hidden="true"></i> Error!</h4>
            <?php 
               foreach ($errors as $e) :
                 echo $e;
               endforeach;
            ?>
          </div>
         <?php 
         endif;
         ?>
         
         <?php 
         if (isset($status)) :
         ?>
         <div class="alert alert-success alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h4><i class="icon fa fa-check" aria-hidden="true"></i> Success!</h4>
          <?php 
             foreach ($status as $s) :
               echo $s;
             endforeach;
          ?>
           </div>
         <?php 
         endif;
         ?>
         
             <div class="box box-primary">
                <div class="box-header with-border">
                  <h3 class="box-title">Request Your Data Export</h3>
                </div>
                <!-- /.box-header -->
               
                <div class="box-body">
                  <p>Under GDPR, you have the right to receive a copy of your personal data in a commonly used electronic format. 
                  Use the form below to request an export of your data.</p>
                  
                  <form method="post" action="index.php?load=privacy&action=export" class="form-horizontal">
                    <div class="form-group">
                      <label for="email" class="col-sm-2 control-label">Email Address</label>
                      <div class="col-sm-10">
                        <input type="email" class="form-control" id="email" name="export_email" 
                               placeholder="Enter your registered email" required 
                               value="<?= isset($_SESSION['scriptlog_session_email']) ? htmlout($_SESSION['scriptlog_session_email']) : ''; ?>">
                      </div>
                    </div>
                    
                    <div class="form-group">
                      <label class="col-sm-2 control-label">Data to Export</label>
                      <div class="col-sm-10">
                        <div class="checkbox">
                          <label>
                            <input type="checkbox" name="export_profile" value="1" checked disabled>
                            Profile Information (Name, Email, Username)
                          </label>
                        </div>
                        <div class="checkbox">
                          <label>
                            <input type="checkbox" name="export_comments" value="1" checked>
                            Comments you've submitted
                          </label>
                        </div>
                        <div class="checkbox">
                          <label>
                            <input type="checkbox" name="export_posts" value="1">
                            Posts you've authored (if any)
                          </label>
                        </div>
                        <div class="checkbox">
                          <label>
                            <input type="checkbox" name="export_activity" value="1" checked>
                            Activity logs
                          </label>
                        </div>
                      </div>
                    </div>
                    
                    <div class="form-group">
                      <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary">Request Data Export</button>
                        <a href="index.php?load=dashboard" class="btn btn-default">Cancel</a>
                      </div>
                    </div>
                  </form>
                  
                  <hr>
                  
                  <h4>What happens next?</h4>
                  <ol>
                    <li>We will verify your email address</li>
                    <li>We'll process your request within 30 days (as required by GDPR)</li>
                    <li>You'll receive an email with a download link</li>
                    <li>The download link will expire after 7 days</li>
                  </ol>
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
