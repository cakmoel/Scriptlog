<?php if (!defined('SCRIPTLOG')) exit(); ?>

<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small>Control Panel</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i>Home</a></li>
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
<h2><i class="icon fa fa-warning"></i> Invalid Form Data!</h2>
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
<h2><i class="icon fa fa-ban"></i> Alert!</h2>
<?php 
echo "Error saving data. Please try again." . $saveError;
?>
</div>
<?php 
endif;

$action = isset($formAction) ? $formAction : null;
?>

<form method="post" action="<?= generate_request('index.php', 'post', ['templates', $action, 0])['link'];?>" role="form" onsubmit="return(mandatoryThemeUpload());" enctype="multipart/form-data" autocomplete="off">
<input type="hidden" name="MAX_FILE_SIZE" value="<?= scriptlog_upload_filesize(); ?>" >

<div class="box-body">

<div class="form-group">
<label for="themeUploaded">Upload Theme (required)</label>
<input type="file"  name="zip_file" id="themeUploaded" accept="application/zip,application/x-zip,application/x-zip-compressed" required>
<p class="help-block">If you have a theme in a .zip format, you may install it by uploading it here.</p>
</div>

</div>
<!-- /.box-body -->
<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<input type="submit" name="themeFormSubmit" class="btn btn-primary" value="Install Now">

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
document.getElementById('themeUploaded').addEventListener('change', checkFile, false);

function checkFile(e) {

    var file_list = e.target.files;

    for (var i = 0, file; file = file_list[i]; i++) {
        var sFileName = file.name;
        var sFileExtension = sFileName.split('.')[sFileName.split('.').length - 1].toLowerCase();
        var iFileSize = file.size;
        var iConvert = (file.size / 10485760).toFixed(2);

        if (!(sFileExtension === "zip") || iFileSize > 10485760) {
            txt = "File type : " + sFileExtension + "\n\n";
            txt += "Size: " + iConvert + " MB \n\n";
            txt += "Please make sure your file is in .zip format and less than <?= format_size_unit(10485760); ?>.\n\n";
            alert(txt);
        }
    }
}
</script>