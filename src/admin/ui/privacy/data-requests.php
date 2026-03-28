<?php if (!defined('SCRIPTLOG')) { exit(); } ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : "GDPR Data Requests"; ?>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home </a></li>
        <li><a href="index.php?load=privacy">Privacy </a></li>
        <li class="active">Data Requests</li>
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
                 echo $e;
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
               echo $s;
             endforeach;
          ?>
           </div>
         <?php 
         endif;
         ?>
         
             <div class="box box-primary">
                <div class="box-header with-border">
              <h2 class="box-title">
              <?=(isset($requestsTotal)) ? $requestsTotal : 0; ?> 
               Request<?=($requestsTotal != 1) ? 's' : ''; ?>
              in Total  
              </h2>
            </div>
              <!-- /.box-header -->
             
              <div class="box-body table-responsive">
                <table id="scriptlog-table" class="table table-bordered table-striped" aria-describedby="all data requests">
                <thead>
                <tr>
                  <th>#</th>
                  <th>Type</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>IP Address</th>
                  <th>Date</th>
                  <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                   <?php 
                     if (is_array($dataRequests)) : 
                        $no = 1;
                        foreach($dataRequests as $request) :
                   ?>
                   <tr>
                     <td><?= $no; ?></td>
                     <td>
                        <?php 
                        $typeLabel = '';
                        $typeClass = 'label-default';
                        if($request['request_type'] == 'access') {
                            $typeLabel = 'Data Access';
                            $typeClass = 'label-info';
                        } elseif($request['request_type'] == 'rectification') {
                            $typeLabel = 'Rectification';
                            $typeClass = 'label-warning';
                        } elseif($request['request_type'] == 'erasure') {
                            $typeLabel = 'Erasure';
                            $typeClass = 'label-danger';
                        }
                        ?>
                        <span class="label <?= $typeClass; ?>"><?= $typeLabel; ?></span>
                     </td>
                     <td><?= isset($request['request_email']) ? htmlout($request['request_email']) : ""; ?></td>
                     <td>
                        <?php 
                        $statusLabel = '';
                        $statusClass = 'label-default';
                        if($request['request_status'] == 'pending') {
                            $statusLabel = 'Pending';
                            $statusClass = 'label-warning';
                        } elseif($request['request_status'] == 'processing') {
                            $statusLabel = 'Processing';
                            $statusClass = 'label-info';
                        } elseif($request['request_status'] == 'completed') {
                            $statusLabel = 'Completed';
                            $statusClass = 'label-success';
                        } elseif($request['request_status'] == 'rejected') {
                            $statusLabel = 'Rejected';
                            $statusClass = 'label-danger';
                        }
                        ?>
                        <span class="label <?= $statusClass; ?>"><?= $statusLabel; ?></span>
                     </td>
                     <td><?= isset($request['request_ip']) ? htmlout($request['request_ip']) : ""; ?></td>
                     <td><?= isset($request['request_date']) ? htmlout(date('M j, Y H:i', strtotime($request['request_date']))) : ""; ?></td>
                     <td>
                        <?php if($request['request_status'] == 'pending'): ?>
                        <form method="post" action="index.php?load=privacy" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?= (int)$request['ID']; ?>">
                            <input type="hidden" name="action" value="process">
                            <button type="submit" class="btn btn-xs btn-info">Process</button>
                        </form>
                        <?php endif; ?>
                        <?php if($request['request_status'] == 'processing'): ?>
                        <form method="post" action="index.php?load=privacy" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?= (int)$request['ID']; ?>">
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" class="btn btn-xs btn-success">Complete</button>
                        </form>
                        <?php endif; ?>
                        <?php if($request['request_status'] != 'completed'): ?>
                        <form method="post" action="index.php?load=privacy" style="display:inline;">
                            <input type="hidden" name="request_id" value="<?= (int)$request['ID']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-xs btn-danger">Reject</button>
                        </form>
                        <?php endif; ?>
                     </td>
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
                  <th>Type</th>
                  <th>Email</th>
                  <th>Status</th>
                  <th>IP Address</th>
                  <th>Date</th>
                  <th>Actions</th>
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
