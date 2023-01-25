<?php if (!defined('SCRIPTLOG')) exit(); ?>
 <!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small>
        <a href="<?= generate_request('index.php', 'get', ['plugins', ActionConst::INSTALLPLUGIN, 0])['link']; ?>" class="btn btn-primary"><i class="fa fa-cloud-upload"></i> Upload Plugin</a>
        </small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=plugins">All Plugins</a></li>
        <li class="active">Data Plugin</li>
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
                <h4><i class="icon fa fa-ban"></i> Error!</h4>
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
                <h4><i class="icon fa fa-check"></i> Success!</h4>
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
              <?=(isset($pluginsTotal)) ? $pluginsTotal : 0; ?> 
               plugin<?=($pluginsTotal != 1) ? 's' : ''; ?>
               in Total  
              </h2>
               </div>
              <!-- /.box-header -->
              
              <div class="box-body">
               <table id="scriptlog-table" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th>#</th>
                  <th>Plugin</th>
                  <th>Description</th>
                  <th>Level</th>
                  <th>Delete</th>
                  <th>Status</th>
                </tr>
                </thead>
                <tbody>
                 <?php 
                   if (is_array($plugins)) : 
                   $no = 0;
                   foreach ($plugins as $p => $plugin) :
                   $no++;
                  ?>
              
                    <tr>
                       <td><?= $no; ?></td>
                       <td>
                       <?= safe_html($plugin['plugin_name']); ?>
                       </td>
                       <td><p><?= html_entity_decode($plugin['plugin_desc']); ?></p></td>
                       <td><?= safe_html($plugin['plugin_level']); ?></td>
                       <td>
                       <a href="javascript:deletePlugin('<?=(isset($plugin['ID']) ?  safe_html((int)$plugin['ID']) : 0); ?>', '<?=(isset($plugin['plugin_name']) ? safe_html($plugin['plugin_name']) : ""); ?>')" title="Delete Plugin" class="btn btn-danger"> 
                       <i class="fa fa-remove fa-fw"></i> Delete</a>
                       </td>
                       <td>
                       <?php if($plugin['plugin_status'] == 'N') : ?>
                       <a href="javascript:activatePlugin('<?= abs((int)$plugin['ID']); ?>', '<?= safe_html($plugin['plugin_name']); ?>')" class="btn btn-success" title="Enabled plugin">
                       <i class="fa fa-toggle-on fa-fw"></i> Enabled</a>
                       <?php else : ?>
                       <a href="javascript:deactivatePlugin('<?= abs((int)$plugin['ID']); ?>', '<?= safe_html($plugin['plugin_name']); ?>')" class="btn btn-danger" title="Disabled plugin">
                       <i class="fa fa-toggle-off fa-fw"></i> Disabled</a>
                       <?php endif; ?>
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
                  <th>Plugin</th>
                  <th>Description</th>
                  <th>Level</th>
                  <th>Delete</th>
                  <th>Status</th>
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
  function activatePlugin(id, plugin)
  {
	  if (confirm("Are you sure want to activate '" + plugin + "'?"))
	  {
	  	window.location.href = 'index.php?load=plugins&action=activatePlugin&Id=' + id;
	  }
  }

  function deactivatePlugin(id, plugin)
  {
	  if (confirm("Are you sure want to deactivate '" + plugin + "'?"))
	  {
	  	window.location.href = 'index.php?load=plugins&action=deactivatePlugin&Id=' + id;
	  }
  }
  
  function deletePlugin(id, plugin)
  {
	  if (confirm("Are you sure want to uninstall '" + plugin + "'?"))
	  {
	  	window.location.href = 'index.php?load=plugins&action=deletePlugin&Id=' + id;
	  }
  }

</script>
