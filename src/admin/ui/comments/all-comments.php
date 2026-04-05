<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>
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
                <h4><i class="icon fa fa-ban" aria-hidden="true"></i> Alert!</h4>
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
                <h3 class="box-title">
              <?= (isset($totalComments)) ? $totalComments : 0; ?> 
               <?=($totalComments !== 1) ? 'Comments' : 'Comment'; ?>
               in Total  
              </h3>
               </div>
              <!-- /.box-header -->
               
               <div class="box-body table-responsive">
                   <table id="scriptlog-table" class="table table-bordered table-striped responsive" aria-describedby="all comment">
                <thead>
                <tr>
                  <th scope="col" style="width: 5%">#</th>
                  <th scope="col" style="width: 40%">Comment</th>
                  <th scope="col" class="hidden-xs hidden-sm">In Response To</th>
                  <th scope="col" class="hidden-xs" style="width: 10%">Replies</th>
                  <th scope="col" class="hidden-xs">Submitted On</th>
                  <th scope="col" style="width: 120px; text-align: center;">Actions</th>
                </tr>
                </thead>
                <tbody>
                   <?php
                    if (is_array($comments)) :
                        $no = 0;
                        foreach ($comments as $comment) :
                            $no++;
                            $replyCount = isset($commentService) ? $commentService->countReplies($comment['ID']) : 0;
                            ?>
                       <tr>
                         <th scope="row"><?= $no; ?></th>
                         <td>
                           <a href="<?= generate_request("index.php", 'get', ['comments', ActionConst::EDITCOMMENT, $comment['ID']])['link']; ?>">
                             <?= htmlspecialchars(mb_substr($comment['comment_content'], 0, 60)); ?><?= (strlen($comment['comment_content']) > 60) ? '...' : ''; ?>
                           </a>
                           <br>
                           <small class="text-muted">by <?= safe_html($comment['comment_author_name']); ?></small>
                         </td>
                         <td class="hidden-xs hidden-sm"><?= safe_html(mb_substr($comment['post_title'], 0, 30)); ?><?= (strlen($comment['post_title']) > 30) ? '...' : ''; ?></td>
                         <td class="hidden-xs">
                            <?php if ($replyCount > 0) : ?>
                             <span class="badge bg-blue"><?= $replyCount; ?> <?= ($replyCount == 1) ? 'reply' : 'replies'; ?></span>
                            <?php else : ?>
                             <span class="text-muted">0 replies</span>
                            <?php endif; ?>
                         </td>
                         <td class="hidden-xs"><?= time_elapsed_string($comment['comment_date']); ?></td>
                         <td style="white-space: nowrap; text-align: center;">
                           <div class="btn-group">
                             <a href="<?= generate_request("index.php", 'get', ['comments', ActionConst::EDITCOMMENT, $comment['ID']])['link']; ?>" class="btn btn-warning btn-xs" title="Edit" aria-label="Edit Comment">
                               <i class="fa fa-pencil fa-fw" aria-hidden="true"></i> 
                             </a>
                             <a href="<?= generate_request("index.php", 'get', ['reply', ActionConst::REPLY, $comment['ID']])['link']; ?>" class="btn btn-primary btn-xs" title="Reply" aria-label="Reply to Comment">
                               <i class="fa fa-reply fa-fw" aria-hidden="true"></i> 
                             </a>
                             <a href="javascript:deleteComment('<?= abs((int)$comment['ID']); ?>', '<?= htmlspecialchars($comment['comment_author_name'], ENT_QUOTES); ?>')" class="btn btn-danger btn-xs" title="Delete" aria-label="Delete Comment" role="button">
                               <i class="fa fa-trash-o fa-fw" aria-hidden="true"></i> 
                             </a>
                           </div>
                         </td>
                       </tr>
                            <?php
                        endforeach;
                    endif
                    ?>
                </tbody>
                <tfoot>
                <tr>
                  <th scope="col" style="width: 5%">#</th>
                  <th scope="col" style="width: 40%">Comment</th>
                  <th scope="col" class="hidden-xs hidden-sm">In Response To</th>
                  <th scope="col" class="hidden-xs" style="width: 10%">Replies</th>
                  <th scope="col" class="hidden-xs">Submitted On</th>
                  <th scope="col" style="width: 120px; text-align: center;">Actions</th>
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
  
  function deleteReply(id, name)
  {
      if (confirm("Are you sure want to delete reply from '" + name + "'"))
      {
        window.location.href = 'index.php?load=reply&action=deleteReply&Id=' + id;
      }
  }
</script>