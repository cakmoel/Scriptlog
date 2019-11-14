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
        <li><a href="index.php?load=menu">Menu</a></li>
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

<form method="post" action="index.php?load=menu&action=<?=(isset($formAction)) ? $formAction : null; ?>&menuId=<?=(isset($menuData['ID'])) ? $menuData['ID'] : 0; ?>" role="form">
<input type="hidden" name="menu_id" value="<?=(isset($menuData['ID'])) ? $menuData['ID'] : 0; ?>" />

<div class="box-body">
<div class="form-group">
<label>Menu (required)</label>
<input type="text" class="form-control" name="menu_label" placeholder="Enter menu label here" value="
<?=(isset($menuData['menu_label'])) ? htmlspecialchars($menuData['menu_label']) : ""; ?>
<?=(isset($formData['menu_label'])) ? htmlspecialchars($formData['menu_label'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>" required>
</div>

<div class="form-group">
<label>Link</label>
<input type="text" class="form-control" name="menu_link" placeholder="ex:about-us" value="
<?=(isset($menuData['menu_link'])) ? htmlspecialchars($menuData['menu_link']) : ""; ?>
<?=(isset($formData['menu_link'])) ? htmlspecialchars($formData['menu_link'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>" >
</div>

<?php
  if (!empty($menuData['menu_sort'])) :
?>
<div class="form-group">
<label>Order</label>
<input type="text" class="form-control" name="menu_sort" placeholder="" value="
<?=(isset($menuData['menu_sort'])) ? htmlspecialchars($menuData['menu_sort']) : ""; ?>
<?=(isset($formData['menu_sort'])) ? htmlspecialchars($formData['menu_sort'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>" required>
</div>
<?php 
  endif;
?>

<?php if (isset($menuData['menu_status'])) : ?>
<div class="form-group">
<label>Actived</label>
<div class="radio">
<label>
<input type="radio" name="menu_status" id="optionsRadios1" value="Y" 
<?=(isset($menuData['menu_status']) && $menuData['menu_status'] === 'Y') ? 'checked="checked"' : ""; ?>
<?=(isset($formData['menu_status']) && $formData['menu_status'] === 'Y') ? 'checked="checked"' : "" ?> >
   Yes
 </label>
</div>

<div class="radio">
<label>
<input type="radio" name="menu_status" id="optionsRadios1" value="N" 
<?=(isset($menuData['menu_status']) && $menuData['menu_status'] === 'N') ? 'checked="checked"' : ""; ?>
<?=(isset($formData['menu_status']) && $formData['menu_status'] == 'N') ? 'checked="checked"' : ""; ?> >
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
<input type="submit" name="menuFormSubmit" class="btn btn-primary" value="<?=(isset($menuData['ID']) && $menuData['ID'] != '') ? "Update" : "Add New Menu" ?>">
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