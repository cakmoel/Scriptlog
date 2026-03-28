<?php if (!defined('SCRIPTLOG')) { exit(); } ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : "Privacy Audit Logs"; ?>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home </a></li>
        <li><a href="index.php?load=privacy">Privacy </a></li>
        <li class="active">Audit Logs</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
         <div class="col-xs-12">
         
             <div class="box box-primary">
                <div class="box-header with-border">
              <h2 class="box-title">
              <?=(isset($logsTotal)) ? $logsTotal : 0; ?> 
               Log<?=($logsTotal != 1) ? 's' : ''; ?>
              in Total  
              </h2>
            </div>
              <!-- /.box-header -->
             
              <div class="box-body table-responsive">
                <table id="scriptlog-table" class="table table-bordered table-striped" aria-describedby="privacy logs">
                <thead>
                <tr>
                  <th>#</th>
                  <th>Action</th>
                  <th>Type</th>
                  <th>User ID</th>
                  <th>Email</th>
                  <th>IP Address</th>
                  <th>Date</th>
                  <th>Details</th>
                </tr>
                </thead>
                <tbody>
                   <?php 
                     if (is_array($privacyLogs)) : 
                        $no = 1;
                        foreach($privacyLogs as $log) :
                   ?>
                   <tr>
                     <td><?= $no; ?></td>
                     <td>
                        <?php 
                        $actionLabel = '';
                        $actionClass = 'label-default';
                        if($log['log_action'] == 'consent_given') {
                            $actionLabel = 'Consent';
                            $actionClass = 'label-success';
                        } elseif($log['log_action'] == 'data_access') {
                            $actionLabel = 'Data Access';
                            $actionClass = 'label-info';
                        } elseif($log['log_action'] == 'data_export') {
                            $actionLabel = 'Export';
                            $actionClass = 'label-primary';
                        } elseif($log['log_action'] == 'data_deletion') {
                            $actionLabel = 'Deletion';
                            $actionClass = 'label-danger';
                        } elseif($log['log_action'] == 'data_rectification') {
                            $actionLabel = 'Rectification';
                            $actionClass = 'label-warning';
                        }
                        ?>
                        <span class="label <?= $actionClass; ?>"><?= $actionLabel; ?></span>
                     </td>
                     <td><?= isset($log['log_type']) ? htmlout($log['log_type']) : ""; ?></td>
                     <td><?= isset($log['log_user_id']) ? (int)$log['log_user_id'] : '-'; ?></td>
                     <td><?= isset($log['log_email']) ? htmlout($log['log_email']) : "-"; ?></td>
                     <td><?= isset($log['log_ip']) ? htmlout($log['log_ip']) : ""; ?></td>
                     <td><?= isset($log['log_date']) ? htmlout(date('M j, Y H:i', strtotime($log['log_date']))) : ""; ?></td>
                     <td><?= isset($log['log_details']) ? htmlout(mb_substr($log['log_details'], 0, 50)) . (mb_strlen($log['log_details']) > 50 ? '...' : '') : "-"; ?></td>
                   </tr>
                   <?php 
                        $no++;
                        endforeach;
                     endif;
                   ?>
                </tbody>
                <tfoot>
                <tr>
                  <th>#</th>
                  <th>Action</th>
                  <th>Type</th>
                  <th>User ID</th>
                  <th>Email</th>
                  <th>IP Address</th>
                  <th>Date</th>
                  <th>Details</th>
                </tr>
                </tfoot>
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
