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
        <li><a href="index.php?load=settings">Settings</a></li>
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

<form method="post" action="index.php?load=settings&action=<?=(isset($formAction)) ? $formAction : null; ?>&settingId=<?=(isset($settingData['ID'])) ? $settingData['ID'] : 0; ?>" role="form">
<input type="hidden" name="setting_id" value="<?=(isset($settingData['ID'])) ? $settingData['ID'] : 0; ?>" />

<div class="box-body">

<div class="form-group">
<label>Name (required)</label>
<input type="text" class="form-control" name="setting_name" placeholder="Enter name here" value="
<?=(isset($settingData['setting_name'])) ? htmlspecialchars($settingData['setting_name']) : ""; ?>
<?=(isset($formData['setting_name'])) ? htmlspecialchars($formData['setting_name'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>" required 
<?=(isset($settingData['setting_name']) && ($settingData['setting_name'] == 'app_key')) ? "disabled" : ""; ?>>
</div>

<div class="form-group">
<label>Value (required)</label>
<input type="text" class="form-control" name="setting_value" placeholder="Enter value here" value="
<?=(isset($settingData['setting_value'])) ? htmlspecialchars($settingData['setting_value']) : ""; ?>
<?=(isset($formData['setting_value'])) ? htmlspecialchars($formData['setting_value'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>" required
<?=(isset($settingData['setting_name']) && ($settingData['setting_name'] == 'app_key')) ? "disabled" : ""; ?>>
</div>

<div class="form-group">
<label>Description</label>
<textarea class="textarea" placeholder="Place some text here"
style="width: 100%; height: 200px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;" 
name="setting_desc"  maxlength="500" >
<?=(isset($settingData['setting_desc'])) ? $settingData['setting_desc'] : ""; ?>
<?=(isset($formData['setting_desc'])) ? htmlspecialchars($formData['setting_desc'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>
</textarea>
</div>

</div>
<!-- /.box-body -->

<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<input type="submit" name="configFormSubmit" class="btn btn-primary" value="<?=(isset($settingData['ID']) && $settingData['ID'] != '') ? "Update" : "Add New Setting" ?>">
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