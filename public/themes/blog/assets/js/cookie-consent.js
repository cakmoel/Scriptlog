/**
 * Cookie Consent Banner JavaScript
 *
 * Handles cookie consent banner interactions
 *
 * @category Theme Assets
 * @author   Scriptlog
 * @license  MIT
 * @version  1.0
 */

(function() {
    'use strict';

    const CookieConsent = {
        cookieName: 'cookie_consent',
        versionCookieName: 'cookie_consent_version',
        bannerId: 'cookie-consent-banner',
        apiEndpoint: null,
        consentLifetime: 180,
        consentVersion: '1',

        /**
         * Initialize the cookie consent banner
         */
        init: function() {
            // Read behavior config from banner data attributes
            this.readConfig();

            // Set API endpoint
            this.apiEndpoint = this.getApiEndpoint();

            // Check if consent already given
            if (!this.hasConsentCookie()) {
                // Show banner after a short delay
                setTimeout(() => {
                    this.showBanner();
                }, 500);
            }

            // Bind event listeners
            this.bindEvents();
        },

        /**
         * Read consent lifetime and version from banner data attributes
         */
        readConfig: function() {
            const banner = document.getElementById(this.bannerId);
            if (!banner) return;

            if (banner.dataset.consentLifetime) {
                const lifetime = parseInt(banner.dataset.consentLifetime, 10);
                if (!isNaN(lifetime) && lifetime > 0) {
                    this.consentLifetime = lifetime;
                }
            }

            if (banner.dataset.consentVersion) {
                this.consentVersion = banner.dataset.consentVersion;
            }
        },

        /**
         * Get the API endpoint for consent processing
         */
        getApiEndpoint: function() {
            // Try to get from data attribute first
            const banner = document.getElementById(this.bannerId);
            if (banner && banner.dataset.apiEndpoint) {
                return banner.dataset.apiEndpoint;
            }
            // Fallback to default
            return window.appUrl ? window.appUrl + '/api/v1/gdpr/consent' : '/api/v1/gdpr/consent';
        },

        /**
         * Get cookie value by name
         */
        getCookieValue: function(name) {
            const decodedCookie = decodeURIComponent(document.cookie);
            const ca = decodedCookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name + '=') === 0) {
                    return c.substring(name.length + 1, c.length);
                }
            }
            return null;
        },

        /**
         * Check if a valid consent cookie exists for the current version
         */
        hasConsentCookie: function() {
            const status = this.getCookieValue(this.cookieName);
            if (status === null) {
                return false;
            }

            const version = this.getCookieValue(this.versionCookieName);
            return version === this.consentVersion;
        },

        /**
         * Get consent cookie value
         */
        getConsentValue: function() {
            return this.getCookieValue(this.cookieName);
        },

        /**
         * Get consent version cookie value
         */
        getConsentVersion: function() {
            return this.getCookieValue(this.versionCookieName);
        },

        /**
         * Show the consent banner
         */
        showBanner: function() {
            const banner = document.getElementById(this.bannerId);
            if (banner) {
                banner.classList.add('show');
                banner.classList.remove('hidden');
                // Trigger animation
                setTimeout(() => {
                    banner.classList.add('animate');
                }, 10);
            }
        },

        /**
         * Hide the consent banner
         */
        hideBanner: function() {
            const banner = document.getElementById(this.bannerId);
            if (banner) {
                banner.classList.remove('show');
                setTimeout(() => {
                    banner.classList.add('hidden');
                }, 300);
            }
        },

        /**
         * Set consent cookie (status + version) for the configured lifetime
         */
        setConsentCookie: function(value, days) {
            const lifetime = days || this.consentLifetime;
            const date = new Date();
            date.setTime(date.getTime() + (lifetime * 24 * 60 * 60 * 1000));
            const expires = 'expires=' + date.toUTCString();
            document.cookie = this.cookieName + '=' + value + ';' + expires + ';path=/;samesite=Lax';
            document.cookie = this.versionCookieName + '=' + this.consentVersion + ';' + expires + ';path=/;samesite=Lax';
        },

        /**
         * Clear consent cookies and re-show the banner (cookie settings)
         */
        resetConsent: function() {
            document.cookie = this.cookieName + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;samesite=Lax';
            document.cookie = this.versionCookieName + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;samesite=Lax';
            this.showBanner();
        },

        /**
         * Send consent to server
         */
        sendConsentToServer: function(status) {
            return fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ status: status })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .catch(error => {
                console.error('Error sending consent:', error);
                // Continue anyway - cookie is the primary storage
                return { success: true };
            });
        },

        /**
         * Handle accept button click
         */
        acceptAll: function() {
            const status = 'accepted';
            
            // Set cookie locally
            this.setConsentCookie(status);
            
            // Send to server
            this.sendConsentToServer(status);
            
            // Hide banner
            this.hideBanner();
        },

        /**
         * Handle reject button click
         */
        rejectAll: function() {
            const status = 'rejected';
            
            // Set cookie locally
            this.setConsentCookie(status);
            
            // Send to server
            this.sendConsentToServer(status);
            
            // Hide banner
            this.hideBanner();
        },

        /**
         * Handle learn more link click
         */
        learnMore: function() {
            // Redirect to privacy policy page
            const privacyUrl = document.getElementById(this.bannerId);
            if (privacyUrl && privacyUrl.dataset.privacyUrl) {
                window.location.href = privacyUrl.dataset.privacyUrl;
            } else {
                window.location.href = '/privacy';
            }
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            const banner = document.getElementById(this.bannerId);
            if (!banner) return;

            // Accept button
            const acceptBtn = banner.querySelector('.cookie-btn-accept');
            if (acceptBtn) {
                acceptBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.acceptAll();
                });
            }

            // Reject button
            const rejectBtn = banner.querySelector('.cookie-btn-reject');
            if (rejectBtn) {
                rejectBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.rejectAll();
                });
            }

            // Learn more link
            const learnMoreBtn = banner.querySelector('.cookie-btn-learn-more');
            if (learnMoreBtn) {
                learnMoreBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.learnMore();
                });
            }

            // Cookie settings re-trigger links (delegated - live in footer/privacy page)
            document.addEventListener('click', (e) => {
                const trigger = e.target.closest('[data-cookie-settings]');
                if (!trigger) return;
                e.preventDefault();
                this.resetConsent();
            });

            // Keyboard accessibility
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && banner.classList.contains('show')) {
                    // Allow closing with Escape but still require choice
                    // This is optional - some prefer to force a choice
                }
            });
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            CookieConsent.init();
        });
    } else {
        CookieConsent.init();
    }

    // Expose to global scope for manual control
    window.CookieConsent = CookieConsent;

})();
