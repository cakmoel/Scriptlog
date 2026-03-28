<?php if (!defined('SCRIPTLOG')) { exit(); } ?>

<?php
$action = isset($formAction) ? $formAction : null;
$page_id = isset($pageData['ID']) ? (int)$pageData['ID'] : 0;
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
<section class="content-header">
<h1><?=(isset($pageTitle)) ? $pageTitle : ""; ?> <small>Control Panel</small></h1>
  <ol class="breadcrumb">
      <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard" aria-hidden="true"></i> Home </a></li>
      <li><a href="index.php?load=pages">Pages </a></li>
      <li class="active"> <?= (isset($pageTitle)) ? $pageTitle : ""; ?></li>
   </ol>
</section>

<!-- Main content -->
<section class="content">
<div class="row">
<!-- left column -->
<div class="col-md-8">
<!-- general form elements -->
<div class="box box-primary">
<div class="box-header with-border">
  <h3 class="box-title">Page Content</h3>
</div>
<!-- /.box-header -->
<?php
if (isset($errors)) :
?>
<div class="alert alert-danger alert-dismissible" role="alert">
<button type="button" class="close" data-dismiss="alert" aria-label="Close" aria-hidden="true">&times;</button>
<h4><i class="icon fa fa-warning" aria-hidden="true"></i> Invalid Form Data!</h4>

<?php 
foreach ($errors as $e) :
echo '<p>' . $e . '</p>';
endforeach;
?>

</div>

<?php 
endif;
?>

<!-- form start -->
<form method="post" action="<?=generate_request('index.php', 'post', ['pages', $action, $page_id])['link']; ?>" role="form" enctype="multipart/form-data" >
<input type="hidden" name="page_id" value="<?= $page_id; ?>" />
<input type="hidden" name="MAX_FILE_SIZE" value="<?=APP_FILE_SIZE;?>">

<div class="box-body">

<div class="form-group">
<label for="title">Title <span class="text-red" title="required">*</span></label>
<input type="text" class="form-control" id="title" name="post_title" placeholder="e.g. About Us, Contact, Privacy Policy" value="<?=(isset($pageData['post_title'])) ? safe_html($pageData['post_title']) : ""; ?><?=(isset($formData['post_title'])) ? safe_html($formData['post_title']) : ""; ?>" maxlength="200" required aria-required="true">
</div>

<div class="form-group">
<label for="summernote">Content <span class="text-red" title="required">*</span></label>
<textarea class="form-control" id="summernote" name="post_content" rows="10" cols="80" maxlength="50000" required aria-required="true"><?=(isset($pageData['post_content']) ? safe_html($pageData['post_content']) : ""); ?><?=(isset($formData['post_content']) ? safe_html($formData['post_content']) : "") ; ?></textarea>
</div>

<div class="form-group">
<label>Stick to the top of the blog</label>
<p class="help-block">This will keep the page link prominently displayed in your navigation.</p>
<div class="radio">
<label for="sticky_yes">
<input type="radio" id="sticky_yes" name="post_sticky" class="flat-red" value="1" 
<?=(isset($pageData['post_sticky']) && $pageData['post_sticky'] === 1 ? " checked" : ""); ?>
<?=(isset($formData['post_sticky']) && $formData['post_sticky'] === 1 ? " checked" : ""); ?>> Yes, pin this page
</label>
</div>

<div class="radio">
<label for="sticky_no">
<input type="radio" id="sticky_no" name="post_sticky" class="flat-red" value="0" 
<?=(isset($pageData['post_sticky']) && $pageData['post_sticky'] === 0 ? " checked" : ""); ?>
<?=(isset($formData['post_sticky']) && $formData['post_sticky'] === 0 ? " checked" : ""); ?>> No, standard page
</label>
</div>

</div>


<div class="form-group">
<label for="datetimepicker">Publication Date</label>
<div class="input-group date">
<div class="input-group-addon">
<i class="fa fa-calendar" aria-hidden="true"></i>
</div>

<input type="text" id="datetimepicker" name="<?=(isset($pageData['post_modified']) ? "post_modified" : "post_date"); ?>" class="form-control" placeholder="YYYY-MM-DD HH:MM:SS" value="<?php 
if (isset($pageData['post_modified']) || isset($pageData['post_date']) ) {
   if ($pageData['post_modified'] === null ) {
      echo make_date(safe_html($pageData['post_date']));
   } else {
      echo make_date(safe_html($pageData['post_modified']));
   }
}
?>">

</div>
<p class="help-block">Set the date this page will be publicly available.</p>
</div>

<?=(isset($medialibs)) ? $medialibs : "Media Not Found"; ?>
<!-- ./image-radio-button form-group -->

</div>
<!-- /.box-body -->
</div>
<!-- /.box-primary -->
</div>
<!--/.col-md-8 (left) -->

<!-- right column -->
<div class="col-md-4">

<!-- general form elements disabled -->
<div class="box box-info">
<div class="box-header with-border">
  <h3 class="box-title">Page Settings</h3>
</div>
<!-- /.box-header -->
<div class="box-body">
<!-- text input -->
<div class="form-group">
<label for="meta_desc">Meta Description</label>
<textarea class="form-control" id="meta_desc" rows="3" placeholder="Brief summary for search engines..." name="post_summary" maxlength="320" aria-describedby="metaDescHelp"><?=(isset($pageData['post_summary'])) ? safe_html($pageData['post_summary']) : ""; ?><?=(isset($formData['post_summary'])) ? safe_html($formData['post_summary']) : ""; ?></textarea>
<p id="metaDescHelp" class="help-block">Maximum 320 characters. This summary appears in search results.</p>
</div>

<div class="form-group">
<label for="post_status">Page status</label>
<?=(isset($postStatus)) ? $postStatus : ""; ?>
</div>

<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<a href="index.php?load=pages" class="btn btn-default" role="button" aria-label="Cancel and return to pages list">
  <i class="fa fa-times" aria-hidden="true"></i> Cancel
</a>
<button type="submit" class="btn btn-primary pull-right" name="pageFormSubmit" aria-label="<?=((isset($page_id)) && ($page_id === 0) ? "Publish Page" : "Update Page") ; ?>">
  <i class="fa <?= ((isset($page_id)) && ($page_id === 0) ? "fa-paper-plane" : "fa-save"); ?>" aria-hidden="true"></i> 
  <?=((isset($page_id)) && ($page_id === 0) ? "Publish" : "Update") ; ?>
</button>
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