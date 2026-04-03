<?php if (!defined('SCRIPTLOG')) {
    exit();
} ?>

<?php
$currentSettings = $currentSettings ?? [];
$defaultMimeTypes = $defaultMimeTypes ?? [];
$status = $status ?? [];
$errors = $errors ?? [];
?>

<div class="content-wrapper">
    <section class="content-header">
      <h1>
        <?= isset($pageTitle) ? $pageTitle : "Download Settings"; ?>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=option-general">Settings</a></li>
        <li class="active">Download Settings</li>
      </ol>
    </section>

    <section class="content">
      <div class="row">
        <div class="col-xs-12">
          
          <?php if (isset($status) && !empty($status)) : ?>
          <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-check"></i> Success!</h4>
                <?php foreach ($status as $s) :
                    echo $s;
                endforeach; ?>
          </div>
          <?php endif; ?>
          
          <?php if (isset($errors) && !empty($errors)) : ?>
          <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h4><i class="icon fa fa-ban"></i> Error!</h4>
                <?php foreach ($errors as $e) :
                    echo $e;
                endforeach; ?>
          </div>
          <?php endif; ?>
          
          <form method="post" action="index.php?load=option-downloads&action=downloadConfig">
            <input type="hidden" name="csrfToken" value="<?= (isset($csrfToken)) ? $csrfToken : ''; ?>">
            
            <div class="box box-primary">
              <div class="box-header with-border">
                <h3 class="box-title">Allowed File Types</h3>
              </div>
              <div class="box-body">
                <p class="help-block">Select which MIME types are allowed for download.</p>
                <div class="row">
                  <?php
                    $chunks = array_chunk($defaultMimeTypes, 4);
                    foreach ($chunks as $chunk) :
                        ?>
                  <div class="col-md-3">
                                        <?php foreach ($chunk as $mime) : ?>
                    <div class="checkbox">
                      <label>
                        <input type="checkbox" name="allowed_mime_types[]" value="<?= $mime; ?>"
                                            <?= in_array($mime, $currentSettings['allowed_mime_types'] ?? []) ? 'checked' : ''; ?>>
                                            <?= basename($mime); ?>
                      </label>
                    </div>
                                        <?php endforeach; ?>
                  </div>
                    <?php endforeach; ?>
                </div>
              </div>
            </div>
            
            <div class="box box-primary">
              <div class="box-header with-border">
                <h3 class="box-title">Link Expiration</h3>
              </div>
              <div class="box-body">
                <div class="form-group">
                  <label for="expiry-hours">Expiration Time (hours)</label>
                  <input type="number" name="expiry_hours" id="expiry-hours" 
                         class="form-control" value="<?= (int)($currentSettings['expiry_hours'] ?? 8); ?>" 
                         min="1" max="720">
                  <p class="help-block">Download links will expire after this many hours.</p>
                </div>
              </div>
            </div>
            
            <div class="box box-primary">
              <div class="box-header with-border">
                <h3 class="box-title">Hotlink Protection</h3>
              </div>
              <div class="box-body">
                <div class="form-group">
                  <label>
                    <input type="checkbox" name="hotlink_protection" value="1"
                    <?= ($currentSettings['hotlink_protection'] ?? false) ? 'checked' : ''; ?>>
                    Enable hotlink protection
                  </label>
                  <p class="help-block">When enabled, downloads will only work from allowed domains.</p>
                </div>
                <div class="form-group">
                  <label for="allowed-domains">Allowed Domains (one per line)</label>
                  <textarea name="allowed_domains" id="allowed-domains" class="form-control" rows="4"
                  placeholder="example.com&#10;www.example.com"><?= implode("\n", $currentSettings['allowed_domains'] ?? []); ?></textarea>
                  <p class="help-block">Enter domains (without http://) that are allowed to link to downloads.</p>
                </div>
              </div>
            </div>
            
            <div class="box box-primary">
              <div class="box-header with-border">
                <h3 class="box-title">Support/Donation</h3>
              </div>
              <div class="box-body">
                <div class="form-group">
                  <label for="support-url">Support URL</label>
                  <input type="url" name="support_url" id="support-url" class="form-control"
                         value="<?= safe_html($currentSettings['support_url'] ?? ''); ?>"
                         placeholder="https://opencollective.com/...">
                  <p class="help-block">Optional: Display a support/donation button on download pages.</p>
                </div>
                <div class="form-group">
                  <label for="support-label">Button Label</label>
                  <input type="text" name="support_label" id="support-label" class="form-control"
                         value="<?= safe_html($currentSettings['support_label'] ?? 'Support'); ?>"
                         placeholder="Support">
                </div>
              </div>
            </div>
            
            <div class="box-footer">
              <button type="submit" name="downloadSettingSubmit" class="btn btn-primary">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </section>
</div>
