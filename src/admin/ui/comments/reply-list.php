<?php if (!defined('SCRIPTLOG')) { exit(); } ?>

<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small>Control Panel</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard" aria-hidden="true"></i> Home </a></li>
        <li><a href="index.php?load=comments">Comments </a></li>
        <li class="active">Replies</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        
        <?php 
        if (isset($errors)) :
        ?>
        <div class="col-md-12">
          <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-ban" aria-hidden="true"></i> Alert!</h4>
            <?php 
              foreach ($errors as $e) :
                echo $e;
              endforeach;
            ?>
          </div>
        </div>
        <?php 
        endif;
        ?>
        
        <?php 
        if (isset($status)) :
        ?>
        <div class="col-md-12">
          <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-check" aria-hidden="true"></i> Success!</h4>
            <?php 
              foreach ($status as $s) :
                echo $s;
              endforeach;
            ?>
          </div>
        </div>
        <?php 
        endif;
        ?>

        <div class="col-xs-12">
          <div class="box box-primary">
            <div class="box-header with-border">
              <div class="row">
                <div class="col-sm-6">
                  <h3 class="box-title">Replies for Comment #<?= isset($parentId) ? $parentId : ''; ?></h3>
                </div>
                <div class="col-sm-6 text-right">
                  <a href="<?= generate_request("index.php", 'get', ['comments', ActionConst::EDITCOMMENT, isset($parentId) ? $parentId : 0])['link']; ?>" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left" aria-hidden="true"></i> Back to Comment
                  </a>
                  <a href="<?= generate_request("index.php", 'get', ['reply', ActionConst::REPLY, isset($parentId) ? $parentId : 0])['link']; ?>" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus" aria-hidden="true"></i> Add Reply
                  </a>
                </div>
              </div>
            </div>
            <!-- /.box-header -->

            <div class="box-body table-responsive">
              <?php if (isset($replies) && !empty($replies)) : ?>
                <table id="scriptlog-table" class="table table-bordered table-striped responsive">
                  <thead>
                    <tr>
                      <th scope="col" style="width: 5%">#</th>
                      <th scope="col" class="hidden-xs" style="width: 15%">Author</th>
                      <th scope="col" style="width: 45%">Reply Content</th>
                      <th scope="col" class="hidden-xs" style="width: 10%">Status</th>
                      <th scope="col" class="hidden-xs">Submitted On</th>
                      <th scope="col" style="width: 100px; text-align: center;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                      $no = 0;
                      foreach($replies as $reply) :
                        $no++;
                    ?>
                      <tr>
                        <th scope="row"><?= $no; ?></th>
                        <td class="hidden-xs"><?= safe_html($reply['comment_author_name']); ?></td>
                        <td>
                          <?= htmlspecialchars(mb_substr($reply['comment_content'], 0, 70)); ?><?= (strlen($reply['comment_content']) > 70) ? '...' : ''; ?>
                        </td>
                        <td class="hidden-xs">
                          <?php
                            $statusClass = 'default';
                            $statusLabel = ucfirst($reply['comment_status']);
                            if ($reply['comment_status'] === 'approved') { $statusClass = 'success'; }
                            elseif ($reply['comment_status'] === 'pending') { $statusClass = 'warning'; }
                            elseif ($reply['comment_status'] === 'spam') { $statusClass = 'danger'; }
                          ?>
                          <span class="label label-<?= $statusClass; ?>"><?= $statusLabel; ?></span>
                        </td>
                        <td class="hidden-xs"><?= time_elapsed_string($reply['comment_date']); ?></td>
                        <td style="white-space: nowrap; text-align: center;">
                          <div class="btn-group">
                            <a href="<?= generate_request("index.php", 'get', ['reply', ActionConst::EDITREPLY, $reply['ID']])['link']; ?>" class="btn btn-warning btn-xs" title="Edit" aria-label="Edit Reply">
                              <i class="fa fa-pencil fa-fw" aria-hidden="true"></i>
                            </a>
                            <a href="javascript:deleteReply('<?= abs((int)$reply['ID']); ?>', '<?= htmlspecialchars($reply['comment_author_name'], ENT_QUOTES); ?>')" class="btn btn-danger btn-xs" title="Delete" aria-label="Delete Reply" role="button">
                              <i class="fa fa-trash-o fa-fw" aria-hidden="true"></i>
                            </a>
                          </div>
                        </td>
                      </tr>
                    <?php 
                      endforeach;
                    ?>
                  </tbody>
                  <tfoot>
                    <tr>
                      <th scope="col" style="width: 5%">#</th>
                      <th scope="col" class="hidden-xs" style="width: 15%">Author</th>
                      <th scope="col" style="width: 45%">Reply Content</th>
                      <th scope="col" class="hidden-xs" style="width: 10%">Status</th>
                      <th scope="col" class="hidden-xs">Submitted On</th>
                      <th scope="col" style="width: 100px; text-align: center;">Actions</th>
                    </tr>
                  </tfoot>
                </table>
              <?php else : ?>
                <div class="alert alert-info">
                  <p>No replies found for this comment.</p>
                  <a href="<?= generate_request("index.php", 'get', ['reply', ActionConst::REPLY, isset($parentId) ? $parentId : 0])['link']; ?>" class="btn btn-primary btn-sm">
                    <i class="fa fa-plus" aria-hidden="true"></i> Add First Reply
                  </a>
                </div>
              <?php endif; ?>
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
  function deleteReply(id, name)
  {
	  if (confirm("Are you sure want to delete reply from '" + name + "'"))
	  {
	  	window.location.href = 'index.php?load=reply&action=deleteReply&Id=' + id;
	  }
  }
</script>
