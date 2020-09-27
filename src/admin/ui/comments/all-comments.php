<?php if (!defined('SCRIPTLOG')) exit(); ?>
 <!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=comments">All Comments</a></li>
        <li class="active">Data Comments</li>
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
              <?=(isset($commmentsTotal)) ? $commentsTotal : 0; ?> 
               Comment<?=($commentsTotal != 1) ? 's' : ''; ?>
               in Total  
              </h3>
               </div>
              <!-- /.box-header -->
              
              <div class="box-body">
                  <table id="scriptlog-table" class="table table-bordered table-striped">
                <thead>
                <tr>
                  <th>#</th>
                  <th>Comment</th>
                  <th>In Response To</th>
                  <th>Submited On</th>
                  <th>Edit</th>
                  <th>Delete</th>
                </tr>
                </thead>
                <tbody>
                   <?php 
                      if(is_array($comments)) :
                        $no = 0;
                        foreach($comments as $comment) :
                        $no++;
                   ?>
                       <tr>
                         <td><?= $no; ?></td>
                         <td><a href="<?= generate_request("index.php", 'get', ['reply', ActionConst::NEWREPLY, 0])['link']; ?>"><?= htmlspecialchars($comment['comment_content']); ?></a></td>
                         <td><?= htmlspecialchars($comment['post_title']); ?></td>
                         <td><?= human_readable_datetime(read_datetime($comment['comment_date']), 'g:ia \o\n l jS F Y'); ?></td>

                         <td>
                          <a href="<?= generate_request("index.php", 'get', ['comments', ActionConst::EDITCOMMENT, $comment['ID']])['link']; ?>" class="btn btn-warning">
                           <i class="fa fa-pencil fa-fw"></i> Edit</a>
                         </td>
                         <td>
                          <a href="javascript:deleteComment('<?= abs((int)$comment['ID']); ?>', '<?= $comment['comment_author_name']; ?>')" class="btn btn-danger">
                           <i class="fa fa-trash-o fa-fw"></i> Delete</a>
                         </td>

                       </tr>
                   <?php
                        endforeach;
                      endif
                   ?>
                </tbody>
                <tfoot>
                <tr>
                  <th>#</th>
                  <th>Comment</th>
                  <th>In Response To</th>
                  <th>Submited On</th>
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
  function deleteComment(id, name)
  {
	  if (confirm("Are you sure want to delete comment from '" + name + "'"))
	  {
	  	window.location.href = 'index.php?load=comments&action=deleteComment&Id=' + id;
	  }
  }
</script>