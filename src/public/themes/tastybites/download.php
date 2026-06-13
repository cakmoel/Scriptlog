<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

$identifier = isset($_GET['download']) ? trim($_GET['download']) : '';

if (empty($identifier)) {
    $identifier = $GLOBALS['download_identifier'] ?? '';
}

if (empty($identifier) && function_exists('is_permalink_enabled')
    && is_permalink_enabled() === 'yes') {
    $identifier = $requestPath->identifier ?? '';
}

$downloadPageData = function_exists('get_download_page_data')
    ? get_download_page_data($identifier)
    : [];

$fileType      = $downloadPageData['media']['media_type'] ?? '';
$fileIcon      = 'fa-file-o';
if      (strpos($fileType, 'image/')      === 0)    $fileIcon = 'fa-file-image-o';
elseif  (strpos($fileType, 'video/')      === 0)    $fileIcon = 'fa-file-video-o';
elseif  (strpos($fileType, 'audio/')      === 0)    $fileIcon = 'fa-file-audio-o';
elseif  (strpos($fileType, 'pdf')         !== false) $fileIcon = 'fa-file-pdf-o';
elseif  (strpos($fileType, 'zip')         !== false
      || strpos($fileType, 'compressed')  !== false) $fileIcon = 'fa-file-archive-o';
elseif  (strpos($fileType, 'text/')       === 0)    $fileIcon = 'fa-file-text-o';

$filename      = $downloadPageData['media']['media_filename'] ?? '';
$fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">

            <?php if (isset($downloadPageData['error'])) : ?>
            <div class="alert alert-danger" role="alert">
                <h3>Download Error</h3>
                <p><?= safe_html($downloadPageData['error']); ?></p>
                <?php if (!empty($downloadPageData['expired'])) : ?>
                <p>The download link has expired. Please contact the site administrator for a new link.</p>
                <?php endif; ?>
            </div>
            <?php else : ?>
            <div class="download-page">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h3 class="panel-title">
                            <i class="fa <?= $fileIcon; ?>"></i>
                            <?= safe_html($downloadPageData['media']['media_caption'] ?? 'Download File'); ?>
                        </h3>
                    </div>
                    <div class="panel-body">

                        <div class="file-info">
                            <?php if (!empty($fileExtension)) : ?>
                            <span class="label label-default"><?= strtoupper(safe_html($fileExtension)); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($downloadPageData['media']['media_type'])) : ?>
                            <span class="label label-info"><?= safe_html($downloadPageData['media']['media_type']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($downloadPageData['file_size'])) : ?>
                            <span class="label label-default"><?= safe_html($downloadPageData['file_size']); ?></span>
                            <?php endif; ?>
                            <dl class="dl-horizontal" style="margin-top: 10px;">
                                <dt>Filename</dt>
                                <dd><?= safe_html($downloadPageData['media']['media_filename'] ?? ''); ?></dd>
                                <dt>Type</dt>
                                <dd><?= safe_html($downloadPageData['media']['media_type'] ?? ''); ?></dd>
                                <dt>Size</dt>
                                <dd><?= safe_html($downloadPageData['file_size'] ?? ''); ?></dd>
                            </dl>
                        </div>

                        <hr>

                        <div class="download-action">
                            <a href="<?= safe_html($downloadPageData['download_url']); ?>" class="btn btn-lg btn-primary">
                                <i class="fa fa-download"></i> Download Now
                            </a>

                            <div class="copy-link" style="margin-top: 15px;">
                                <p class="text-muted small">Share this link:</p>
                                <div class="input-group">
                                    <input type="text" id="download-share-url" class="form-control" value="<?= safe_html($downloadPageData['download_url']); ?>" readonly>
                                    <span class="input-group-btn">
                                        <button id="copy-link-btn" class="btn btn-default" type="button" onclick="copyDownloadLink()" aria-label="Copy download link to clipboard">
                                            <i class="fa fa-copy"></i> Copy
                                        </button>
                                    </span>
                                </div>
                                <div id="copy-status" aria-live="polite" role="status"></div>
                            </div>
                        </div>

                        <?php if (!empty($downloadPageData['support_url'])) : ?>
                        <hr>
                        <div class="support-section text-center">
                            <p class="text-muted">Support this project</p>
                            <a href="<?= safe_html($downloadPageData['support_url']); ?>" class="btn btn-success" target="_blank">
                                <i class="fa fa-heart"></i> <?= !empty($downloadPageData['support_label']) ? safe_html($downloadPageData['support_label']) : safe_html($downloadPageData['support_url']); ?>
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
(function () {
    'use strict';

    window.copyDownloadLink = function () {
        var input  = document.getElementById('download-share-url');
        var btn    = document.getElementById('copy-link-btn');
        var status = document.getElementById('copy-status');
        if (!input || !btn) return;

        input.select();
        input.setSelectionRange(0, 99999);

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(input.value).then(
                function () { showCopySuccess(btn, status); },
                function () { fallbackCopy(input, btn, status); }
            );
        } else {
            fallbackCopy(input, btn, status);
        }
    };

    function fallbackCopy(input, btn, status) {
        var ta = document.createElement('textarea');
        ta.value = input.value;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            showCopySuccess(btn, status);
        } catch (e) {
            showCopyError(btn, status);
        }
        document.body.removeChild(ta);
    }

    function showCopySuccess(btn, status) {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-check"></i> Copied!';
        btn.setAttribute('aria-label', 'Link copied to clipboard');
        if (status) status.textContent = 'Link copied to clipboard!';
        setTimeout(function () {
            btn.innerHTML = orig;
            btn.setAttribute('aria-label', 'Copy download link to clipboard');
            if (status) status.textContent = '';
        }, 2000);
    }

    function showCopyError(btn, status) {
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-times"></i> Failed';
        if (status) status.textContent = 'Failed to copy. Please copy manually.';
        setTimeout(function () {
            btn.innerHTML = orig;
            if (status) status.textContent = '';
        }, 2000);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.getElementById('copy-link-btn');
        if (btn) {
            btn.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    window.copyDownloadLink();
                }
            });
        }
    });
}());
</script>