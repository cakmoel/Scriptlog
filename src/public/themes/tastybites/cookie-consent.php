<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

// Load theme functions for t() translation
require_once dirname(__FILE__) . '/functions.php';

/**
 * Cookie Consent Banner
 * Renders GDPR-compliant cookie consent banner
 */
function render_cookie_consent(): string
{
    if (isset($_COOKIE['cookie_consent'])) {
        return '';
    }
    
    $defaultText = t('cookie.banner_text');
    $learnMore = t('cookie.learn_more');
    $acceptBtn = t('cookie.accept');
    
    $html = <<<HTML
<div id="cookie-consent-banner" class="cookie-consent-banner" role="dialog" aria-label="Cookie consent">
    <div class="cookie-consent-content">
        <p>{$defaultText}
        <a href="/privacy-policy">{$learnMore}</a></p>
        <div class="cookie-buttons">
            <button class="btn btn-primary btn-sm" id="cookie-accept">{$acceptBtn}</button>
        </div>
    </div>
</div>
<style>
.cookie-consent-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #fff;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    padding: 20px;
    z-index: 10000;
    display: none;
}
.cookie-consent-banner.show {
    display: block;
}
.cookie-consent-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}
.cookie-consent-content p {
    margin: 0;
    color: #333;
}
.cookie-consent-content a {
    color: #e63946;
}
</style>
<script>
(function() {
    if (!document.cookie.split('; ').find(function(row) { return row.startsWith('cookie_consent='); })) {
        document.getElementById('cookie-consent-banner').classList.add('show');
    }
    document.getElementById('cookie-accept').addEventListener('click', function() {
        var d = new Date();
        d.setTime(d.getTime() + (365*24*60*60*1000));
        document.cookie = 'cookie_consent=accepted; expires=' + d.toUTCString() + '; path=/';
        document.getElementById('cookie-consent-banner').classList.remove('show');
    });
})();
</script>
HTML;
    
    return $html;
}