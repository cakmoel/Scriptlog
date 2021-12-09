<?php if (!defined('SCRIPTLOG')) exit(); ?>
 <!-- Content Wrapper. Contains page content -->
 <div class="content-wrapper">
 <!-- Content Header (Page header) -->
 <section class="content-header">
   <h1>
     <?=(isset($pageTitle) ? $pageTitle : ""); ?> 
     <small>Control Panel</small>
   </h1>
   <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=option-reading">Reading Settings</a></li>
        <li class="active"><?=(isset($pageTitle)) ? $pageTitle : ""; ?></li>
   </ol>
 </section>

<!-- Main Content -->
<section class="content">
<div class="row">
<div class="col-md-8">
<div class="box box-primary">
  <!-- box-header -->
  <div class="box-header with-border"></div>

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

if (isset($status)) :

?>

<div class="alert alert-success alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <h2><i class="icon fa fa-check"></i> Success!</h2>
    <?php 
        foreach ($status as $s) :
          echo $s;
        endforeach;
    ?>
</div>

<?php
 
endif;

$action = (isset($formAction)) ? $formAction : null;

?>

<div class="box-body">
<form method="post" action="<?= generate_request('index.php', 'get', ['option-reading', $action, 0])['link']; ?>" role="form">

<table class="table table-bordered table-striped">
<tbody>
<?php 

if (is_array($settings)) :

   $i = 0; 

   foreach ($settings as $s) :
     
    switch ($s['setting_name']) {

      case 'post_per_page':
         
        $setting_name = "Post to display on frontpage";
         
        break;

      case 'post_per_rss';

        $setting_name = "Post to display in Feeds";

        break;
       
     }

?>

  <tr>
    <th><label for="<?=(isset($s['setting_name'])) ? safe_html($s['setting_name']) : ""; ?>"> <?=(isset($setting_name) ? safe_html($setting_name) : "") ; ?></label></th>
   <td>
      <input type="hidden" name="setting_id[]" value="<?= safe_html((int)$s['ID']);?>">
      <input type="text" name="setting_value[]" class="form-control" id="<?=(isset($s['setting_name'])) ? safe_html($s['setting_name']) : ""; ?>" value="<?= safe_html($s['setting_value']); ?>" maxlength="255" >
    </td>
  </tr>
             
<?php
    $i++;
    endforeach;
  endif;
?>

</tbody>
</table>
  <div class="box-footer">
    <input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
    <input type="submit" name="configFormSubmit" class="btn btn-primary" value="<?=(empty($s)) ?: "Update" ?>">
  </div>
</form>
</div>

</div>
</div>
</div>
</section>
</div>