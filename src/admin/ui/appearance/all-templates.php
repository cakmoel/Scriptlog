<?php if (!defined('SCRIPTLOG')) exit(); ?>
 <!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small>
        <a href="<?= generate_request('index.php', 'get', ['templates', ActionConst::NEWTHEME, 0])['link']; ?>" class="btn btn-primary"><i class="fa fa-plus-circle"></i> Add New</a>
        </small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=templates">All Themes</a></li>
        <li class="active">Data Theme</li>
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
                <h3 class="box-title">
              <?=(isset($themesTotal)) ? $themesTotal : 0; ?> 
               theme<?=($themesTotal != 1) ? 's' : ''; ?>
               in Total  
              </h3>
               </div>
              <!-- /.box-header -->
              
              <div class="box-body">
               <table id="scriptlog-table" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th>#</th>
                  <th>Theme</th>
                  <th>Designer</th>
                  <th>Folder</th>
                  <th>Edit</th>
                  <th>Status</th>
                </tr>
                </thead>
                <tbody>
                 <?php 
                   if (is_array($themes)) : 
                   $no = 0;
                   foreach ($themes as $theme) :
                   $no++;
                  ?>
              
                    <tr>
                       <td><?= $no; ?></td>
                       <td>
                       <a href="index.php?load=templates&action=editTheme&Id=<?= safe_html((int)$theme['ID']);?>"><?= safe_html($theme['theme_title']); ?>
                       </a>
                       </td>
                       <td><?= safe_html($theme['theme_designer']); ?></td>
                       <td><?= safe_html($theme['theme_directory']); ?></td>
                       <td>
                       <a href="index.php?load=templates&action=editTheme&Id=<?= safe_html((int)$theme['ID']);?>" class="btn btn-warning">
                       <i class="fa fa-pencil fa-fw"></i> Edit</a>
                       </td>
                       <td>
                       <?php if($theme['theme_status'] == 'N') : ?>
                       <a href="javascript:activateTheme('<?= abs((int)$theme['ID']); ?>', '<?= safe_html($theme['theme_title']); ?>')" class="btn btn-success" title="Activate theme">
                       <i class="fa fa-check fa-fw"></i> Activate</a>
                       <?php else : ?>
                       <a href="javascript:deactivateTheme('<?= abs((int)$theme['ID']); ?>', '<?= safe_html($theme['theme_title']); ?>')" class="btn btn-danger" title="Deactivate theme">
                       <i class="fa fa-times-circle fa-fw"></i> Deactivate</a>
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
                  <th>Theme</th>
                  <th>Designer</th>
                  <th>Folder</th>
                  <th>Edit</th>
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
  function activateTheme(id, theme)
  {
	  if (confirm("Are you sure want to activate Theme '" + theme + "'"))
	  {
	  	window.location.href = 'index.php?load=templates&action=activateTheme&Id=' + id;
	  }
  }

  function deactivateTheme(id, theme)
  {
	  if (confirm("Are you sure want to deactivate theme '" + theme + "'"))
	  {
	  	window.location.href = 'index.php?load=templates&action=deactivatTheme&Id=' + id;
	  }
  }
</script>