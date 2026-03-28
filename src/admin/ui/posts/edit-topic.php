<?php if (!defined('SCRIPTLOG')) exit(); ?>

<?php
$action = (isset($formAction)) ? $formAction : null;
$topic_id = (isset($topicData)) ? abs((int)$topicData['ID']) : 0;
?>

<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small>Control Panel</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard" aria-hidden="true"></i> Home </a></li>
        <li><a href="index.php?load=topics">Categories </a></li>
        <li class="active"><?=(isset($pageTitle)) ? $pageTitle : ""; ?></li>
      </ol>
    </section>

 <!-- Main content -->
<section class="content">
<div class="row">
<div class="col-md-6">
<div class="box box-primary">
<div class="box-header with-border">
  <h3 class="box-title"><?=(($topic_id) && ($topic_id != '')) ? "Edit Category" : "Add New Category" ?></h3>
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

<form method="post" action="<?=generate_request('index.php', 'post', ['topics', $action, $topic_id])['link']?>">
<input type="hidden" name="topic_id" value="<?=$topic_id; ?>">

<div class="box-body">
<div class="form-group">
<label for="title">Title <span class="text-red" title="required">*</span></label>
<input type="text" class="form-control" id="title" name="topic_title" placeholder="e.g. Technology, Lifestyle, News" value="<?=(isset($topicData['topic_title'])) ? safe_html($topicData['topic_title']) : ""; ?><?=(isset($formData['topic_title'])) ? safe_html($formData['topic_title']) : ""; ?>" required aria-required="true">
<p class="help-block">Enter a unique and descriptive name for this category.</p>
</div>

<?php if (isset($topicData['topic_status'])) : ?>
  
<div class="form-group">
<label>Active Status</label>
<p class="help-block">Disable this to hide all posts in this category from the public view.</p>
<div class="radio">
<label for="optionsRadios1">
<input type="radio" name="topic_status" id="optionsRadios1" value="Y" 
<?=(isset($topicData['topic_status']) && $topicData['topic_status'] === 'Y') ? 'checked="checked"' : "";  ?>
<?=(isset($formData['topic_status']) && $formData['topic_status'] === 'Y') ? 'checked="checked"' : "" ?>>
   Yes, keep this category active
 </label>
</div>

<div class="radio">
<label for="optionsRadios2">
<input type="radio" name="topic_status" id="optionsRadios2" value="N" 
<?=(isset($topicData['topic_status']) && $topicData['topic_status'] === 'N') ? 'checked="checked"' : ""; ?>
<?=(isset($formData['topic_status']) && $formData['topic_status'] === 'N') ? 'checked="checked"' : ""; ?>>
   No, deactivate this category
 </label>
</div>

</div>

<?php 
endif;
?>

</div>
<!-- /.box-body -->

<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<a href="index.php?load=topics" class="btn btn-default" role="button" aria-label="Cancel and return to categories list">
  <i class="fa fa-times" aria-hidden="true"></i> Cancel
</a>
<button type="submit" name="topicFormSubmit" class="btn btn-primary pull-right" aria-label="<?=(($topic_id) && ($topic_id != '')) ? "Update Category" : "Add New Category" ?>">
  <i class="fa <?= (($topic_id) && ($topic_id != '')) ? "fa-save" : "fa-plus"; ?>" aria-hidden="true"></i> 
  <?=(($topic_id) && ($topic_id != '')) ? "Update" : "Add Category" ?>
</button>
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