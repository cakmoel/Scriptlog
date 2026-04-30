<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Always get identifier from $_GET first - works for both SEO and query string modes
$identifier = isset($_GET['download']) ? trim($_GET['download']) : '';

// Fallback to global variable set by Dispatcher/HandleRequest for path-based URLs
if (empty($identifier)) {
    $identifier = $GLOBALS['download_identifier'] ?? '';
}

// For SEO-friendly URLs with permalinks enabled, also check request path
if (empty($identifier) && function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes') {
    $identifier = $requestPath->identifier ?? '';
}

$downloadPageData = function_exists('get_download_page_data') ? get_download_page_data($identifier) : [];

// Extract file type icon
$fileType = $downloadPageData['media']['media_type'] ?? '';
$fileIcon = 'fa-file-o';
if (strpos($fileType, 'image/') === 0) $fileIcon = 'fa-file-image-o';
elseif (strpos($fileType, 'video/') === 0) $fileIcon = 'fa-file-video-o';
elseif (strpos($fileType, 'audio/') === 0) $fileIcon = 'fa-file-audio-o';
elseif (strpos($fileType, 'pdf') !== false) $fileIcon = 'fa-file-pdf-o';
elseif (strpos($fileType, 'zip') !== false || strpos($fileType, 'compressed') !== false) $fileIcon = 'fa-file-archive-o';
elseif (strpos($fileType, 'text/') === 0) $fileIcon = 'fa-file-text-o';

$filename = $downloadPageData['media']['media_filename'] ?? '';
$fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
?>

<section class="download-page-section" aria-labelledby="download-heading">
    <div class="download-container">
        <div class="download-card" role="region" aria-label="File download">

            <?php if (isset($downloadPageData['error'])) : ?>
            <!-- Error State -->
            <div class="download-error" role="alert">
                <div class="download-error-icon">
                    <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                </div>
                <h2 class="download-error-title" id="download-heading">Download Unavailable</h2>
                <p class="download-error-message"><?= safe_html($downloadPageData['error']); ?></p>
                <?php if (isset($downloadPageData['expired']) && $downloadPageData['expired']) : ?>
                <p class="download-error-hint">This download link has expired. Please contact the site administrator for a new link.</p>
                <?php endif; ?>
            </div>

            <?php else : ?>
            <!-- Success State -->
            <div class="download-header">
                <div class="download-file-icon" aria-hidden="true">
                    <i class="fa <?= $fileIcon; ?>"></i>
                </div>
                <div class="download-file-meta">
                    <h1 class="download-file-title" id="download-heading">
                        <?= safe_html($downloadPageData['media']['media_caption'] ?? 'Download File'); ?>
                    </h1>
                    <div class="download-file-badges">
                        <?php if (!empty($fileExtension)) : ?>
                        <span class="download-badge badge-ext"><?= strtoupper(safe_html($fileExtension)); ?></span>
                        <?php endif; ?>
                        <span class="download-badge badge-type"><?= safe_html($downloadPageData['media']['media_type'] ?? 'File'); ?></span>
                        <span class="download-badge badge-size"><?= $downloadPageData['file_size'] ?? 'Unknown'; ?></span>
                    </div>
                </div>
            </div>

            <div class="download-details">
                <div class="download-detail-row">
                    <div class="download-detail-label">
                        <i class="fa fa-file-text-o" aria-hidden="true"></i>
                        <span>Filename</span>
                    </div>
                    <div class="download-detail-value download-detail-value--filename" title="<?= safe_html($filename); ?>"><?= safe_html($filename); ?></div>
                </div>
                <div class="download-detail-divider"></div>
                <div class="download-detail-row">
                    <div class="download-detail-label">
                        <i class="fa fa-file-o" aria-hidden="true"></i>
                        <span>Type</span>
                    </div>
                    <div class="download-detail-value"><?= safe_html($downloadPageData['media']['media_type'] ?? 'N/A'); ?></div>
                </div>
                <div class="download-detail-divider"></div>
                <div class="download-detail-row">
                    <div class="download-detail-label">
                        <i class="fa fa-weight" aria-hidden="true"></i>
                        <span>Size</span>
                    </div>
                    <div class="download-detail-value"><?= $downloadPageData['file_size'] ?? 'Unknown'; ?></div>
                </div>
            </div>

            <div class="download-actions">
                <a href="<?= safe_html($downloadPageData['download_url'] ?? '#'); ?>"
                   class="download-btn download-btn-primary"
                   role="button"
                   aria-label="Download <?= safe_html($downloadPageData['media']['media_caption'] ?? 'file'); ?>">
                    <i class="fa fa-download" aria-hidden="true"></i>
                    <span>Download Now</span>
                </a>
            </div>

            <div class="download-share">
                <label class="download-share-label" for="download-share-url">
                    <i class="fa fa-link" aria-hidden="true"></i> Share this link
                </label>
                <div class="download-share-input-group">
                    <input type="text"
                           id="download-share-url"
                           class="download-share-input"
                           value="<?= safe_html($downloadPageData['download_url'] ?? ''); ?>"
                           readonly
                           aria-label="Download link URL"
                           aria-describedby="copy-status">
                    <button type="button"
                            class="download-share-btn"
                            id="copy-link-btn"
                            aria-label="Copy download link to clipboard"
                            onclick="copyDownloadLink()">
                        <i class="fa fa-copy" aria-hidden="true"></i>
                        <span>Copy</span>
                    </button>
                </div>
                <div id="copy-status" class="copy-status" aria-live="polite" role="status"></div>
            </div>

            <?php if (!empty($downloadPageData['support_url'])) : ?>
            <div class="download-support">
                <p class="download-support-text">Support this project</p>
                <a href="<?= safe_html($downloadPageData['support_url']); ?>"
                   class="download-btn download-btn-support"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="Support this project (opens in new tab)">
                    <i class="fa fa-heart" aria-hidden="true"></i>
                    <span><?= safe_html($downloadPageData['support_label']); ?></span>
                </a>
            </div>
            <?php endif; ?>

            <div class="download-footer">
                <div class="download-expiry-pill">
                    <i class="fa fa-clock-o" aria-hidden="true"></i>
                    <span>Expires <?= date('M j, Y', $downloadPageData['expires_at']); ?> at <?= date('g:i A', $downloadPageData['expires_at']); ?></span>
                </div>
            </div>

            <?php endif; ?>

        </div>
    </div>
</section>

<script>
(function() {
    'use strict';

    window.copyDownloadLink = function() {
        var input = document.getElementById('download-share-url');
        var btn = document.getElementById('copy-link-btn');
        var status = document.getElementById('copy-status');

        if (!input || !btn) return;

        var text = input.value;

        // Select text for visual feedback
        input.select();
        input.setSelectionRange(0, 99999);

        // Try modern Clipboard API first
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                showCopySuccess(btn, status);
            }, function() {
                fallbackCopy(input, text, btn, status);
            });
        } else {
            fallbackCopy(input, text, btn, status);
        }
    };

    function fallbackCopy(input, text, btn, status) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');
            showCopySuccess(btn, status);
        } catch (err) {
            showCopyError(btn, status);
        }

        document.body.removeChild(textarea);
    }

    function showCopySuccess(btn, status) {
        // Button visual feedback
        var originalHTML = btn.innerHTML;
        btn.classList.add('copied');
        btn.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i> <span>Copied!</span>';
        btn.setAttribute('aria-label', 'Link copied to clipboard');

        // Status for screen readers
        if (status) {
            status.textContent = 'Link copied to clipboard!';
        }

        // Reset after 2 seconds
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = originalHTML;
            btn.setAttribute('aria-label', 'Copy download link to clipboard');
            if (status) {
                status.textContent = '';
            }
        }, 2000);
    }

    function showCopyError(btn, status) {
        var originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fa fa-times" aria-hidden="true"></i> <span>Failed</span>';

        if (status) {
            status.textContent = 'Failed to copy. Please copy manually.';
        }

        setTimeout(function() {
            btn.innerHTML = originalHTML;
            if (status) {
                status.textContent = '';
            }
        }, 2000);
    }

    // Keyboard support: Enter/Space on button
    document.addEventListener('DOMContentLoaded', function() {
        var btn = document.getElementById('copy-link-btn');
        if (btn) {
            btn.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    window.copyDownloadLink();
                }
            });
        }
    });
})();
</script>
