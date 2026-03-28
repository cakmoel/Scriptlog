<?php if (!defined('SCRIPTLOG')) { exit(); } ?>

<?php 
$action = isset($formAction) ? $formAction : null;
$media_id = isset($mediaData['ID']) ? $mediaData['ID'] : 0;
?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
  <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small>Control Panel</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard" aria-hidden="true"></i> Home </a></li>
        <li><a href="index.php?load=medialib">Media </a></li>
        <li class="active"><?=(isset($pageTitle)) ? $pageTitle : ""; ?></li>
      </ol>
  </section>

 <!-- Main content -->
<section class="content">
<div class="row">
<div class="col-md-6">
<div class="box box-primary">
<div class="box-header with-border">
  <h3 class="box-title">Media Details</h3>
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

<form method="post" action="<?=generate_request('index.php', 'post', ['medialib', $action, $media_id])['link'];?>" role="form" enctype="multipart/form-data" autocomplete="off" >
<input type="hidden" name="MAX_FILE_SIZE" value="<?= APP_FILE_SIZE; ?>" >
<input type="hidden" name="media_id" value="<?=(int)$media_id; ?>" >

<div class="box-body">

<?php 
if (isset($mediaData['media_filename']) && isset($mediaData['media_type'])) :

  $webp_src = invoke_webp_image($mediaData['media_filename'], false);
  $image_src = invoke_image_uploaded($mediaData['media_filename'], false);
  $webp_src_thumb = invoke_webp_image($mediaData['media_filename'], true);
  $image_src_thumb = invoke_image_uploaded($mediaData['media_filename'], true);
  $video_src = invoke_video_uploaded($mediaData['media_filename']);
  $audio_src = invoke_audio_uploaded($mediaData['media_filename']);
  
    if(! ($image_src_thumb || $webp_src_thumb)) :
      
      $webp_src_thumb = app_url().'/public/files/pictures/nophoto.jpg';
      $image_src_thumb = app_url().'/public/files/pictures/nophoto.jpg';

    endif;

if ($image_src || $webp_src) :

?>

<div class="form-group">
<a href="<?=$webp_src;?>" title="<?=(!isset($mediaData['media_caption']) ?: safe_html($mediaData['media_caption'])); ?>">
<picture class="img-responsive pad">
<source srcset="<?= $webp_src_thumb; ?>" type="image/webp">
<img src="<?= $image_src_thumb;?>" class="img-responsive pad" alt="<?=(!isset($mediaData['media_caption']) ?: safe_html($mediaData['media_caption'])); ?>">
</picture>
</a>
</div>

<div class="form-group">
  <label for="image-upload">Change picture</label>
  <input type="file" name="media" id="image-upload" accept="image/*" maxlength="512" >
  <p class="help-block">Maximum upload file size: <?= format_size_unit(APP_FILE_SIZE); ?>.</p>
</div>  

<?php else: ?>

<div class="form-group">

<?php 
if ($mediaData['media_type'] == "video/webm" || $mediaData['media_type'] == "video/mp4" || $mediaData['media_type'] == "video/ogg") :
?>

<video class="img-responsive pad" controls width="600" height="320" preload="metadata">
<source src="<?=$video_src; ?>" type="<?=$mediaData['media_type']; ?>">
Sorry, your browser doesn't support embedded <code>videos</code>
<track label="English" kind="captions" srclang="en">
</video>

<?php  elseif($mediaData['media_type'] == "audio/mpeg" || $mediaData['media_type'] == "audio/wav" || $mediaData['media_type'] == "audio/ogg") : ?>

<audio class="img-responsive pad" controls>
<source src="<?=$audio_src; ?>" type="<?=$mediaData['media_type']; ?>">
Your browser does not support the <code>audio</code> element. 
</audio>

<?php else :?>

<a href="#" class="img-responsive pad" aria-hidden="true"><?=invoke_fileicon($mediaData['media_type']);?></a>

<?php endif; ?>

<label for="mediaUploaded">Change file</label>
<input type="file"  name="media" id="mediaUploaded" maxlength="512" >
<p class="help-block">Maximum upload file size: <?= format_size_unit(APP_FILE_SIZE); ?>.</p>
</div>

<?php endif; ?>

<?php else: ?>

<div class="form-group">
<label for="mediaUploaded">Select Media File <span class="text-red" title="required">*</span></label>
<input type="file" name="media" id="mediaUploaded" maxlength="512" required aria-required="true">
<p class="help-block">Supported formats: Images, Audio, and Video. Max size: <?= format_size_unit(APP_FILE_SIZE); ?>.</p>
</div>

<?php endif; ?>

<div class="form-group">
<label for="caption">Caption </label>
<input type="text" class="form-control" id="caption" name="media_caption" placeholder="Describe this media..." value="<?=(isset($mediaData['media_caption'])) ? safe_html($mediaData['media_caption']) : ""; ?><?=(isset($formData['media_caption'])) ? purify_dirty_html($formData['media_caption']) : ""; ?>" maxlength="200" aria-describedby="captionHelp">
<p id="captionHelp" class="help-block">A brief description or title for the media.</p>
</div>

<div class="form-group">
<label for="media_target">Display on</label><br>
<?=(isset($mediaTarget)) ? $mediaTarget : ""; ?>
</div>
<!-- media target -->

<div class="form-group">
<label for="media_access">Access Visibility</label><br>
<?=(isset($mediaAccess)) ? $mediaAccess : ""; ?>
</div>
<!-- media access -->

<?php if(isset($mediaData['media_status'])) : ?>

<div class="form-group">
<label>Active Status</label>
<div class="radio">
<label for="optionsRadios1">
<input type="radio" name="media_status" id="optionsRadios1" value="1" 
<?=(isset($mediaData['media_status']) && $mediaData['media_status'] === 1) ? 'checked="checked"' : "";  ?>
<?=(isset($formData['media_status']) && $formData['media_status'] === 1) ? 'checked="checked"' : "" ?>>
   Yes, keep this media active
 </label>
</div>

<div class="radio">
<label for="optionsRadios2">
<input type="radio" name="media_status" id="optionsRadios2" value="0" 
<?=(isset($mediaData['media_status']) && $mediaData['media_status'] === 0) ? 'checked="checked"' : ""; ?>
<?=(isset($formData['media_status']) && $formData['media_status'] === 0) ? 'checked="checked"' : ""; ?>>
   No, deactivate this media
 </label>
</div>

</div>

<?php endif; ?>

</div>
<!-- /.box-body -->
<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<a href="index.php?load=medialib" class="btn btn-default" role="button" aria-label="Cancel and return to media library">
  <i class="fa fa-times" aria-hidden="true"></i> Cancel
</a>
<button type="submit" name="mediaFormSubmit" class="btn btn-primary pull-right" aria-label="<?=(isset($mediaData['ID']) && $mediaData['ID'] != '') ? "Update Media" : "Upload Media"; ?>">
  <i class="fa <?= (isset($mediaData['ID']) && $mediaData['ID'] != '') ? "fa-save" : "fa-upload"; ?>" aria-hidden="true"></i> 
  <?=(isset($mediaData['ID']) && $mediaData['ID'] != '') ? "Update" : "Upload"; ?>
</button>
</div>
</form>
            
</div>
<!-- /.box -->
</div>
<!--/.col-md-6 -->

<?php 
if((isset($mediaData['ID'])) && (!empty($mediaData['ID']))) :
?>
<div class="col-md-6">
<div class="box box-solid">
<div class="box-header with-border">
      <?=(isset($mediaData['media_type'])) ? invoke_fileicon($mediaData['media_type']) : ""; ?>
<h2 class="box-title">Media properties</h2>
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
                <dd><?= safe_html($media_properties['Uploaded at']); ?></dd>
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
<!-- /.box box-solid-->
</div>
<!--/.col-md-6 -->
<?php endif; ?>

</div>
<!-- /.row --> 
</section>
<!--/.content -->
</div>
<!-- /.content-wrapper -->

