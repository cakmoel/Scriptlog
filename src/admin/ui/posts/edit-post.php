<?php if (!defined('SCRIPTLOG')) exit(); ?>

<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small>Control Panel</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=posts">Posts</a></li>
        <li class="active"><?=(isset($pageTitle)) ? $pageTitle : ""; ?></li>
      </ol>
    </section>

 <!-- Main content -->
<section class="content">
<div class="row">
<div class="col-md-12">
<div class="box box-primary">
<div class="box-header with-border"></div>
<!-- /.box-header -->
<?php
if (isset($errors)) :
?>
<div class="alert alert-danger alert-dismissible">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
<h4><i class="icon fa fa-warning"></i> Invalid Form Data!</h4>
<?php 
foreach ($errors as $e) :
echo '<p>' . $e . '</p>';
endforeach;
?>
</div>
<?php 
endif;
?>

<?php
if (isset($saveError)) :
?>
<div class="alert alert-danger alert-dismissible">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
<h4><i class="icon fa fa-ban"></i> Alert!</h4>
<?php 
echo "Error saving data. Please try again." . $saveError;
?>
</div>
<?php 
endif;
?>

<?php
$action = isset($formAction) ? $formAction : null;
$post_id = isset($postData['ID']) ? (int)$postData['ID'] : 0;
?>
<form method="post" action="<?=generate_request('index.php', 'post', ['posts', $action, $post_id])['link']; ?>" role="form" enctype="multipart/form-data" >
<input type="hidden" name="post_id" value="<?= $post_id; ?>" />
<input type="hidden" name="MAX_FILE_SIZE" value="697856"/>

<div class="box-body">
<div class="form-group">
<label>Title (required)</label>
<input type="text" class="form-control" name="post_title" placeholder="Enter title here" value="
<?=(isset($postData['post_title'])) ? safe_html($postData['post_title']) : ""; ?>
<?=(isset($formData['post_title'])) ? safe_html($formData['post_title']) : ""; ?>" maxlength="200" required>
</div>

<div class="form-group">
<label>Meta Description</label>
<textarea class="form-control" name="post_summary" rows="3" maxlength="320" >
<?=(isset($postData['post_summary'])) ? safe_html($postData['post_summary']) : ""; ?>
<?=(isset($formData['post_summary'])) ? safe_html($formData['post_summary']) : ""; ?>
</textarea>
<p class="help-block">Maximum 320 characters</p>
</div>

<div class="form-group">
<label>Meta Keywords</label>
<textarea class="form-control" name="post_keyword" rows="3" maxlength="200" >
<?=(isset($postData['post_keyword'])) ? safe_html($postData['post_keyword']) : ""; ?>
<?=(isset($formData['post_keyword'])) ? safe_html($formData['post_keyword']) : ""; ?>
</textarea>
<p class="help-block">Maximum 200 characters</p>
</div>

<div class="form-group">
<label>Content (required)</label>
<textarea class="textarea" placeholder="Place some text here"
style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;" 
name="post_content"  maxlength="10000"  required>
<?=(isset($postData['post_content'])) ? safe_html($postData['post_content']) : ""; ?>
<?=(isset($formData['post_content'])) ? safe_html($formData['post_content']) : ""; ?>
</textarea>
</div>

<?=(isset($topics)) ? $topics : ""; ?>

<div class="form-group">
<label>Post status</label>
<?=(isset($postStatus)) ? $postStatus : ""; ?>
</div>
<!-- /.post status -->

<div class="form-group">
<label>Comment status</label>
<?=(isset($commentStatus)) ? $commentStatus : ""; ?>
</div>
<!-- /.comment status -->

<?=(isset($medialibs)) ? $medialibs : "Media Not Found"; ?>

<!-- /.box-body -->
<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<input type="submit" class="btn btn-primary" name="postFormSubmit" value="<?=(isset($post_id) && ($post_id != '')) ? "Update" : "Publish"; ?>" >
</div>
</form>
            
</div>
<!-- /.box -->
</div>
<!-- /.col-md-12 -->
</div>
<!-- /.row --> 
</section>

</div>
<!-- /.content-wrapper --->

<script type="text/javascript">
  var loadFile = function(event) {
	  var output = document.getElementById('output');
	      output.src = URL.createObjectURL(event.target.files[0]);
	  };
</script>
