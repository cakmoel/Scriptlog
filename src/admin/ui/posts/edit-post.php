<?php if (!defined('SCRIPTLOG')) exit(); ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
<section class="content-header">
<h1><?=(isset($pageTitle)) ? $pageTitle : ""; ?>
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
<!-- left column -->
<div class="col-md-8">
<!-- general form elements -->
<div class="box box-primary">
<div class="box-header with-border"></div>
<!-- /.box-header -->
<?php
if (isset($errors)) :
?>
<div class="alert alert-danger alert-dismissible">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
<h2><i class="icon fa fa-warning"></i> Invalid Form Data!</h2>

<?php 
foreach ($errors as $e) :
echo '<p>' . $e . '</p>';
endforeach;
?>

</div>

<?php 
endif;

$action = isset($formAction) ? $formAction : null;
$post_id = isset($postData['ID']) ? (int)$postData['ID'] : 0;

?>

<!-- form start -->
<form method="post" action="<?=generate_request('index.php', 'post', ['posts', $action, $post_id])['link']; ?>" role="form" enctype="multipart/form-data" >
<input type="hidden" name="post_id" value="<?= $post_id; ?>" />
<input type="hidden" name="MAX_FILE_SIZE" value="<?=APP_FILE_SIZE;?>">

<div class="box-body">
              
<div class="form-group">
<label for="title">Title (required)</label>
<input type="text" class="form-control" id="title" name="post_title" placeholder="Enter title here" value="
<?=(isset($postData['post_title'])) ? safe_html($postData['post_title']) : ""; ?>
<?=(isset($formData['post_title'])) ? safe_html($formData['post_title']) : ""; ?>" maxlength="200" required>
</div>
                
<div class="form-group">
<label for="summernote">Content (required)</label>
<textarea class="form-control" id="summernote" name="post_content" rows="10" cols="80" maxlength="50000" required>
<?=(isset($postData['post_content']) ? safe_html($postData['post_content']) : ""); ?>
<?=(isset($formData['post_content']) ? safe_html($formData['post_content']) : "") ; ?>
</textarea>
</div>
                
<div class="form-group">
<label>Featured post</label>

<div class="radio">
<label for="headlines">
<input type="radio" id="headlines" name="post_headlines" class="flat-red" value="1" 
<?=(isset($postData['post_headlines']) && $postData['post_headlines'] === 1 ? " checked" : ""); ?>
<?=(isset($formData['post_headlines']) && $formData['post_headlines'] === 1 ? " checked" : ""); ?>> Yes
</label>
</div>

<div class="radio">
<label for="sticky">
<input type="radio" id="sticky" name="post_headlines" class="flat-red" value="0" 
<?=(isset($postData['post_headlines']) && $postData['post_headlines'] === 0 ? " checked" : ""); ?>
<?=(isset($formData['post_headlines']) && $formData['post_headlines'] === 0 ? " checked" : ""); ?>> No
</label>
</div>

</div>
              
<div class="form-group">
<label for="datepicker">Date</label>
<div class="input-group date">
<div class="input-group-addon">
<i class="fa fa-calendar"></i>
</div>

<input type="text" id="datetimepicker" name="<?=(isset($postData['post_modified']) ? "post_modified" : "post_date"); ?>" class="form-control" placeholder="Date" value="
<?php 
if (isset($postData['post_modified']) || isset($postData['post_date']) ) {

   if ($postData['post_modified'] === null ) {

      echo make_date(safe_html($postData['post_date']));

   } else {

      echo make_date(safe_html($postData['post_modified']));

   }

}
?>">

</div>
</div>

</div>
<!-- /.box-body -->
</div>
<!-- /.box-primary -->
</div>
<!--/.col (left) -->
        
<!-- right column -->
<div class="col-md-4">
                    
<!-- general form elements disabled -->
<div class="box box-info">
<div class="box-header with-border"></div>
<!-- /.box-header -->
<div class="box-body">
<!-- text input -->

<?=(isset($topics)) ? $topics : ""; ?>

<div class="form-group">
<label for="meta_desc">Meta Description</label>
<textarea class="form-control" id="meta_desc" rows="3" placeholder="Enter ..." name="post_summary" maxlength="320" >
<?=(isset($postData['post_summary'])) ? safe_html($postData['post_summary']) : ""; ?>
<?=(isset($formData['post_summary'])) ? safe_html($formData['post_summary']) : ""; ?>
</textarea>
<p class="help-block">Maximum 320 characters</p>
</div>

<div class="form-group">
<label for="meta_key">Meta Keywords</label>
<textarea class="form-control" id="meta_key" rows="3" placeholder="Enter ..." name="post_keyword" maxlength="200" >
<?=(isset($postData['post_keyword'])) ? safe_html($postData['post_keyword']) : ""; ?>
<?=(isset($formData['post_keyword'])) ? safe_html($formData['post_keyword']) : ""; ?>
</textarea>
<p class="help-block">Maximum 200 characters</p>
</div>

<div class="form-group">
<label for="tag">Tags</label>
<input type="text" class="form-control" id="tag" name="post_tags" placeholder="Enter tags here" value="
<?=(isset($postData['post_tags'])) ? safe_html($postData['post_tags']) : ""; ?>
<?=(isset($formData['post_tags'])) ? safe_html($formData['post_tags']) : ""; ?>" maxlength="400">
<p class="help-block">Comma separated</p>
</div>

<?=(isset($medialibs)) ? $medialibs : "Media Not Found"; ?>

<div class="form-group">
<label for="post_status">Post status</label>
<?=(isset($postStatus)) ? $postStatus : ""; ?>
</div>

<div class="form-group">
<label for="comment_status">Comment status</label>
<?=(isset($commentStatus)) ? $commentStatus : ""; ?>
</div>

<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<input type="submit" class="btn btn-primary" name="postFormSubmit" value="<?=(isset($post_id) && ($post_id != '')) ? "Update" : "Publish"; ?>" >
</div>
             
</form>
</div>
<!-- /.box-body -->
</div>
<!-- /.box -->
</div>
<!--/.col (right) -->
</div>
<!-- /.row -->
</section>
<!-- /.content -->
</div>
<!-- /.content-wrapper -->
<script type="text/javascript">
  var loadFile = function(event) {
	  var output = document.getElementById('output');
	      output.src = URL.createObjectURL(event.target.files[0]);
	  };
</script>