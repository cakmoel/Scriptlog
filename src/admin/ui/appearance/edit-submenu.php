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

<form method="post" action="index.php?load=menu-child&action=<?=(isset($formAction)) ? $formAction : null; ?>&subMenuId=<?=(isset($subMenuData['ID'])) ? $subMenuData['ID'] : 0; ?>" role="form">
<input type="hidden" name="child_id" value="<?=(isset($subMenuData['ID'])) ? $subMenuData['ID'] : 0; ?>" />

<div class="box-body">
<div class="form-group">
<label>Sub Menu (required)</label>
<input type="text" class="form-control" name="child_label" placeholder="Enter sub menu label here" value="
<?=(isset($subMenuData['menu_child_label'])) ? htmlspecialchars($subMenuData['menu_child_label']) : ""; ?>
<?=(isset($formData['child_label'])) ? htmlspecialchars($formData['child_label'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>" required>
</div>

<div class="form-group">
<label>Link</label>
<input type="text" class="form-control" name="child_link" placeholder="ex:about-us" value="
<?=(isset($subMenuData['menu_child_link'])) ? htmlspecialchars($subMenuData['menu_child_link']) : ""; ?>
<?=(isset($formData['child_link'])) ? htmlspecialchars($formData['child_link'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>" >
</div>

<div class="form-group">
<label>Menu</label>
<?=(is_array($menus)) ? $menus : ""; ?>
</div>

<div class="form-group">
<label>Sub Menu</label>
<?=(is_array($submenus)) ? $submenus : ""; ?>
</div>

<?php
  if (!empty($subMenuData['menu_child_sort'])) :
?>
<div class="form-group">
<label>Order</label>
<input type="text" class="form-control" name="child_sort" placeholder="" value="
<?=(isset($subMenuData['menu_child_sort'])) ? htmlspecialchars($subMenuData['menu_child_sort']) : ""; ?>
<?=(isset($formData['child_sort'])) ? htmlspecialchars($formData['child_sort'], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") : ""; ?>" required>
</div>
<?php 
  endif;
?>

<?php if (isset($subMenuData['menu_child_status'])) : ?>
<div class="form-group">
<label>Actived</label>
<div class="radio">
<label>
<input type="radio" name="child_status" id="optionsRadios1" value="Y" 
<?=(isset($subMenuData['menu_child_status']) && $subMenuData['menu_child_status'] === 'Y') ? 'checked="checked"' : ""; ?>
<?=(isset($formData['child_status']) && $formData['child_status'] === 'Y') ? 'checked="checked"' : "" ?> >
   Yes
 </label>
</div>

<div class="radio">
<label>
<input type="radio" name="child_status" id="optionsRadios1" value="N" 
<?=(isset($subMenuData['menu_child_status']) && $subMenuData['menu_child_status'] === 'N') ? 'checked="checked"' : ""; ?>
<?=(isset($formData['child_status']) && $formData['child_status'] == 'N') ? 'checked="checked"' : ""; ?> >
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
<input type="submit" name="childFormSubmit" class="btn btn-primary" value="<?=(isset($subMenuData['ID']) && $subMenuData['ID'] != '') ? "Update" : "Add New Submenu" ?>">
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