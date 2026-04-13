<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : "Privacy Settings"; ?>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home </a></li>
        <li class="active">Privacy</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
         <div class="col-xs-12">
         
             <div class="box">
                <div class="box-header with-border">
                  <h3 class="box-title">GDPR Compliance Dashboard</h3>
                </div>
                <!-- /.box-header -->
               
                <div class="box-body">
                  <div class="row">
                    <div class="col-md-4">
                      <!-- Pending Requests -->
                      <div class="info-box">
                        <span class="info-box-icon bg-yellow"><i class="fa fa-clock-o"></i></span>
                        <div class="info-box-content">
                          <span class="info-box-text">Pending Requests</span>
                          <span class="info-box-number"><?= isset($pendingCount) ? (int)$pendingCount : 0; ?></span>
                          <a href="index.php?load=privacy&p=data-requests" class="small-box-footer">View All <i class="fa fa-arrow-circle-right"></i></a>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-md-4">
                      <!-- Total Requests -->
                      <div class="info-box">
                        <span class="info-box-icon bg-blue"><i class="fa fa-envelope"></i></span>
                        <div class="info-box-content">
                          <span class="info-box-text">Total Requests</span>
                          <span class="info-box-number"><?= isset($totalRequests) ? (int)$totalRequests : 0; ?></span>
                          <a href="index.php?load=privacy&p=data-requests" class="small-box-footer">View All <i class="fa fa-arrow-circle-right"></i></a>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-md-4">
                      <!-- Total Logs -->
                      <div class="info-box">
                        <span class="info-box-icon bg-green"><i class="fa fa-file-text"></i></span>
                        <div class="info-box-content">
                          <span class="info-box-text">Audit Logs</span>
                          <span class="info-box-number"><?= isset($totalLogs) ? (int)$totalLogs : 0; ?></span>
                          <a href="index.php?load=privacy&p=audit-logs" class="small-box-footer">View All <i class="fa fa-arrow-circle-right"></i></a>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <hr>
                  
                  <h4>Quick Actions</h4>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="box box-primary">
                        <div class="box-header">
                          <h3 class="box-title">User Privacy Tools</h3>
                        </div>
                        <div class="box-body">
                          <p>These tools allow users to manage their own data:</p>
                          <a href="index.php?load=privacy&p=data-export" class="btn btn-primary btn-block">
                            <i class="fa fa-download"></i> Data Export Request
                          </a>
                          <a href="index.php?load=privacy&p=data-deletion" class="btn btn-danger btn-block">
                            <i class="fa fa-trash"></i> Data Deletion Request
                          </a>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-md-6">
                      <div class="box box-info">
                        <div class="box-header">
                          <h3 class="box-title">Cookie Consent</h3>
                        </div>
                        <div class="box-body">
                          <p>Manage cookie consent settings:</p>
                          <p><strong>Status:</strong> 
                            <?php if (function_exists('has_cookie_consent')) : ?>
                              <span class="label label-success">Active</span>
                            <?php else : ?>
                              <span class="label label-default">Not Configured</span>
                            <?php endif; ?>
                          </p>
                          <p><strong>Banner URL:</strong> <code>/privacy</code></p>
                          <a href="<?= function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : app_url() . '/privacy'; ?>" target="_blank" class="btn btn-info btn-block">
                            <i class="fa fa-eye"></i> View Privacy Policy
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <hr>
                  
                  <h4>Recent Activity</h4>
                  <table class="table table-bordered">
                    <thead>
                      <tr>
                        <th>Action</th>
                        <th>Email</th>
                        <th>Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (isset($recentLogs) && is_array($recentLogs)) : ?>
                            <?php foreach ($recentLogs as $log) : ?>
                        <tr>
                          <td><?= htmlout($log['log_action']); ?></td>
                          <td><?= htmlout($log['log_email'] ?? '-'); ?></td>
                          <td><?= htmlout(date('M j, Y H:i', strtotime($log['log_date']))); ?></td>
                        </tr>
                            <?php endforeach; ?>
                      <?php else : ?>
                        <tr>
                          <td colspan="3" class="text-center">No recent activity</td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
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
