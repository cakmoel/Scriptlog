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
        <li><a href="index.php?load=topics">Topics</a></li>
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
$topic_id = (isset($topicData)) ? abs((int)$topicData['ID']) : 0;
?>
<form method="post" action="<?=generate_request('index.php', 'post', ['topics', $action, $topic_id])['link']?>" role="form">
<input type="hidden" name="topic_id" value="<?=$topic_id; ?>">

<div class="box-body">
<div class="form-group">
<label>Title (required)</label>
<input type="text" class="form-control" name="topic_title" placeholder="Enter title here" value="
<?=(isset($topicData['topic_title'])) ? safe_html($topicData['topic_title']) : ""; ?>
<?=(isset($formData['topic_title'])) ? safe_html($formData['topic_title']) : ""; ?>" required>
</div>

<?php if (isset($topicData['topic_status'])) : ?>
<div class="form-group">
<label>Actived</label>
<div class="radio">
<label>
<input type="radio" name="topic_status" id="optionsRadios1" value="Y" 
<?=(isset($topicData['topic_status']) && $topicData['topic_status'] === 'Y') ? 'checked="checked"' : "";  ?>
<?=(isset($formData['topic_status']) && $formData['topic_status'] === 'Y') ? 'checked="checked"' : "" ?>>
   Yes
 </label>
</div>

<div class="radio">
<label>
<input type="radio" name="topic_status" id="optionsRadios1" value="N" 
<?=(isset($topicData['topic_status']) && $topicData['topic_status'] === 'N') ? 'checked="checked"' : ""; ?>
<?=(isset($formData['topic_status']) && $formData['topic_status'] == 'N') ? 'checked="checked"' : ""; ?>>
   No
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
<input type="submit" name="topicFormSubmit" class="btn btn-primary" value="<?=(($topic_id) && ($topic_id != '')) ? "Update" : "Add New Topic" ?>">
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