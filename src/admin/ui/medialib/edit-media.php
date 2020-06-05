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
        <li><a href="index.php?load=medialib">Media</a></li>
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
$action = isset($formAction) ? $formAction : null;
$media_id = isset($mediaData['ID']) ? $mediaData['ID'] : 0;
?>
<form method="post" action="<?=generate_request('index.php', 'post', ['medialib', $action, $media_id])['link'];?>" role="form" enctype="multipart/form-data" autocomplete="off" >
<input type="hidden" name="MAX_FILE_SIZE" value="<?= APP_FILE_SIZE; ?>" >
<input type="hidden" name="media_id" value="<?=(int)$media_id; ?>" >

<div class="box-body">

<?php 
if (isset($mediaData['media_filename'])) :

  $webp_src = invoke_webp_image($mediaData['media_filename'], false);
  $image_src = invoke_image_uploaded($mediaData['media_filename'], false);
  $webp_src_thumb = invoke_webp_image($mediaData['media_filename']);
  $image_src_thumb = invoke_image_uploaded($mediaData['media_filename']);
  
     if(!$webp_src_thumb || !$image_src_thumb) :
      
       $webp_src_thumb = app_url().'/public/files/pictures/thumbs/nophoto.jpg';
       $image_src_thumb = app_url().'/public/files/pictures/thumbs/nophoto.jpg';

     endif;

  if($image_src || $webp_src) :

?>

<div class="form-group">
<a href="<?=$webp_src;?>" title="<?=(!isset($mediaData['media_caption']) ?: safe_html($mediaData['media_caption'])); ?>">
<picture class="thumbnail">
<source srcset="<?=$webp_src_thumb; ?>" type="image/webp">
<img src="<?=$image_src_thumb;?>" class="img-responsive pad" width="320" alt="<?=(!isset($mediaData['media_caption']) ?: safe_html($mediaData['media_caption'])); ?>">
</picture>
</a>
<label for="ChangePicture">Change picture</label>
<input type="file"  name="media" id="mediaUploaded" accept="image/*" onchange="loadFile(event)" maxlength="512" >
<img id="output" class="img-responsive pad" >
<p class="help-block">Maximum upload file size: <?= format_size_unit(697856); ?>.</p>
</div>
  <?php else: ?>
<div class="form-group">
<a href="#" class="thumbnail"><?=invoke_fileicon($mediaData['media_type']);?></a>
<label for="ChangeFile">Change file</label>
<input type="file"  name="media" id="mediaUploaded" accept="image/*"  maxlength="512" >
<p class="help-block">Maximum upload file size: <?= format_size_unit(697856); ?>.</p>
</div>
  <?php endif; ?>

<?php else: ?>

<div class="form-group">
<div id="image-preview">
  <label for="image-upload" id="image-label">Choose picture</label>
  <input type="file" name="media" id="image-upload" accept="image/*" maxlength="512" required>
</div>
<p class="help-block">Maximum upload file size: <?= format_size_unit(697856); ?>.</p>
</div>

<?php endif; ?>

<div class="form-group">
<label>Caption </label>
<input type="text" class="form-control" name="media_caption" placeholder="type media caption" value="
<?=(isset($mediaData['media_caption'])) ? safe_html($mediaData['media_caption']) : ""; ?>
<?=(isset($formData['media_caption'])) ? safe_html($formData['media_caption']) : ""; ?>" maxlength="200" >
</div>

<div class="form-group">
<label>Display on</label>
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
<input type="radio" name="media_status" id="optionsRadios1" value="1" 
<?=(isset($mediaData['media_status']) && $mediaData['media_status'] === 1) ? 'checked="checked"' : "";  ?>
<?=(isset($formData['media_status']) && $formData['media_status'] === 1) ? 'checked="checked"' : "" ?>>
   Yes
 </label>
</div>

<div class="radio">
<label>
<input type="radio" name="media_status" id="optionsRadios2" value="0" 
<?=(isset($mediaData['media_status']) && $mediaData['media_status'] === 0) ? 'checked="checked"' : ""; ?>
<?=(isset($formData['media_status']) && $formData['media_status'] === 0) ? 'checked="checked"' : ""; ?>>
   No
 </label>
</div>

</div>

<?php endif; ?>

</div>
<!-- /.box-body -->
<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<input type="submit" name="mediaFormSubmit" class="btn btn-primary" value="<?=(isset($mediaData['ID']) && $mediaData['ID'] != '') ? "Update" : "Upload"; ?>">

</div>
</form>
            
</div>
<!-- /.box -->
</div>
<!--/.col-md-12 -->
<?php 
if((isset($mediaData['ID'])) && (!empty($mediaData['ID']))) :
?>
<div class="col-md-6">
<div class="box box-solid">
            <div class="box-header with-border">
             <?=(isset($mediaData['media_type'])) ? invoke_fileicon($mediaData['media_type']) : ""; ?>

              <h3 class="box-title">Media properties</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
              <dl class="dl-horizontal">
              
              <?php 
                 if (isset($mediaProperties['meta_value'])) :
                   $media_properties = media_properties($mediaProperties['meta_value']);
              ?>

                <dt>File name</dt>
                <dd><?= safe_html($media_properties['Origin']); ?></dd>
                <dt>MIME type</dt>
                <dd><?= safe_html($media_properties['File type']); ?></dd>
                <dt>File size</dt>
                <dd><?= safe_html($media_properties['File size']); ?></dd>
                <dt>Uploaded by</dt>
                <dd><?=(isset($mediaData['media_user'])) ? safe_html($mediaData['media_user']) : ""; ?></dd>
                <dt>Uploaded on</dt>
                <dd><?= safe_html($media_properties['Uploaded on']); ?></dd>
                <dt>Dimension</dt>
                <dd><?=(isset($mediaData['media_type']) && $mediaData['media_type'] != "image/jpeg" && $mediaData['media_type'] != "image/png" 
                      && $mediaData['media_type'] != "image/webp" 
                      && $mediaData['media_type'] != "image/gif") ? "Not specified" : safe_html($media_properties['Dimension']); ?> 
                </dd>

              <?php
                 endif;
              ?>
              
              </dl>
            </div>
            <!-- /.box-body -->
          </div>
          <!-- /.box -->
</div>
<!--/.col-md-6 -->
<?php endif; ?>

</div>
<!-- /.row --> 
</section>
<!--/.content -->
</div>
<!-- /.content-wrapper -->
<script type="text/javascript">
  var loadFile = function(event) {
	  var output = document.getElementById('output');
	      output.src = URL.createObjectURL(event.target.files[0]);
	  };
</script>