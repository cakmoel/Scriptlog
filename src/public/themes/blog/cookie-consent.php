<?php

/**
 * Cookie Consent Banner Template
 *
 * Renders the cookie consent banner HTML
 *
 * @category Theme Template
 * @author   Scriptlog
 * @license  MIT
 * @version  1.0
 */

// Get privacy policy URL
$privacyUrl = function_exists('get_privacy_policy_url') ? get_privacy_policy_url() : app_url() . '/privacy';

// Get site name
$siteName = function_exists('app_sitename') ? app_sitename() : 'Our Website';
?>

<div id="cookie-consent-banner" 
     class="cookie-consent-banner hidden" 
     data-privacy-url="<?= htmlout($privacyUrl); ?>"
     data-api-endpoint="<?= app_url(); ?>/api/v1/gdpr/consent"
     role="dialog"
     aria-labelledby="cookie-consent-title"
     aria-describedby="cookie-consent-description">
    
    <div class="cookie-consent-container">
        <div class="cookie-consent-content">
            <h3 id="cookie-consent-title" class="cookie-consent-title">
                <i class="fa fa-info-circle" aria-hidden="true"></i> <?= t('cookie_consent.banner.title'); ?>
            </h3>
            <p id="cookie-consent-description" class="cookie-consent-text">
                <?= htmlout($siteName); ?> <?= t('cookie_consent.banner.description'); ?>
                <a href="<?= htmlout($privacyUrl); ?>" target="_blank"><?= t('cookie_consent.privacy.link'); ?></a>
            </p>
        </div>
        
        <div class="cookie-consent-buttons">
            <button type="button" class="cookie-btn cookie-btn-reject" aria-label="<?= t('cookie_consent.buttons.reject'); ?>">
                <?= t('cookie_consent.buttons.reject'); ?>
            </button>
            <button type="button" class="cookie-btn cookie-btn-learn-more" aria-label="<?= t('cookie_consent.buttons.learn_more'); ?>">
                <?= t('cookie_consent.buttons.learn_more'); ?>
            </button>
            <button type="button" class="cookie-btn cookie-btn-accept" aria-label="<?= t('cookie_consent.buttons.accept'); ?>">
                <?= t('cookie_consent.buttons.accept'); ?>
            </button>
        </div>
    </div>
</div>