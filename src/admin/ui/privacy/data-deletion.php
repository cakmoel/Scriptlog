<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : "Request Data Deletion"; ?>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home </a></li>
        <li><a href="index.php?load=privacy">Privacy </a></li>
        <li class="active">Delete Data</li>
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
         
             <div class="box box-danger">
                <div class="box-header with-border">
                  <h3 class="box-title">Request Data Deletion (Right to be Forgotten)</h3>
                </div>
                <!-- /.box-header -->
               
                <div class="box-body">
                  <div class="alert alert-warning">
                    <h4><i class="icon fa fa-warning"></i> Important</h4>
                    This action is irreversible. Once your data is deleted, it cannot be recovered.
                  </div>
                  
                  <p>Under GDPR Article 17, you have the "right to be forgotten" - the right to request deletion of your personal data.</p>
                  
                  <form method="post" action="index.php?load=privacy&action=delete" class="form-horizontal">
                    <div class="form-group">
                      <label for="email" class="col-sm-2 control-label">Email Address</label>
                      <div class="col-sm-10">
                        <input type="email" class="form-control" id="email" name="delete_email" 
                               placeholder="Enter your registered email" required
                               value="<?= isset($_SESSION['scriptlog_session_email']) ? htmlout($_SESSION['scriptlog_session_email']) : ''; ?>">
                      </div>
                    </div>
                    
                    <div class="form-group">
                      <label for="reason" class="col-sm-2 control-label">Reason (Optional)</label>
                      <div class="col-sm-10">
                        <textarea class="form-control" id="reason" name="delete_reason" 
                                  rows="3" placeholder="Please tell us why you're requesting deletion"></textarea>
                      </div>
                    </div>
                    
                    <div class="form-group">
                      <label class="col-sm-2 control-label">Data to Delete</label>
                      <div class="col-sm-10">
                        <div class="checkbox">
                          <label>
                            <input type="checkbox" name="delete_profile" value="1" checked disabled>
                            Profile Information (Name, Email, Username)
                          </label>
                        </div>
                        <div class="checkbox">
                          <label>
                            <input type="checkbox" name="delete_comments" value="1" checked>
                            Comments you've submitted
                          </label>
                        </div>
                        <div class="checkbox">
                          <label>
                            <input type="checkbox" name="delete_posts" value="1">
                            Posts you've authored
                          </label>
                        </div>
                      </div>
                    </div>
                    
                    <div class="form-group">
                      <div class="col-sm-offset-2 col-sm-10">
                        <div class="checkbox">
                          <label>
                            <input type="checkbox" name="confirm_delete" value="1" required>
                            I understand this action is irreversible and I want to proceed
                          </label>
                        </div>
                      </div>
                    </div>
                    
                    <div class="form-group">
                      <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-danger">Request Data Deletion</button>
                        <a href="index.php?load=dashboard" class="btn btn-default">Cancel</a>
                      </div>
                    </div>
                  </form>
                  
                  <hr>
                  
                  <h4>What happens next?</h4>
                  <ol>
                    <li>We will verify your email address</li>
                    <li>We'll process your request within 30 days (as required by GDPR)</li>
                    <li>You'll receive a confirmation email when deletion is complete</li>
                    <li>Note: Some data may be retained for legal obligations (e.g., tax records)</li>
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
