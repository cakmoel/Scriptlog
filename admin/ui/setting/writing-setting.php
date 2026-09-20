<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1><?= (isset($pageTitle) ? $pageTitle : admin_translate('nav.writing')); ?>
        <small>Control Panel</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard" aria-hidden="true"></i> Home </a></li>
        <li class="active"><a href="index.php?load=option-writing"><?= admin_translate('nav.writing'); ?></a></li>
      </ol>
    </section>

<!-- Main content -->
<section class="content">
<div class="row">
<div class="col-md-8">
<div class="box box-primary">
<div class="box-header with-border"></div>
      <!-- /.box-header -->
<?php
if (isset($errors) && !empty($errors)) :
    ?>
<div class="alert alert-danger alert-dismissible">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
<h2><i class="icon fa fa-warning" aria-hidden="true"></i> Invalid Form Data!</h2>
    <?php
    foreach ($errors as $e) :
        echo '<p>' . safe_html($e) . '</p>';
    endforeach;
    ?>
</div>

    <?php
endif;

if (isset($_GET['status']) && $_GET['status'] === 'writingConfigUpdated') :
    ?>

<div class="alert alert-success alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <h2><i class="icon fa fa-check" aria-hidden="true"></i> Success!</h2>
  <p>Writing settings have been updated successfully.</p>
</div>

    <?php
endif;

$action = (isset($formAction)) ? $formAction : null;
$scheduledEnabled = ($writing['writing_scheduled_post_enabled'] ?? '1') === '1';
?>

<div class="box-body">
  <form method="post" action="<?= generate_request('index.php', 'get', ['option-writing', $action, 0])['link']; ?>">
    <input type="hidden" name="csrfToken" value="<?= $csrfToken ?>">

    <!-- Scheduled Posting Toggle -->
    <div class="form-group">
      <label>
        <input type="checkbox" name="writing_scheduled_post_enabled" value="1" <?= $scheduledEnabled ? 'checked' : '' ?> aria-describedby="scheduledPostingHelp">
        Enable Scheduled Posting
      </label>
      <p id="scheduledPostingHelp" class="help-block">When enabled, posts with a future publication date and status "Publish" are stored as "Scheduled" and published automatically at that time.</p>
    </div>

    <!-- Info Box -->
    <div class="callout callout-info">
      <h4>How Scheduled Posting Works</h4>
      <p>On the post editor, open the calendar next to the <strong>Publication Date</strong> field and pick a future date and time, then keep the status set to <strong>Publish</strong>.</p>
      <ul>
        <li>The post is saved with the status <strong>Scheduled</strong>.</li>
        <li>When the scheduled time arrives, the post is published automatically on the next site visit.</li>
        <li>Only administrators can enable or disable this feature.</li>
      </ul>
    </div>
  </div>

  <div class="box-footer">
    <button type="submit" name="writingConfigSubmit" class="btn btn-primary">
      <i class="fa fa-save"></i> Update Writing Settings
    </button>
  </div>
</form>
</div>
  <!-- /.box-primary -->
</div>
    <!-- /.col-md-8 -->
</div>
</section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
