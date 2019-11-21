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
        <li><a href="index.php?load=media">Media</a></li>
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

<form method="post" action="index.php?load=medialib&action=<?=(isset($formAction)) ? $formAction : null; ?>&mediaId=0" 
  role="form" enctype="multipart/form-data" autocomplete="off" >
   
<input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
<div class="box-body">
<div class="form-group">
<label>Select file  (required)</label>
<input type="file"  name="media" id="mediaUploaded" required>
<p class="help-block">Maximum upload file size: <?= format_size_unit(697856); ?>.</p>
</div>

<div class="form-group">
<label>Caption </label>
<input type="text" class="form-control" name="media_caption" placeholder="type media caption" value="
<?=(isset($mediaData['media_caption'])) ? htmlspecialchars($pluginData['media_caption']) : ""; ?>
<?=(isset($formData['media_caption'])) ? htmlspecialchars($formData['media_caption'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>" >
</div>

<div class="form-group">
<label>Media will be shown on</label>
<?=(isset($mediaTarget)) ? $mediaTarget : ""; ?>
</div>
<!-- media target -->

<div class="form-group">
<label>Access</label>
<?=(isset($mediaAccess)) ? $mediaAccess : ""; ?>
</div>
<!-- media access -->

<?php if(isset($mediaData['media_status'])) : ?>

<div class="form-group">
<label>Actived</label>
<div class="radio">
<label>
<input type="radio" name="media_status" id="optionsRadios1" value="Y" 
<?=(isset($mediaData['media_status']) && $mediaData['media_status'] === 'Y') ? 'checked="checked"' : "";  ?>
<?=(isset($formData['media_status']) && $formData['media_status'] === 'Y') ? 'checked="checked"' : "" ?>>
   Yes
 </label>
</div>

<div class="radio">
<label>
<input type="radio" name="media_status" id="optionsRadios1" value="N" 
<?=(isset($mediaData['media_status']) && $mediaData['media_status'] === 'N') ? 'checked="checked"' : ""; ?>
<?=(isset($formData['media_status']) && $formData['media_status'] == 'N') ? 'checked="checked"' : ""; ?>>
   No
 </label>
</div>

</div>

<?php endif; ?>

</div>
<!-- /.box-body -->
<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<input type="submit" name="mediaFormSubmit" class="btn btn-primary" value="Upload">

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

</script>