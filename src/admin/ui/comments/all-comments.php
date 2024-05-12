<?php if (!defined('SCRIPTLOG')) { exit(); } ?>
 <!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home </a></li>
        <li><a href="index.php?load=comments">All Comments </a></li>
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
                <h2><i class="icon fa fa-ban" aria-hidden="true"></i> Alert!</h2>
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
                <h2><i class="icon fa fa-check" aria-hidden="true"></i> Success!</h2>
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
              <?= (isset($totalComments)) ? $totalComments : 0; ?> 
               <?=($totalComments !== 1) ? 'Comments' : 'Comment'; ?>
               in Total  
              </h2>
               </div>
              <!-- /.box-header -->
              
              <div class="box-body table-responsive">
                  <table id="scriptlog-table" class="table table-bordered table-striped responsive" aria-describedby="all comment">
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
                         <td><a href="<?= generate_request("index.php", 'get', ['reply', ActionConst::RESPONSETO, 0])['link']; ?>"><?= htmlspecialchars($comment['comment_content']); ?></a></td>
                         <td><?= safe_html($comment['post_title']); ?></td>
                         <td><?= time_elapsed_string($comment['comment_date']); ?></td>

                         <td>
                          <a href="<?= generate_request("index.php", 'get', ['comments', ActionConst::EDITCOMMENT, $comment['ID']])['link']; ?>" class="btn btn-warning" title="Edit comment">
                           <i class="fa fa-pencil fa-fw" aria-hidden="true"></i> </a>
                         </td>
                         <td>
                          <a href="javascript:deleteComment('<?= abs((int)$comment['ID']); ?>', '<?= $comment['comment_author_name']; ?>')" class="btn btn-danger" title="Delete comment">
                           <i class="fa fa-trash-o fa-fw" aria-hidden="true"></i> </a>
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