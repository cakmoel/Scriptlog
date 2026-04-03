<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>

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
        <li class="active"><?=(isset($pageTitle)) ? $pageTitle : ""; ?></li>
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

        <div class="col-md-8">
          <div class="box box-primary">
            <div class="box-header with-border">
              <h3 class="box-title">
                <?php if (isset($replyData['ID'])) : ?>
                  Edit Reply
                <?php else : ?>
                  Reply to Comment
                <?php endif; ?>
              </h3>
            </div>
            <!-- /.box-header -->
            
            <?php
            $action = isset($formAction) ? $formAction : ActionConst::REPLY;
            $reply_id = isset($replyData['ID']) ? (int)$replyData['ID'] : 0;
            $parent_comment_id = isset($parentComment['ID']) ? (int)$parentComment['ID'] : (isset($replyData['comment_parent_id']) ? (int)$replyData['comment_parent_id'] : 0);
            ?>

            <form method="post" action="<?=generate_request('index.php', 'post', ['reply', $action, $reply_id])['link'];?>" role="form">
              <input type="hidden" name="parent_comment_id" value="<?= $parent_comment_id; ?>">

              <div class="box-body">
                <div class="form-group">
                  <label for="author_name">Author Name <span class="text-red">*</span></label>
                  <input type="text" class="form-control" id="author_name" name="author_name" placeholder="Enter your name" value="<?=(isset($replyData['comment_author_name'])) ? htmlspecialchars($replyData['comment_author_name']) : ""; ?>" required maxlength="60">
                </div>

                <div class="form-group">
                  <label for="reply_content">Reply Content <span class="text-red">*</span></label>
                  <textarea class="form-control" id="reply_content" name="reply_content" rows="5" placeholder="Enter your reply..." required maxlength="1000" aria-describedby="replyContentHelp"><?=(isset($replyData['comment_content'])) ? $replyData['comment_content'] : ""; ?></textarea>
                  <p id="replyContentHelp" class="help-block">Maximum 1000 characters</p>
                </div>

                <div class="form-group">
                  <label for="reply_status">Reply Status</label>
                  <?=(isset($replyStatus)) ? $replyStatus : ""; ?>
                </div>
              </div>
              <!-- /.box-body -->

              <div class="box-footer">
                <input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
                <a href="<?= generate_request("index.php", 'get', ['comments', ActionConst::EDITCOMMENT, $parent_comment_id])['link']; ?>" class="btn btn-default pull-left">
                  <i class="fa fa-times" aria-hidden="true"></i> Cancel
                </a>
                <button type="submit" name="replyFormSubmit" class="btn btn-primary pull-right">
                  <?php if (isset($replyData['ID']) && $replyData['ID'] != '') : ?>
                    <i class="fa fa-save" aria-hidden="true"></i> Update Reply
                  <?php else : ?>
                    <i class="fa fa-reply" aria-hidden="true"></i> Submit Reply
                  <?php endif; ?>
                </button>
              </div>
            </form>
          </div>
          <!-- /.box -->
        </div>
        <!-- /.col-md-8 -->

        <div class="col-md-4">
          <!-- Parent Comment Info -->
          <div class="box box-info">
            <div class="box-header with-border">
              <h3 class="box-title">Parent Comment Information</h3>
            </div>
            <div class="box-body">
              <?php if (isset($parentComment) && !empty($parentComment)) : ?>
                <div class="form-group">
                  <label><i class="fa fa-user" aria-hidden="true"></i> Author</label>
                  <p class="text-muted"><?= safe_html($parentComment['comment_author_name']); ?></p>
                </div>
                <div class="form-group">
                  <label><i class="fa fa-file-text-o" aria-hidden="true"></i> Content</label>
                  <div class="well well-sm" style="background-color: #f9f9f9; border-left: 3px solid #00c0ef;">
                    <?= safe_html($parentComment['comment_content']); ?>
                  </div>
                </div>
                <div class="form-group">
                  <label><i class="fa fa-calendar-check-o" aria-hidden="true"></i> Submitted On</label>
                  <p class="text-muted">
                    <?= (isset($parentComment['comment_date'])) ? human_readable_datetime(new DateTime($parentComment['comment_date']), 'g:i a \o\n l jS F Y') . "<br><small class='text-primary'>" . time_elapsed_string($parentComment['comment_date']) . "</small>" : ""; ?>
                  </p>
                </div>
                <div class="form-group">
                  <label><i class="fa fa-paperclip" aria-hidden="true"></i> In Post</label>
                  <p class="text-muted"><?= safe_html($parentComment['post_title']); ?></p>
                </div>
              <?php elseif (isset($replyData['parent_comment_author']) && !empty($replyData['parent_comment_author'])) : ?>
                <div class="form-group">
                  <label><i class="fa fa-user" aria-hidden="true"></i> Parent Author</label>
                  <p class="text-muted"><?= safe_html($replyData['parent_comment_author']); ?></p>
                </div>
                <div class="form-group">
                  <label><i class="fa fa-file-text-o" aria-hidden="true"></i> Parent Content</label>
                  <div class="well well-sm" style="background-color: #f9f9f9; border-left: 3px solid #00c0ef;">
                    <?= safe_html($replyData['parent_comment_content']); ?>
                  </div>
                </div>
              <?php else : ?>
                <div class="callout callout-warning">
                  <p>Parent comment information not available</p>
                </div>
              <?php endif; ?>
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->
          
          <?php if (isset($replyData['comment_date'])) : ?>
          <div class="box box-default">
            <div class="box-header with-border">
              <h3 class="box-title"><i class="fa fa-info-circle" aria-hidden="true"></i> Reply Info</h3>
            </div>
            <div class="box-body">
              <div class="form-group">
                <label><i class="fa fa-clock-o" aria-hidden="true"></i> Submitted On</label>
                <p class="text-muted">
                  <?= human_readable_datetime(new DateTime($replyData['comment_date']), 'g:i a \o\n l jS F Y'); ?>
                  <br>
                  <small class="text-primary"><?= time_elapsed_string($replyData['comment_date']); ?></small>
                </p>
              </div>
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->
          <?php endif; ?>
        </div>
        <!-- /.col-md-4 -->
      </div>
      <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
