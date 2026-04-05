<?php
defined('SCRIPTLOG') || die("Direct access not permitted");

$api = $this->api ?? [];
$errors = $this->errors ?? [];
$status = isset($_GET['status']) ? $_GET['status'] : "";
$csrfToken = $this->csrfToken ?? csrf_generate_token('csrfToken');
?>

<?php if ($status === 'apiConfigUpdated'): ?>
<div class="alert alert-success alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <h4><i class="icon fa fa-check"></i> Success!</h4>
  API settings have been updated successfully.
</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible">
  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
  <h4><i class="icon fa fa-ban"></i> Error!</h4>
  <?php foreach ($errors as $error): ?>
    <p><?= safe_html($error) ?></p>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row">
  <div class="col-md-12">
    <div class="box box-primary">
      <div class="box-header with-border">
        <h3 class="box-title">RESTful API Settings</h3>
      </div>

      <form method="post" action="<?= generate_request('index.php', 'get', ['option-api', 'apiConfig', 0])['link']; ?>">
        <input type="hidden" name="csrfToken" value="<?= $csrfToken ?>">

        <div class="box-body">
          <!-- Rate Limiting Toggle -->
          <div class="form-group">
            <label>
              <input type="checkbox" name="api_rate_limit_enabled" value="1" <?= ($api['api_rate_limit_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
              Enable Rate Limiting
            </label>
            <p class="help-block">Protect your API from abuse by limiting the number of requests per client.</p>
          </div>

          <div class="row">
            <div class="col-md-6">
              <!-- Read Rate Limit -->
              <div class="form-group">
                <label for="api_rate_limit_read">Read Rate Limit (requests per minute)</label>
                <input type="number" id="api_rate_limit_read" name="api_rate_limit_read"
                       class="form-control" value="<?= safe_html($api['api_rate_limit_read'] ?? '60') ?>"
                       min="1" max="1000">
                <p class="help-block">Maximum GET requests per client per minute. Default: 60</p>
              </div>
            </div>

            <div class="col-md-6">
              <!-- Write Rate Limit -->
              <div class="form-group">
                <label for="api_rate_limit_write">Write Rate Limit (requests per minute)</label>
                <input type="number" id="api_rate_limit_write" name="api_rate_limit_write"
                       class="form-control" value="<?= safe_html($api['api_rate_limit_write'] ?? '20') ?>"
                       min="1" max="500">
                <p class="help-block">Maximum POST/PUT/DELETE/PATCH requests per client per minute. Default: 20</p>
              </div>
            </div>
          </div>

          <!-- Info Box -->
          <div class="callout callout-info">
            <h4>How Rate Limiting Works</h4>
            <p>Rate limits are tracked per client using the following priority:</p>
            <ol>
              <li><strong>API Key</strong> (X-API-Key header)</li>
              <li><strong>Bearer Token</strong> (Authorization header)</li>
              <li><strong>IP Address</strong> (fallback)</li>
            </ol>
            <p>When a client exceeds the rate limit, they receive a <code>429 Too Many Requests</code> response with a <code>Retry-After</code> header.</p>
          </div>
        </div>

        <div class="box-footer">
          <button type="submit" name="apiConfigSubmit" class="btn btn-primary">
            <i class="fa fa-save"></i> Update API Settings
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
