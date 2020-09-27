<?php if (!defined('SCRIPTLOG')) exit(); ?>
 <!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small></small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=option-permalink">Permalink setting</a></li>
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
  if (isset($status)) :
?>
<div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <h4><i class="icon fa fa-check"></i> Success!</h4>
    <?php 
        foreach ($status as $s) :
          echo $s;
        endforeach;
    ?>
</div>

<?php
 
endif;

$action = (isset($formAction)) ? $formAction : null;
$paramId = (isset($settingData['ID'])) ? abs((int)$settingData['ID']) : 0;
?>

<div class="box-body">
  
     <form method="post" action="<?= generate_request('index.php', 'get', ['option-permalink', $action, 0])['link']; ?>" role="form">
        <input type="hidden" name="setting_id" value="<?= $paramId; ?>">
        <input type="hidden" name="setting_name" value="<?=(!isset($settingData['setting_name']) ?: safe_html($settingData['setting_name'])); ?>">
        <div class="form-group">
          <label for="permalink">Enable SEO-Friendly URL</label>
          <select class="form-control select2" style="width: 100%;" name="permalinks" id="permalink">
            
            <?php 
              if(isset($settingData['setting_value']) && $settingData['setting_value'] == 'yes'):
            ?>
              <option value="yes" selected="selected">Yes</option>
              <option value="no" >No</option>
            <?php 
              else:
            ?>
            <option value="no" selected="selected">No</option>
            <option value="yes">Yes</option>
              <?php endif; ?>
          </select>
        </div>
        <div class="box-footer">
        <input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
        <input type="submit" name="configFormSubmit" class="btn btn-primary" value="Update">
        </div>
     </form>
   </div>
 </div>
   <!-- /.box-primary -->
</div>
    <!--- /.col-md-6 -->
</div>     
</section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
