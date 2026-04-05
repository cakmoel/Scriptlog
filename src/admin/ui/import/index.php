<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>

<div class="content-wrapper">

  <section class="content-header">
    <h1><?= (isset($pageTitle)) ? $pageTitle : ""; ?>
      <small>Import Content</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard" aria-hidden="true"></i> Home </a></li>
      <li class="active">Import</li>
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
            <h2 class="box-title">Import Content</h2>
          </div>
          
          <div class="box-body">
            <div class="callout callout-info">
              <h4><i class="icon fa fa-info" aria-hidden="true"></i> Supported Platforms</h4>
              <p>Import content from WordPress (WXR), Ghost (JSON), Blogspot/Blogger (XML), or Scriptlog/Blogware (JSON) export files.</p>
            </div>
            
            <form method="post" action="index.php?load=import" enctype="multipart/form-data">
              <input type="hidden" name="csrf_token" value="<?= (isset($csrfToken)) ? $csrfToken : ""; ?>">
              
              <div class="form-group">
                <label for="source">Select Source Platform</label>
                <select name="source" id="source" class="form-control" required>
                  <option value="">-- Select Platform --</option>
                  <option value="scriptlog">Scriptlog / Blogware (JSON)</option>
                  <option value="wordpress">WordPress (WXR)</option>
                  <option value="ghost">Ghost (JSON)</option>
                  <option value="blogspot">Blogspot / Blogger (XML)</option>
                </select>
                <p class="help-block">Choose the platform your content is exported from.</p>
              </div>
              
              <div class="form-group">
                <label for="import_file">Import File</label>
                <input type="file" name="import_file" id="import_file" accept=".xml,.json" required>
                <p class="help-block">Upload your export file (JSON for Scriptlog/Ghost, XML for WordPress/Blogspot).</p>
              </div>
              
              <div class="form-group">
                <label for="author_id">Assign Content to Author</label>
                <select name="author_id" id="author_id" class="form-control">
                  <?php if (!empty($users)) : ?>
                        <?php foreach ($users as $user) : ?>
                      <option value="<?= $user['ID']; ?>"><?= safe_html($user['user_fullname'] ?: $user['user_login']); ?> (<?= safe_html($user['user_level']); ?>)</option>
                        <?php endforeach; ?>
                  <?php else : ?>
                    <option value="1">Admin</option>
                  <?php endif; ?>
                </select>
                <p class="help-block">Select the author to assign imported content to.</p>
              </div>
              
              <div class="box-footer">
                <div class="btn-group pull-right" role="group" aria-label="Import actions">
                  <button type="submit" name="previewSubmit" class="btn btn-default btn-flat" style="margin-right: 10px;" title="Preview content before importing">
                    <i class="fa fa-eye" aria-hidden="true"></i> Preview
                  </button>
                  <button type="submit" name="importSubmit" class="btn btn-primary btn-flat" id="importBtn" title="Import content from the uploaded file" aria-describedby="importHelp">
                    <i class="fa fa-upload" aria-hidden="true"></i> Import
                  </button>
                </div>
                <span id="importHelp" class="help-block" style="margin-top: 10px; margin-bottom: 0;">
                  <strong>Preview</strong> allows you to review content before importing. <strong>Import</strong> will add content to your site.
                </span>
              </div>
              
            </form>
            
          </div>
        </div>
        
        <div class="box box-default">
          <div class="box-header with-border">
            <h3 class="box-title">Instructions</h3>
          </div>
          <div class="box-body">
            
            <h4>Scriptlog / Blogware</h4>
            <ol>
              <li>Go to Tools &gt; Export in another Scriptlog/Blogware installation</li>
              <li>Select "Scriptlog / Blogware (JSON)" as the destination</li>
              <li>Click "Export" to download the JSON file</li>
              <li>Upload the downloaded JSON file here</li>
            </ol>
            
            <h4>WordPress</h4>
            <ol>
              <li>Go to your WordPress admin panel</li>
              <li>Navigate to Tools &gt; Export</li>
              <li>Select "Posts" or "Pages" (or "All content")</li>
              <li>Click "Download Export File"</li>
              <li>Upload the downloaded XML file here</li>
            </ol>
            
            <h4>Ghost</h4>
            <ol>
              <li>Go to your Ghost admin panel</li>
              <li>Navigate to Labs &gt; Export</li>
              <li>Click "Export Content"</li>
              <li>Upload the downloaded JSON file here</li>
            </ol>
            
            <h4>Blogspot / Blogger</h4>
            <ol>
              <li>Go to your Blogger dashboard</li>
              <li>Go to Settings &gt; Other</li>
              <li>Click "Export blog"</li>
              <li>Download the XML file</li>
              <li>Upload the XML file here</li>
            </ol>
            
          </div>
        </div>
        
      </div>
    </div>
  </section>
  
  <script>
  (function() {
    document.getElementById('importBtn').addEventListener('click', function(e) {
      var selectedSource = document.getElementById('source').value;
      if (!selectedSource) {
        e.preventDefault();
        alert('Please select a source platform first.');
        return false;
      }
      return confirm('Are you sure you want to import content from ' + selectedSource + '?\n\nThis action will add new content to your site.');
    });
  })();
  </script>
</div>
