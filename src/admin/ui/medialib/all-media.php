<?php if (!defined('SCRIPTLOG')) exit(); ?>

 <!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small>
        <a href="index.php?load=medialib&action=newMedia&Id=0" class="btn btn-primary"><i class="fa fa-cloud-upload"></i> Add New</a>
        </small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=medialib">All media</a></li>
        <li class="active">Data media</li>
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
              <?=(isset($mediaTotal)) ? $mediaTotal : 0; ?> 
               item<?=($mediaTotal != 1) ? 's' : ''; ?>
               in Total  
              </h3>
               </div>
              <!-- /.box-header -->
              
              <div class="box-body">
               <table id="scriptlog-table" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th>#</th>
                  <th>File</th>
                  <th>Type</th>
                  <th>Display on</th>
                  <th>Edit</th>
                  <th>Delete</th>
                </tr>
                </thead>
                <tbody>
                 <?php 
                   if (is_array($mediaLib)) : 
                   $no = 0;
                   foreach ($mediaLib as $media) :
                   $no++;
                  ?>
              
                    <tr>
                       <td><?= $no; ?></td>
                       <td><a href="<?=medialib_link($media['media_type'], $media['media_filename']);?>" title="<?= safe_html($media['media_caption']); ?>" ><?=invoke_fileicon($media['media_type']); ?></a></td>
                       <td><?= safe_html($media['media_type']); ?></td>
                       <td><?= safe_html($media['media_target']); ?></td>

                       <td>
                       <a href="<?=generate_request("index.php", 'get', ['medialib', 'editMedia', $media['ID']])['link']; ?>" class="btn btn-warning">
                       <i class="fa fa-pencil fa-fw"></i> Edit</a>
                       </td>

                       <td>
                       <a href="javascript:deleteMedia('<?= abs((int)$media['ID']); ?>', '<?= $media['media_user']; ?>')" class="btn btn-danger">
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
                  <th>File</th>
                  <th>Type</th>
                  <th>Display on</th>
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
  function deleteMedia(id, level)
  {
	  if (confirm("Are you sure want to delete media belongs to '" + level + "'"))
	  {
	  	window.location.href = 'index.php?load=medialib&action=deleteMedia&Id=' + id;
	  }
  }
</script>
