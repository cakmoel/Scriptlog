<?php if (!defined('SCRIPTLOG')) exit(); ?>

<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small><a href="index.php?load=templates&action=installTheme&Id=0" class="btn btn-primary"><i class="fa fa-cloud-upload"></i> Upload Theme</a></small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=templates">Themes</a></li>
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
$action = (isset($formAction)) ? $formAction : null;
$theme_id = (isset($themeData['ID'])) ? safe_html((int)$themeData['ID']) : 0;
?>
<form method="post" action="<?=generate_request('index.php', 'post', ['templates', $action, $theme_id])['link']; ?>" role="form">
<input type="hidden" name="theme_id" value="<?= $theme_id; ?>" />

<div class="box-body">

<div class="form-group">
<label>Theme (required)</label>
<input type="text" class="form-control" name="theme_title" placeholder="Enter theme name here" value="
<?=(isset($themeData['theme_title'])) ? safe_html($themeData['theme_title']) : ""; ?>
<?=(isset($formData['theme_title'])) ? safe_html($formData['theme_title']) : ""; ?>" required>
</div>

<div class="form-group">
<label>Directory/Folder (required)</label>
<input type="text" class="form-control" name="theme_directory" placeholder="/public/themes/yourthemefolder" value="
<?=(isset($themeData['theme_directory'])) ? safe_html($themeData['theme_directory']) : ""; ?>
<?=(isset($formData['theme_directory'])) ? safe_html($formData['theme_directory']) : ""; ?>">
<p class="help-block">Fill in the directory with the format: public/themes/yourthemedirectory</p>
</div>

<div class="form-group">
<label>Description </label>
<textarea class="textarea" placeholder="Place some text here"  
style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;"  
name="theme_description"  maxlength="1000" >
<?=(isset($themeData['theme_desc'])) ? safe_html($themeData['theme_desc']) : ""; ?>
<?=(isset($formData['theme_description'])) ? safe_html($formData['theme_description']) : ""; ?>
</textarea>
</div>

<div class="form-group">
<label>Designer (required)</label>
<input type="text" class="form-control" name="theme_designer" placeholder="Designer's name" value="
<?=(isset($themeData['theme_designer'])) ? safe_html($themeData['theme_designer']) : ""; ?>
<?=(isset($formData['theme_designer'])) ? safe_html($formData['theme_designer']) : ""; ?>" required>
</div>

</div>
<!-- /.box-body -->
<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<input type="submit" name="themeFormSubmit" class="btn btn-primary" value="<?=(($theme_id) && ($theme_id != '')) ? "Update" : "Add New Theme"; ?>">
<?php 
 if(!empty($themeData['ID'])) :
?>
<a href="javascript:deleteTheme('<?=(isset($themeData['ID']) ?  safe_html((int)$themeData['ID']) : 0); ?>', '<?=(isset($themeData['theme_title']) ? safe_html($themeData['theme_title']) : ""); ?>')"
title="Delete Theme" class="btn btn-danger pull-right"> <i
class="fa fa-exclamation-circle fa-fw"></i> Delete
</a>
<?php 
 endif;
?>

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
  function deleteTheme(id, theme)
  {
	  if (confirm("Are you sure want to uninstall Theme '" + theme + "'"))
	  {
	  	window.location.href = 'index.php?load=templates&action=deleteTheme&Id=' + id;
	  }
  }
</script>