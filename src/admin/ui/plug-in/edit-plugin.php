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
        <li><a href="index.php?load=plugins">Plugins</a></li>
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

<form method="post" action="index.php?load=plugins&action=<?=(isset($formAction)) ? $formAction : null; ?>&Id=<?=(isset($pluginData['ID'])) ? $pluginData['ID'] : 0; ?>" role="form">
<input type="hidden" name="plugin_id" value="<?=(isset($pluginData['ID'])) ? $pluginData['ID'] : 0; ?>" />

<div class="box-body">
<div class="form-group">
<label for="plugin_name">Plugin (required)</label>
<input type="text" class="form-control" id="plugin_name" name="plugin_name" placeholder="Enter plugin name here" value="
<?=(isset($pluginData['plugin_name'])) ? htmlspecialchars($pluginData['plugin_name']) : ""; ?>
<?=(isset($formData['plugin_name'])) ? htmlspecialchars($formData['plugin_name'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>" maxlength="150" required>
</div>

<div class="form-group">
<label for="plugin_link">Link </label>
<input type="text" class="form-control" id="plugin_link" name="plugin_link" placeholder="?load=plugin_name" value="
<?=(isset($pluginData['plugin_link'])) ? htmlspecialchars($pluginData['plugin_link']) : ""; ?>
<?=(isset($formData['plugin_link'])) ? htmlspecialchars($formData['plugin_link'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>" maxlength="255" >
</div>

<div class="form-group">
<label for="description">Description (required)</label>
<textarea class="form-control" id="description" rows="3" placeholder="Enter ..." name="description"  maxlength="500" >
<?=(isset($pluginData['plugin_desc'])) ? $pluginData['plugin_desc'] : ""; ?>
<?=(isset($formData['description'])) ? htmlspecialchars($formData['description'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>
</textarea>
</div>

<div class="form-group">
<label for="plugin_level">Level</label>
<?=(isset($pluginLevel)) ? $pluginLevel : ""; ?>
</div>
<!-- /.plugin level -->

<?php if(!empty($pluginData['plugin_sort'])) : ?>
<div class="form-group">
<label for="plugin_sort">sort</label>
<input type="text" class="form-control" id="plugin_sort" name="plugin_sort" value="<?=(isset($pluginData['plugin_sort']) ? abs((int)$pluginData['plugin_sort']) : 0);  ?>" >
</div>
<?php endif;  ?>
<!-- /.plugin sort -->

</div>
<!-- /.box-body -->
<div class="box-footer">
<input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
<input type="submit" name="pluginFormSubmit" class="btn btn-primary" value="<?=(isset($pluginData['ID']) && $pluginData['ID'] != '') ? "Update" : "Add New Plugin"; ?>">
<?php 
 if(!empty($pluginData['ID'])) :
?>
<a href="javascript:deletePlugin('<?=(isset($pluginData['ID']) ? $pluginData['ID'] : 0); ?>', '<?=(isset($pluginData['plugin_name']) ? $pluginData['plugin_name'] : ""); ?>')"
	title="Uninstall Plugin" class="btn btn-danger pull-right"> <i class="fa fa-exclamation-circle fa-fw"></i> Uninstall
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
  function deletePlugin(id, plugin)
  {
	  if (confirm("Are you sure want to delete Plugin '" + plugin + "'"))
	  {
	  	window.location.href = 'index.php?load=plugins&action=deletePlugin&Id=' + id;
	  }
  }
</script>