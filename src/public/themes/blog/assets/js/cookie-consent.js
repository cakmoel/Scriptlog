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
        bannerId: 'cookie-consent-banner',
        apiEndpoint: null,

        /**
         * Initialize the cookie consent banner
         */
        init: function() {
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
         * Check if consent cookie exists
         */
        hasConsentCookie: function() {
            const cookies = document.cookie.split(';');
            for (let i = 0; i < cookies.length; i++) {
                const cookie = cookies[i].trim();
                if (cookie.indexOf(this.cookieName + '=') === 0) {
                    return true;
                }
            }
            return false;
        },

        /**
         * Get consent cookie value
         */
        getConsentValue: function() {
            const name = this.cookieName + '=';
            const decodedCookie = decodeURIComponent(document.cookie);
            const ca = decodedCookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1);
                }
                if (c.indexOf(name) === 0) {
                    return c.substring(name.length, c.length);
                }
            }
            return null;
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
         * Set consent cookie
         */
        setConsentCookie: function(value, days = 365) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            const expires = 'expires=' + date.toUTCString();
            document.cookie = this.cookieName + '=' + value + ';' + expires + ';path=/;samesite=Lax';
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
