<?php if (!defined('SCRIPTLOG')) { exit(); } ?>

<div class="content-wrapper">

  <section class="content-header">
    <h1><?= (isset($pageTitle)) ? $pageTitle : ""; ?>
      <small>Export Content</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard" aria-hidden="true"></i> Home </a></li>
      <li class="active">Export</li>
    </ol>
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        
        <?php 
        if (!empty($errors)) :
        ?>
          <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h2><i class="icon fa fa-ban" aria-hidden="true"></i> Error!</h2>
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
        if (!empty($success)) :
        ?>
          <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h2><i class="icon fa fa-check" aria-hidden="true"></i> Success!</h2>
            <?php
            foreach ($success as $s) :
              echo '<p>' . nl2br($s) . '</p>';
            endforeach;
            ?>
          </div>
        <?php
        endif;
        ?>

        <div class="box box-primary">
          <div class="box-header with-border">
            <h2 class="box-title">Export Content</h2>
          </div>
          
          <div class="box-body">
            <div class="callout callout-info">
              <h4><i class="icon fa fa-info" aria-hidden="true"></i> Export Options</h4>
              <p>Export your Blogware content to various formats for migration or backup.</p>
            </div>
            
            <form method="post" action="index.php?load=export" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= (isset($csrfToken)) ? $csrfToken : ""; ?>">
              
              <div class="form-group">
                <label for="destination">Select Destination Platform</label>
                <select name="destination" id="destination" class="form-control" required>
                  <option value="">-- Select Platform --</option>
                  <option value="scriptlog">Scriptlog / Blogware (JSON)</option>
                  <option value="wordpress">WordPress (WXR)</option>
                  <option value="ghost">Ghost (JSON)</option>
                  <option value="blogspot">Blogspot / Blogger (XML)</option>
                </select>
                <p class="help-block">Choose the platform you want to export content to.</p>
              </div>
              
              <div class="form-group">
                <label for="author_id">Filter Content by Author (Optional)</label>
                <select name="author_id" id="author_id" class="form-control">
                  <option value="">All Authors</option>
                  <?php if (!empty($users)) : ?>
                    <?php foreach ($users as $user) : ?>
                      <option value="<?= $user['ID']; ?>"><?= safe_html($user['user_fullname'] ?: $user['user_login']); ?> (<?= safe_html($user['user_level']); ?>)</option>
                    <?php endforeach; ?>
                  <?php else : ?>
                    <option value="1">Admin</option>
                  <?php endif; ?>
                </select>
                <p class="help-block">Select a specific author to export only their content, or leave blank to export all content.</p>
              </div>
              
              <div class="box-footer">
                <button type="submit" name="exportSubmit" class="btn btn-primary btn-flat" id="exportBtn" title="Download export file">
                  <i class="fa fa-download" aria-hidden="true"></i> Export
                </button>
              </div>
              
            </form>
            
          </div>
          
        </div>
        
        <div class="box box-default">
          <div class="box-header with-border">
            <h3 class="box-title">Information</h3>
          </div>
          <div class="box-body">
            
            <h4>What gets exported:</h4>
            <ul>
              <li>Posts (including featured images)</li>
              <li>Pages</li>
              <li>Categories/Tags</li>
              <li>Comments (approved only)</li>
              <li>Users (optional, based on author filter)</li>
              <li>Navigation menus</li>
              <li>Site settings</li>
            </ul>
            
            <h4>Supported Formats:</h4>
            <ul>
              <li><strong>Scriptlog / Blogware JSON</strong> - Native format for Scriptlog/Blogware import (recommended for Scriptlog to Scriptlog transfers)</li>
              <li><strong>WordPress WXR XML</strong> - Compatible with WordPress import</li>
              <li><strong>Ghost JSON</strong> - Compatible with Ghost import</li>
              <li><strong>Blogspot/Blogger XML</strong> - Compatible with Blogger import</li>
            </ul>
            
            <div class="alert alert-info">
              <p><i class="icon fa fa-info" aria-hidden="true"></i> <strong>Tip:</strong> When exporting content from one Scriptlog installation to another, always use the <strong>Scriptlog / Blogware JSON</strong> format. It preserves all your data including menus, categories, tags, and settings.</p>
            </div>
            
            <p><strong>Note:</strong> Media files (images, documents, etc.) are NOT included in the export. You'll need to manually transfer your media files or use the media library export/import feature if available.</p>
          </div>
        </div>
        
      </div>
    </div>
  </section>
  
  <script>
  (function() {
    document.getElementById('exportBtn').addEventListener('click', function(e) {
      var selectedDest = document.getElementById('destination').value;
      if (!selectedDest) {
        e.preventDefault();
        alert('Please select a destination platform first.');
        return false;
      }
    });
  })();
  </script>
</div>