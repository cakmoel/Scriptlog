<?php if (!defined('SCRIPTLOG')) exit(); ?>
 <!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small><a href="index.php?load=pages&action=newPage&Id=0"
					class="btn btn-primary"> <i
					class="fa fa-plus-circle"></i> Add New
				</a></small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=pages">All Pages</a></li>
        <li class="active">Data Pages</li>
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
                <h2><i class="icon fa fa-ban"></i> Alert!</h2>
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
                <h2><i class="icon fa fa-check"></i> Alert!</h2>
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
                    <?=(isset($pagesTotal)) ? $pagesTotal : 0; ?> 
                    Page<?=($pagesTotal != 1) ? 's' : ''; ?>
                    in Total  
                  </h2>
               </div>
              <!-- /.box-header -->
              
              <div class="box-body">
                  <table id="scriptlog-table" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th>#</th>
                  <th>Title</th>
                  <th>Author</th>
                  <th>Date</th>
                  <th>Edit</th>
                  <th>Delete</th>
                </tr>
                </thead>
                <tbody>
                <?php 
                 if (is_array($pages)) :
                   $no = 0;
                   foreach ($pages as $p => $page) :
                    $no++;
                ?>
                    <tr>
                       
                       <td><?= $no; ?></td>
                       <td><?= htmlspecialchars($page['post_title']); ?></td>
                       <td><?= htmlspecialchars($page['user_login']); ?></td>
                       <td><?= (isset($page['post_modified'])) ? htmlspecialchars(make_date($page['post_modified'])) : htmlspecialchars(make_date($page['post_date'])); ?></td>
                       <td>
                       <a href="<?=generate_request('index.php', 'get', ['pages', 'editPage', $page['ID']])['link']; ?>" class="btn btn-warning" title="Edit page">
                       <i class="fa fa-pencil fa-fw"></i> </a>
                       </td>
                       <td>
                       <a href="javascript:deletePage('<?= abs((int)$page['ID']); ?>', '<?= safe_html($page['post_title']); ?>')" class="btn btn-danger" title="Delete page">
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
                  <th>Title</th>
                  <th>Author</th>
                  <th>Date</th>
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
  function deletePage(id, title)
  {
	  if (confirm("Are you sure want to delete Page '" + title + "'"))
	  {
	  	window.location.href = 'index.php?load=pages&action=deletePage&pageId=' + id;
	  }
  }
</script>