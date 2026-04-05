/**
 * RTL (Right-to-Left) JavaScript Handler for Blogware Theme
 * 
 * Handles dynamic RTL/LTR switching and related functionality.
 * 
 * @version 1.0
 * @since 1.0
 */

(function() {
    'use strict';

    /**
     * RTL Manager Class
     */
    var RTLManager = {
        config: {
            cookieName: 'scriptlog_locale_dir',
            cookieExpiry: 365,
            bodyClass: 'rtl',
            defaultDirection: 'ltr'
        },

        /**
         * Initialize RTL handling
         */
        init: function() {
            this.bindEvents();
            this.updateIcons();
            this.updateFormDirections();
        },

        /**
         * Bind event listeners
         */
        bindEvents: function() {
            var self = this;

            // Handle language switch
            document.addEventListener('click', function(e) {
                var target = e.target;
                if (target.matches('[data-locale-switch]') || target.closest('[data-locale-switch]')) {
                    var locale = target.dataset.locale || target.closest('[data-locale-switch]').dataset.locale;
                    if (locale) {
                        self.switchLocale(locale);
                    }
                }
            });

            // Handle direction toggle
            document.addEventListener('click', function(e) {
                if (e.target.matches('[data-toggle-direction]') || e.target.closest('[data-toggle-direction]')) {
                    e.preventDefault();
                    self.toggleDirection();
                }
            });

            // Listen for custom direction change events
            document.addEventListener('scriptlog:direction:change', function(e) {
                if (e.detail && e.detail.direction) {
                    self.setDirection(e.detail.direction);
                }
            });

            // Listen for locale change events
            document.addEventListener('scriptlog:locale:change', function(e) {
                if (e.detail && e.detail.locale) {
                    self.onLocaleChange(e.detail.locale);
                }
            });
        },

        /**
         * Check if current direction is RTL
         */
        isRtl: function() {
            return document.documentElement.dir === 'rtl' || 
                   document.body.classList.contains(this.config.bodyClass);
        },

        /**
         * Get current direction
         */
        getDirection: function() {
            return this.isRtl() ? 'rtl' : 'ltr';
        },

        /**
         * Set document direction
         */
        setDirection: function(direction) {
            var isRtl = direction === 'rtl';
            
            // Update document dir attribute
            document.documentElement.dir = direction;
            document.documentElement.lang = direction === 'rtl' ? 'ar' : 'en';
            
            // Toggle body class
            if (isRtl) {
                document.body.classList.add(this.config.bodyClass);
            } else {
                document.body.classList.remove(this.config.bodyClass);
            }

            // Update meta viewport for RTL
            this.updateMetaViewport(direction);
            
            // Save preference
            this.savePreference(direction);
            
            // Dispatch event
            this.dispatchEvent('scriptlog:direction:changed', { direction: direction });
        },

        /**
         * Toggle between RTL and LTR
         */
        toggleDirection: function() {
            var newDirection = this.isRtl() ? 'ltr' : 'rtl';
            this.setDirection(newDirection);
        },

        /**
         * Switch locale and update direction accordingly
         */
        switchLocale: function(locale) {
            var rtlLocales = ['ar', 'he', 'fa', 'ur'];
            var direction = rtlLocales.indexOf(locale) !== -1 ? 'rtl' : 'ltr';
            
            this.setDirection(direction);
            this.saveLocaleCookie(locale);
        },

        /**
         * Handle locale change event
         */
        onLocaleChange: function(locale) {
            var rtlLocales = ['ar', 'he', 'fa', 'ur'];
            var direction = rtlLocales.indexOf(locale) !== -1 ? 'rtl' : 'ltr';
            
            this.setDirection(direction);
            this.updateIcons();
        },

        /**
         * Update meta viewport tag for RTL
         */
        updateMetaViewport: function(direction) {
            var meta = document.querySelector('meta[name="viewport"]');
            if (meta) {
                var content = meta.getAttribute('content');
                if (direction === 'rtl') {
                    // For RTL, we might want to adjust viewport
                    // Most modern devices handle this automatically
                }
            }
        },

        /**
         * Save direction preference to cookie
         */
        savePreference: function(direction) {
            var expires = new Date();
            expires.setTime(expires.getTime() + (this.config.cookieExpiry * 24 * 60 * 60 * 1000));
            document.cookie = this.config.cookieName + '=' + direction + 
                              ';expires=' + expires.toUTCString() + 
                              ';path=/';
        },

        /**
         * Save locale cookie
         */
        saveLocaleCookie: function(locale) {
            var expires = new Date();
            expires.setTime(expires.getTime() + (365 * 24 * 60 * 60 * 1000));
            document.cookie = 'scriptlog_locale=' + locale + 
                              ';expires=' + expires.toUTCString() + 
                              ';path=/';
        },

        /**
         * Load saved direction preference
         */
        loadPreference: function() {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = cookies[i].trim();
                if (cookie.indexOf(this.config.cookieName) === 0) {
                    return cookie.split('=')[1];
                }
            }
            return null;
        },

        /**
         * Update FontAwesome icons for RTL
         */
        updateIcons: function() {
            if (!this.isRtl()) return;

            var self = this;
            var iconMap = {
                'fa-arrow-left': 'fa-arrow-right',
                'fa-arrow-right': 'fa-arrow-left',
                'fa-chevron-left': 'fa-chevron-right',
                'fa-chevron-right': 'fa-chevron-left',
                'fa-angle-left': 'fa-angle-right',
                'fa-angle-right': 'fa-angle-left',
                'fa-long-arrow-left': 'fa-long-arrow-right',
                'fa-long-arrow-right': 'fa-long-arrow-left'
            };

            Object.keys(iconMap).forEach(function(oldClass) {
                var newClass = iconMap[oldClass];
                var elements = document.querySelectorAll('.' + oldClass);
                elements.forEach(function(el) {
                    el.classList.remove(oldClass);
                    el.classList.add(newClass);
                });
            });
        },

        /**
         * Update form directions
         */
        updateFormDirections: function() {
            var inputs = document.querySelectorAll('input, textarea, select');
            inputs.forEach(function(input) {
                // Don't override explicit direction settings
                if (!input.hasAttribute('dir')) {
                    input.dir = 'auto';
                }
            });
        },

        /**
         * Dispatch custom event
         */
        dispatchEvent: function(eventName, detail) {
            var event;
            if (typeof CustomEvent === 'function') {
                event = new CustomEvent(eventName, { detail: detail });
            } else {
                event = document.createEvent('CustomEvent');
                event.initCustomEvent(eventName, true, true, detail);
            }
            document.dispatchEvent(event);
        }
    };

    /**
     * Initialize when DOM is ready
     */
    function init() {
        // Check for saved preference
        var savedDirection = RTLManager.loadPreference();
        if (savedDirection) {
            RTLManager.setDirection(savedDirection);
        }
        
        // Initialize RTL Manager
        RTLManager.init();
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose globally
    window.RTLManager = RTLManager;

    // Expose helper function
    window.isRtl = function() {
        return RTLManager.isRtl();
    };

    window.toggleDirection = function() {
        RTLManager.toggleDirection();
    };

})();
