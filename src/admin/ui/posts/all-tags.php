<?php if (!defined('SCRIPTLOG')) exit(); ?>
 <!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small><a href="index.php?load=tags&action=newTag&Id=0"
					class="btn btn-primary"> <i
					class="fa fa-plus-circle"></i> Add New
				</a></small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=tags">All Tags</a></li>
        <li class="active">Data Tags</li>
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
                <h2><i class="icon fa fa-check"></i> Success!</h2>
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
                   <?=(isset($tagsTotal)) ? $tagsTotal : 0; ?>
                   <?=($tagsTotal != 1) ? 'Tags' : 'Tag'; ?>
                   in Total  
                 </h2>
               </div>
              <!-- /.box-header -->
              
              <div class="box-body">
                  <table id="scriptlog-table" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Slug</th>
                  <th>Edit</th>
                  <th>Delete</th>
                </tr>
                </thead>
                <tbody>
                  <?php 
                    if(is_array($tags)) :
                      $no = 0;
                      foreach($tags as $tag) :
                        $no++;
                  ?>
                     <tr>
                      <td><?= $no; ?></td>
                      <td><?= safe_html($tag['tag_name']); ?></td>
                      <td><?= safe_html($tag['tag_slug']); ?></td>
                      <td>
                       <a href="<?=generate_request("index.php", 'get', ['tags', 'editTag', $tag['ID']])['link']; ?>" class="btn btn-warning" title="Edit topic">
                       <i class="fa fa-pencil fa-fw"></i> </a>
                       </td>
                       <td>
                       <a href="javascript:deleteTag('<?= abs((int)$tag['ID']); ?>', '<?= safe_html($tag['tag_name']); ?>')" class="btn btn-danger" title="Delete topic">
                       <i class="fa fa-trash-o fa-fw"></i> </a>
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
                  <th>Slug</th>
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
  function deleteTag(id, name)
  {
	  if (confirm("Are you sure want to delete Tag '" + name + "'"))
	  {
	  	window.location.href = 'index.php?load=tags&action=deleteTag&Id=' + id;
	  }
  }
</script>