<?php

/**
 * Privacy Policy Page
 *
 * Loads privacy policy content from database based on current locale.
 *
 * @category Theme Template
 * @author   Scriptlog
 * @license  MIT
 * @version  1.0
 */

$currentLocale = function_exists('get_locale') ? get_locale() : 'en';
$policy = null;

if (class_exists('PrivacyPolicyDao')) {
    try {
        $privacyDao = new PrivacyPolicyDao();
        $policy = $privacyDao->findByLocale($currentLocale);

        if (!$policy) {
            $policy = $privacyDao->findDefault();
        }
    } catch (Throwable $e) {
        $policy = null;
    }
}

$privacy_title = $policy['policy_title'] ?? t('privacy.page_title');
$privacy_content = $policy['policy_content'] ?? "";
$site_name = function_exists('app_sitename') ? app_sitename() : 'Our Website';
$contact_info = function_exists('app_info') ? app_info() : [];
$contact_email = isset($contact_info['site_email']) ? $contact_info['site_email'] : 'admin@example.com';
$last_updated = !empty($policy['updated_at']) ? date('F j, Y', strtotime($policy['updated_at'])) : date('F j, Y');
$last_updated_label = t('privacy.last_updated');
?>

<div class="privacy-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <article class="privacy-card">
                    <header class="privacy-header">
                        <h1><?= htmlout($privacy_title); ?></h1>
                        <div class="privacy-meta">
                            <i class="fa fa-calendar-check-o" aria-hidden="true"></i> 
                            <?= $last_updated_label; ?>: <?= htmlout($last_updated); ?>
                        </div>
                    </header>
                    <section class="privacy-body">
                        <?php if (!empty($privacy_content)) : ?>
                            <?= $privacy_content; ?>
                        <?php else : ?>
                            <p class="lead mt-0 mb-1">
                                This Privacy Policy describes how <strong><?= htmlout($site_name); ?></strong> ("we," "us," or "our") collects, uses, and discloses your information. We are committed to protecting your privacy and handling your data in an open and transparent manner.
                            </p>
                            <h2><?= t('privacy.information_we_collect'); ?></h2>
                            <p>We may collect the following types of information:</p>
                            <ul>
                                <li><strong>Account Information:</strong> Username, email address, and hashed credentials.</li>
                                <li><strong>User-Generated Content:</strong> Blog posts, comments, and uploaded media.</li>
                                <li><strong>Technical Information:</strong> IP address, browser type, and interaction telemetry.</li>
                            </ul>

                            <h2><?= t('privacy.how_we_use'); ?></h2>
                            <p>The information we collect is utilized for:</p>
                            <ul>
                                <li>Maintaining and operating our core services.</li>
                                <li>Providing dedicated customer support.</li>
                                <li>Enhancing software security and performance.</li>
                                <li>Analyzing usage trends to improve user experience.</li>
                            </ul>

                            <h2><?= t('privacy.data_security'); ?></h2>
                            <p>We implement multi-layered security protocols to safeguard your data:</p>
                            <ul>
                                <li><strong>Cryptographic Hashing:</strong> Passwords are never stored in plaintext.</li>
                                <li><strong>Encryption:</strong> Sensitive data is protected via Defuse PHP Encryption.</li>
                                <li><strong>Proactive Defense:</strong> Built-in protection against XSS and CSRF.</li>
                                <li><strong>Input Safety:</strong> Prepared statements for all database interactions.</li>
                            </ul>

                            <h2><?= t('privacy.your_rights'); ?></h2>
                            <p>You maintain the right to access, rectify, or request the deletion of your personal information at any time.</p>

                            <h2><?= t('privacy.contact_us'); ?></h2>
                            <p>
                                For inquiries regarding this policy, please reach us at: 
                                <a href="mailto:<?= htmlout($contact_email); ?>" class="text-primary fw-bold"><?= htmlout($contact_email); ?></a>
                            </p>
                        <?php endif; ?>
                    </section>

                    <footer class="privacy-footer">
                        <a href="<?= app_url(); ?>" class="privacy-back-btn">
                            <i class="fa fa-arrow-left"></i> <?= t('404.back_home'); ?>
                        </a>
                    </footer>
                </article>
            </div>
        </div>
    </div>
</div>
