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
        <li class="active"><?=(isset($pageTitle)) ? $pageTitle : ""; ?></li>
      </ol>
    </section>

 <!-- Main content -->
<section class="content">
<div class="row">
<div class="col-md-6">
<div class="box box-primary">
<div class="box-header with-border"></div>
<!-- /.box-header -->
<?php
 if (isset($errors)) :
?>
<div class="alert alert-danger alert-dismissible">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
<h2><i class="icon fa fa-warning" aria-hidden="true"></i> Invalid Form Data!</h2>
<?php 
 foreach ($errors as $e) :
  echo '<p>' . $e . '</p>';
 endforeach;
?>
</div>
<?php 
 endif;

$action = isset($formAction) ? $formAction : null;
$comment_id = isset($commentData['ID']) ? (int)$commentData['ID'] : 0;
?>

<form method="post" action="<?=generate_request('index.php', 'post', ['comments', $action, $comment_id])['link'];?>" role="form">
<input type="hidden" name="comment_id" value="<?=(isset($commentData['ID'])) ? $commentData['ID'] : 0; ?>" >
<input type="hidden" name="post_id" value="<?=(isset($commentData['comment_post_id'])) ? $commentData['comment_post_id'] : 0; ?>" >

<div class="box-body">
<div class="form-group">
<label for="comment_author">Author</label>
<input type="text" class="form-control" id="comment_author" name="author_name" placeholder="" value="
<?=(isset($commentData['comment_author_name'])) ? htmlspecialchars($commentData['comment_author_name']) : ""; ?>" required>
</div>

<div class="form-group">
<label for="comment">Content (required)</label>
<textarea class="form-control" id="comment" rows="3" placeholder="Enter ..." name="comment_content" maxlength="500" >
<?=(isset($commentData['comment_content'])) ? $commentData['comment_content'] : ""; ?>
</textarea>
</div>

<div class="form-group">
<label for="comment_status">Comment status</label>
<?=(isset($commentStatus)) ? $commentStatus : ""; ?>
</div>
<!-- /.comment status -->

</div>
<!-- /.box-body -->

<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<input type="submit" name="commentFormSubmit" class="btn btn-primary" value="<?=(isset($commentData['ID']) && $commentData['ID'] != '') ? "Update" : ""; ?>">
</div>
</form>
            
</div>
<!-- /.box -->
</div>
<!-- /.col-md-6 -->

<div class="col-md-6">
<!-- Form Element sizes -->
    <div class="box box-info">
        <div class="box-header with-border">
              <h3 class="box-title">Response To: <?=(isset($commentData['post_title'])) ? $commentData['post_title'] : ""; ?></h3>
        </div>
        <div class="box-body">
            <div class="form-group">
                <label><i class="fa fa-calendar" aria-hidden="true"></i> Submited On</label>
                <p class="text-aqua"><?=(isset($commentData['comment_date'])) ? human_readable_datetime(new DateTime($commentData['comment_date']), 'g:i a  \o\n l jS F Y') . "<br>" . time_elapsed_string($commentData['comment_date']) : ""; ?>  </p>
            </div>
            <div class="form-group">
                <a href="<?= generate_request("index.php", 'get', ['reply', ActionConst::RESPONSETO, 0])['link']; ?>" class="btn btn-primary"><i class="fa fa-reply fa-fw" aria-hidden="true"></i> </a>
            </div>
        </div>
            <!-- /.box-body -->
        </div>
          <!-- /.box -->
</div>
<!-- /.col-md-6 -->
</div>
<!-- /.row --> 
</section>

</div>
<!-- /.content-wrapper -->
