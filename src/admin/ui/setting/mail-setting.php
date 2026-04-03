<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>
 <!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1><?=(isset($pageTitle) ? $pageTitle : "") ; ?>
        <small>Control Panel</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard" aria-hidden="true"></i> Home </a></li>
        <li class="active"><a href="index.php?load=option-mail"><?=(isset($pageTitle)) ? $pageTitle : ""; ?> </a></li>
      </ol>
    </section>

<!-- Main content -->
<section class="content">
<div class="row">
<div class="col-md-8">
<div class="box box-primary">
<div class="box-header with-border">
  <h3 class="box-title">SMTP Configuration</h3>
</div>
      <!-- /.box-header -->
<?php
if (isset($errors)) :
    ?>
<div class="alert alert-danger alert-dismissible">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
<h4><i class="icon fa fa-warning" aria-hidden="true"></i> Invalid Form Data!</h4>
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
  <h4><i class="icon fa fa-check" aria-hidden="true"></i> Success!</h4>
    
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
  <form method="post" action="<?= generate_request('index.php', 'get', ['option-mail', $action, 0])['link']; ?>" >
    
    <div class="form-group">
      <label for="smtp_host">SMTP Host</label>
      <input type="text" name="smtp_host" id="smtp_host" class="form-control" value="<?= safe_html($smtp['smtp_host'] ?? ''); ?>" placeholder="smtp.example.com">
      <p class="help-block">Your outgoing mail server hostname.</p>
    </div>

    <div class="form-group">
      <label for="smtp_port">SMTP Port</label>
      <input type="number" name="smtp_port" id="smtp_port" class="form-control" value="<?= safe_html($smtp['smtp_port'] ?? '587'); ?>" placeholder="587">
      <p class="help-block">Common ports: 587 (TLS), 465 (SSL), or 25.</p>
    </div>

    <div class="form-group">
      <label for="smtp_encryption">Encryption</label>
      <select name="smtp_encryption" id="smtp_encryption" class="form-control">
        <option value="tls" <?= ($smtp['smtp_encryption'] == 'tls') ? 'selected' : ''; ?>>TLS</option>
        <option value="ssl" <?= ($smtp['smtp_encryption'] == 'ssl') ? 'selected' : ''; ?>>SSL</option>
        <option value="none" <?= ($smtp['smtp_encryption'] == 'none') ? 'selected' : ''; ?>>None</option>
      </select>
    </div>

    <div class="form-group">
      <label for="smtp_username">SMTP Username</label>
      <input type="text" name="smtp_username" id="smtp_username" class="form-control" value="<?= safe_html($smtp['smtp_username'] ?? ''); ?>" placeholder="user@example.com">
    </div>

    <div class="form-group">
      <label for="smtp_password">SMTP Password</label>
      <input type="password" name="smtp_password" id="smtp_password" class="form-control" value="<?= safe_html($smtp['smtp_password'] ?? ''); ?>" placeholder="********">
      <p class="help-block">Your email account password or app-specific password.</p>
    </div>

    <hr>

    <div class="form-group">
      <label for="smtp_from_email">From Email Address</label>
      <input type="email" name="smtp_from_email" id="smtp_from_email" class="form-control" value="<?= safe_html($smtp['smtp_from_email'] ?? ''); ?>" placeholder="noreply@yourdomain.com">
    </div>

    <div class="form-group">
      <label for="smtp_from_name">From Name</label>
      <input type="text" name="smtp_from_name" id="smtp_from_name" class="form-control" value="<?= safe_html($smtp['smtp_from_name'] ?? ''); ?>" placeholder="Blogware">
    </div>

    <div class="box-footer">
      <input type="hidden" name="csrfToken" value="<?=(isset($csrfToken)) ? $csrfToken : ""; ?>">  
      <input type="submit" name="mailConfigSubmit" class="btn btn-primary" value="Save Settings">
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
