<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : "Downloads"; ?>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=downloads">Downloads</a></li>
        <li class="active">All Downloads</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          
          <?php if (isset($status)) : ?>
          <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-check"></i> Success!</h4>
                <?php
                foreach ($status as $s) :
                    echo $s;
                endforeach;
                ?>
          </div>
          <?php endif; ?>
          
          <?php if (isset($errors)) : ?>
          <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-ban"></i> Error!</h4>
                <?php
                foreach ($errors as $e) :
                    echo $e;
                endforeach;
                ?>
          </div>
          <?php endif; ?>
          
          <div class="box box-primary">
            <div class="box-header with-border">
              <h2 class="box-title">
                Download Records
              </h2>
            </div>
            
            <form id="downloads-form" method="post">
              <input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? $csrfToken : ''; ?>">
              
              <div class="box-body table-responsive">
                <div class="bulk-actions" style="margin-bottom: 15px;">
                  <select name="bulk-action" id="bulk-action" class="form-control" style="display: inline-block; width: auto;">
                    <option value="">-- Select Action --</option>
                    <option value="expire">Expire Selected</option>
                    <option value="regenerate">Regenerate Selected</option>
                    <option value="delete">Delete Selected</option>
                  </select>
                  <button type="submit" class="btn btn-primary" id="apply-bulk-action">Apply</button>
                  <span id="selected-count">0 selected</span>
                </div>
                
                <table id="scriptlog-table" class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th><input type="checkbox" id="select-all"></th>
                      <th>ID</th>
                      <th>File</th>
                      <th>Type</th>
                      <th>Identifier</th>
                      <th>Expires</th>
                      <th>Created</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      $allDownloads = $allDownloads ?? [];
                    $downloadService = $downloadService ?? null;

                    if (is_array($allDownloads) && !empty($allDownloads)) :
                        $no = 0;
                        foreach ($allDownloads as $download) :
                            $no++;
                            $isExpired = ($downloadService !== null) ? $downloadService->isDownloadExpired($download['media_identifier']) : false;
                            $downloadCount = ($downloadService !== null) ? $downloadService->getDownloadCountByMedia($download['media_id']) : 0;
                            ?>
                    <tr>
                      <td><input type="checkbox" name="downloads[]" value="<?= safe_html($download['media_identifier']); ?>"></td>
                      <td><?= $no; ?></td>
                      <td>
                        <strong><?= safe_html($download['media_caption'] ?? $download['media_filename']); ?></strong>
                        <br><small><?= safe_html($download['media_filename']); ?></small>
                      </td>
                      <td><?= safe_html($download['media_type']); ?></td>
                      <td><code><?= safe_html(substr($download['media_identifier'], 0, 8)); ?>...</code></td>
                      <td>
                            <?php if ($isExpired) : ?>
                          <span class="label label-danger">Expired</span>
                            <?php else : ?>
                          <span class="label label-success">Active</span>
                          <br><small><?= date('M j, Y', $download['before_expired']); ?></small>
                            <?php endif; ?>
                      </td>
                      <td><?= date('M j, Y H:i', strtotime($download['created_at'])); ?></td>
                        <td>
                        <a href="index.php?load=downloads&action=history&mediaId=<?= (int)$download['media_id']; ?>" class="btn btn-info btn-xs" title="View History">
                          <i class="fa fa-history"></i>
                        </a>
                        <a href="index.php?load=downloads&action=expire&identifier=<?= safe_html($download['media_identifier']); ?>" class="btn btn-warning btn-xs" title="Expire">
                          <i class="fa fa-clock-o"></i>
                        </a>
                        <a href="index.php?load=downloads&action=deleteDownload&identifier=<?= safe_html($download['media_identifier']); ?>" class="btn btn-danger btn-xs" title="Delete">
                          <i class="fa fa-trash"></i>
                        </a>
                      </td>
                    </tr>
                            <?php
                        endforeach;
                    else : ?>
                    <tr>
                      <td colspan="8" class="text-center">No download records found.</td>
                    </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
</div>

<script>
(function() {
  'use strict';
  
  document.getElementById('select-all').addEventListener('change', function() {
    var checkboxes = document.querySelectorAll('input[name="downloads[]"]');
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = this.checked;
    }
    updateSelectedCount();
  });
  
  document.querySelectorAll('input[name="downloads[]"]').forEach(function(cb) {
    cb.addEventListener('change', updateSelectedCount);
  });
  
  function updateSelectedCount() {
    var selected = document.querySelectorAll('input[name="downloads[]"]:checked').length;
    document.getElementById('selected-count').textContent = selected + ' selected';
  }
  
  document.getElementById('apply-bulk-action').addEventListener('click', function(e) {
    var action = document.getElementById('bulk-action').value;
    var selected = document.querySelectorAll('input[name="downloads[]"]:checked').length;
    
    if (action === '') {
      e.preventDefault();
      alert('Please select an action.');
      return false;
    }
    
    if (selected === 0) {
      e.preventDefault();
      alert('Please select at least one download.');
      return false;
    }
    
    return confirm('Are you sure you want to ' + action + ' ' + selected + ' download(s)?');
  });
})();
</script>
