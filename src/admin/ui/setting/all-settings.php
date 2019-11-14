<?php if (!defined('SCRIPTLOG')) exit(); ?>
 <!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small><a href="index.php?load=settings&action=newConfig&settingId=0"
					class="btn btn-primary"> <i
					class="fa fa-plus-circle"></i> Add New
				</a></small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=settings">All Settings</a></li>
        <li class="active">Data Settings</li>
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
                <h4><i class="icon fa fa-ban"></i> Alert!</h4>
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
                <h4><i class="icon fa fa-check"></i> Alert!</h4>
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
                 <h3 class="box-title">
                   <?=(isset($settingsTotal)) ? $settingsTotal : 0; ?>
                   Setting<?=($settingsTotal != 1) ? 's' : ''; ?>
                   in Total  
                 </h3>
               </div>
              <!-- /.box-header -->
              
              <div class="box-body">
                  <table id="scriptlog-table" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Value</th>
                  <th>Description</th>
                  <th>Edit</th>
                  <th>Delete</th>
                </tr>
                </thead>
                <tbody>
                  <?php 
                    if(is_array($settings)) :
                      $no = 0;
                      foreach($settings as $setting) :
                        $no++;
                  ?>
                     <tr>
                      <td><?= $no; ?></td>
                      <td><?= htmlspecialchars($setting['setting_name']); ?></td>
                      <td><?= htmlspecialchars($setting['setting_value']); ?></td>
                      <td><?= htmlspecialchars($setting['setting_desc']); ?></td>

                      <td>
                       <a href="index.php?load=settings&action=editConfig&settingId=<?= htmlspecialchars((int)$setting['ID']);?>" class="btn btn-warning">
                       <i class="fa fa-pencil fa-fw"></i> Edit</a>
                       </td>
                       <td>
                       <a href="javascript:deleteSetting('<?= abs((int)$setting['ID']); ?>', '<?= $setting['setting_name']; ?>')" class="btn btn-danger">
                       <i class="fa fa-trash-o fa-fw"></i> Delete</a>
                       </td>

                     </tr>
                  <?php 
                      endforeach; 
                    endif; 
                  ?>

                </tbody>
                <tfoot>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Value</th>
                  <th>Description</th>
                  <th>Edit</th>
                  <th>Delete</th>
                </tr>
                </tfoot>
              </table>
              </div>
                  <!-- /.box-body -->
            </div>
               <!-- /.box -->
         </div>
            <!-- /.col-xs-12 -->
      </div>
           <!-- /.row -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
<script type="text/javascript">
  function deleteSetting(id, name)
  {
	  if (confirm("Are you sure want to delete Setting '" + name + "'"))
	  {
	  	window.location.href = 'index.php?load=settings&action=deleteConfig&settingId=' + id;
	  }
  }
</script>