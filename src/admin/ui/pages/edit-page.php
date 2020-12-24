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
        <li><a href="index.php?load=pages">Pages</a></li>
        <li class="active"><?=(isset($pageTitle)) ? $pageTitle : ""; ?></li>
      </ol>
    </section>

 <!-- Main content -->
<section class="content">
<div class="row">
<div class="col-md-8">
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
echo '<p><i class="icon fa fa-ban"></i>' . $e . '</p>';
endforeach;
?>
</div>
<?php 
endif;
?>

<?php 
$action = isset($formAction) ? $formAction : nul;
$page_id = isset($pageData['ID']) ? (int)$pageData['ID'] : 0;
?>
<form method="post" action="<?=generate_request('index.php', 'post', ['pages', $action, $page_id])['link']; ?>" role="form" enctype="multipart/form-data" autocomplete="off">
<input type="hidden" name="page_id" value="<?= $page_id; ?>" />
<input type="hidden" name="MAX_FILE_SIZE" value="<?=APP_FILE_SIZE; ?>" >

<div class="box-body">
<div class="form-group">
<label for="title">Title (required)</label>
<input type="text" class="form-control" id="title" name="post_title" placeholder="Enter title here" value="
<?=(isset($pageData)) ? safe_html($pageData['post_title']) : ""; ?>
<?=(isset($formData['post_title'])) ? safe_html($formData['post_title']) : ""; ?>" maxlength="200" required>
</div>

<?=(isset($medialibs)) ? $medialibs : "Media Not Found"; ?>

<div class="form-group">
<label for="meta_desc">Meta Description</label>
<textarea class="form-control" id="meta_desc" name="post_summary" rows="3" maxlength="320" >
<?=(isset($pageData['post_summary'])) ? safe_html($pageData['post_summary']) : ""; ?>
<?=(isset($formData['post_summary'])) ? safe_html($formData['post_summary']) : ""; ?>
</textarea>
</div>

<div class="form-group">
<label for="meta_key">Meta Keywords</label>
<textarea class="form-control" id="meta_key" name="post_keyword" rows="3" maxlength="200" >
<?=(isset($pageData['post_keyword'])) ? safe_html($pageData['post_keyword']) : ""; ?>
<?=(isset($formData['post_keyword'])) ? safe_html($formData['post_keyword']) : ""; ?>
</textarea>
</div>

<div class="checkbox">
<label for="sticky">
  <input type="checkbox" id="sticky" name="post_sticky" <?=(isset($pageData['post_sticky']) && $pageData['post_sticky'] == 1) ? "checked='checked'" : "";?>> Stick to the top of the blog
</label>
</div>

<div class="form-group">
<label for="summernote">Content (required)</label>
<textarea class="form-control" id="summernote" name="post_content" rows="10" cols="80" maxlength="50000" required>
<?=(isset($pageData['post_content'])) ? safe_html($pageData['post_content']) : ""; ?>
<?=(isset($formData['post_content'])) ? safe_html($formData['post_content']) : ""; ?>
</textarea>
</div>

<div class="form-group">
<label for="post_status">Page status</label>
<?=(isset($postStatus)) ? $postStatus : ""; ?>
</div>
<!-- /.post status -->

<div class="form-group">
<label for="comment_status">Comment status</label>
<?=(isset($commentStatus)) ? $commentStatus : ""; ?>
</div>
<!-- /.comment status -->

</div>
<!-- /.box-body -->

<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<input type="submit" name="pageFormSubmit" class="btn btn-primary" value="<?=(isset($pageData['ID']) && $pageData['ID'] != '') ? "Update" : "Publish"; ?>" >
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
<!-- /.content-wrapper -->
<script type="text/javascript">
  var loadFile = function(event) {
	    var output = document.getElementById('output');
	    output.src = URL.createObjectURL(event.target.files[0]);
	  };
</script>