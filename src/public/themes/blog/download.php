<?php

/**
 * download.php
 *
 * Download page template
 *
 * @category Theme Template
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 *
 */

if (!isset($downloadPageData)) {
    $downloadController = new DownloadController(new DownloadService(new DownloadModel(), new MediaDao()));

    $identifier = $requestPath->identifier ?? '';
    $downloadPageData = $downloadController->getDownloadPage($identifier);
}

call_theme_header();
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            
            <?php if (isset($downloadPageData['error'])) : ?>
            <div class="alert alert-danger">
                <h3>Download Error</h3>
                <p><?= $downloadPageData['error']; ?></p>
                <?php if (isset($downloadPageData['expired']) && $downloadPageData['expired']) : ?>
                <p>The download link has expired. Please contact the site administrator for a new link.</p>
                <?php endif; ?>
            </div>
            <?php else : ?>
            <div class="download-page">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa fa-download"></i> Download File
                        </h3>
                    </div>
                    <div class="panel-body">
                        
                        <div class="file-info">
                            <h4><?= safe_html($downloadPageData['media']['media_caption'] ?? 'Download File'); ?></h4>
                            <p class="text-muted">
                                <strong>Filename:</strong> <?= safe_html($downloadPageData['media']['media_filename']); ?><br>
                                <strong>Type:</strong> <?= safe_html($downloadPageData['media']['media_type']); ?><br>
                                <strong>Size:</strong> <?= $downloadPageData['file_size']; ?>
                            </p>
                        </div>
                        
                        <hr>
                        
                        <div class="download-action">
                            <a href="<?= $downloadPageData['download_url']; ?>/file" class="btn btn-lg btn-primary">
                                <i class="fa fa-download"></i> Download Now
                            </a>
                            
                            <div class="copy-link" style="margin-top: 15px;">
                                <p class="text-muted small">Share this link:</p>
                                <div class="input-group">
                                    <input type="text" class="form-control" value="<?= $downloadPageData['download_url']; ?>" readonly>
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button" onclick="copyToClipboard()">
                                            <i class="fa fa-copy"></i> Copy
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($downloadPageData['support_url'])) : ?>
                        <hr>
                        <div class="support-section text-center">
                            <p class="text-muted">Support this project</p>
                            <a href="<?= safe_html($downloadPageData['support_url']); ?>" class="btn btn-success" target="_blank">
                                <i class="fa fa-heart"></i> <?= safe_html($downloadPageData['support_label']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                    </div>
                    <div class="panel-footer text-muted">
                        <small>
                            <i class="fa fa-clock-o"></i> 
                            Expires: <?= date('F j, Y g:i A', $downloadPageData['expires_at']); ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
            
        </div>
    </div>
</div>

<script>
function copyToClipboard() {
    var copyText = document.querySelector('.copy-link input');
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    
    navigator.clipboard.writeText(copyText.value).then(function() {
        alert('Link copied to clipboard!');
    });
}
</script>

<?php call_theme_footer(); ?>
