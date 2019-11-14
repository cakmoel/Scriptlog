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
echo '<p><i class="icon fa fa-ban"></i>' . $e . '</p>';
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

<form method="post" action="index.php?load=pages&action=<?=(isset($formAction)) ? $formAction : null; ?>&pageId=<?=(isset($formData['ID'])) ? $formData['ID'] : 0; ?>" 
role="form" enctype="multipart/form-data" autocomplete="off">
<input type="hidden" name="page_id" value="<?=(isset($pageData['ID'])) ? $pageData['ID'] : 0; ?>" />
<input type="hidden" name="MAX_FILE_SIZE" value="697856" />

<div class="box-body">
<div class="form-group">
<label>Title (required)</label>
<input type="text" class="form-control" name="post_title" placeholder="Enter title here" value="
<?=(isset($pageData)) ? htmlspecialchars($pageData['post_title']) : ""; ?>
<?=(isset($formData['post_title'])) ? htmlspecialchars($formData['post_title'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>" required>
</div>

<?php 
if (isset($pageData['post_image'])) :
?>
<div class="form-group">
<?php 
$image = '../public/files/pictures/'.$pageData['post_image'];
$imageThumb = '../public/files/pictures/thumbs/thumb_'.$pageData['post_image'];

if (!is_readable($imageThumb)) :
    $imageThumb = '../public/files/pictures/thumbs/nophoto.jpg';
endif;

if (is_readable($image)) :
?>
<br><a href="<?php echo $image; ?>"><img src="<?php  echo $imageThumb; ?>" class="img-responsive pad"></a><br> 
<label>change picture :</label> 
<input type="file" name="image" id="file" accept="image/*" onchange="loadFile(event)" maxlength="512" />
<img id="output" class="img-responsive pad" />
<p class="help-block">Maximum file size: <?= format_size_unit(697856); ?></p>
<?php 
else :
?>
<br><img src="<?php echo $imageThumb; ?>" class="img-responsive pad"><br> 
<label>change picture :</label> 
<input type="file" name="image" id="file" accept="image/*" onchange="loadFile(event)"  maxlength="512" />
<img id="output" class="img-responsive pad" />
<p class="help-block">Maximum file size: <?= format_size_unit(697856); ?></p>
<?php 
endif;
?>
</div>
<?php else : ?>
<div class="form-group">
<label>Upload Picture :</label> 
<input type="file" name="image" id="file" accept="image/*" onchange="loadFile(event)"  maxlength="512" />
<img id="output" class="img-responsive pad" />
<p class="help-block">Maximum file size: <?= format_size_unit(697856); ?></p>
</div>
<?php 
endif;
?>

<div class="form-group">
<label>Meta Description</label>
<textarea class="form-control" name="post_summary" rows="3" maxlength="500" >
<?=(isset($pageData['post_summary'])) ? $pageData['post_summary'] : ""; ?>
<?=(isset($formData['post_summary'])) ? htmlspecialchars($formData['post_summary'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>
</textarea>
</div>

<div class="form-group">
<label>Meta Keywords</label>
<textarea class="form-control" name="post_keyword" rows="3" maxlength="200" >
<?=(isset($pageData['post_keyword'])) ? $pageData['post_keyword'] : ""; ?>
<?=(isset($formData['post_keyword'])) ? htmlspecialchars($formData['post_keyword'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>
</textarea>
</div>

<div class="form-group">
<label>Content (required)</label>
<textarea class="textarea" placeholder="Place some text here"
style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;" 
name="post_content"  maxlength="10000" required>
<?=(isset($pageData['post_content'])) ? $pageData['post_content'] : ""; ?>
<?=(isset($formData['post_content'])) ? htmlspecialchars($formData['post_content'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>
</textarea>
</div>

<div class="form-group">
<?=(isset($postStatus)) ? $postStatus : ""; ?>
</div>
<!-- /.post status -->

<div class="form-group">
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