/**
 * AJAX Search Autocomplete
 * Sidebar live suggestions + permalink-aware full-page fallback on submit.
 *
 * Security:
 * - GET-only idempotent requests (no CSRF needed); honeypot lives in the
 *   plain-HTML form fallback, never in AJAX params.
 * - All server data rendered through escapeHtml(); reflected keyword and
 *   counts are escaped BEFORE interpolation into HTML (reflected-XSS guard).
 * - Query capped at 100 chars, trimmed; `q`/`type` only, no extra params.
 *
 * Performance:
 * - 300ms debounce, abort of in-flight XHR, monotonically increasing
 *   sequence token drops stale out-of-order responses.
 * - 5-minute in-memory cache (max 50 keys) dedupes repeat keystrokes.
 *
 * Accessibility:
 * - Input is role=combobox (see sidebar.php); dropdown is role=listbox,
 *   items role=option with aria-selected roving highlight.
 * - ArrowUp/ArrowDown move, Enter opens the highlighted hit (or submits
 *   the full-page form), Escape dismisses.
 */
(function($) {
    'use strict';

    var SearchWidget = {
        timer: null,
        xhr: null,
        seq: 0,
        minLength: 2,
        maxLength: 100,
        delay: 300,
        cacheTtl: 5 * 60 * 1000,
        cacheSize: 50,
        cache: {},
        cacheOrder: [],
        activeIndex: -1,

        init: function() {
            this.form = $('#ajax-search-form');
            this.input = $('#search-keyword');
            this.results = $('#search-results');
            this.error = $('#search-error');
            this.clear = $('#search-clear');
            this.i18n = (window.scriptlog_vars && window.scriptlog_vars.search) || {};

            if (this.form.length === 0 || this.input.length === 0) {
                return;
            }

            this.bindEvents();
        },

        bindEvents: function() {
            var self = this;

            this.input.on('input', function() {
                clearTimeout(self.timer);
                var keyword = $(this).val().trim();

                if (keyword.length > 0) {
                    self.showClear();
                } else {
                    self.hideClear();
                }

                if (keyword.length < self.minLength) {
                    self.abortPending();
                    self.hideResults();
                    self.hideError();
                    return;
                }

                self.timer = setTimeout(function() {
                    self.performSearch(keyword.substring(0, self.maxLength));
                }, self.delay);
            });

            this.input.on('keydown', function(e) {
                if (e.key === 'Escape' || e.keyCode === 27) {
                    self.abortPending();
                    clearTimeout(self.timer);
                    self.hideResults();
                    self.hideError();
                    return;
                }

                if (!self.results.hasClass('show')) {
                    return;
                }

                if (e.key === 'ArrowDown' || e.keyCode === 40) {
                    e.preventDefault();
                    self.moveActive(1);
                } else if (e.key === 'ArrowUp' || e.keyCode === 38) {
                    e.preventDefault();
                    self.moveActive(-1);
                } else if (e.key === 'Enter' || e.keyCode === 13) {
                    var active = self.getActiveLink();
                    if (active.length) {
                        e.preventDefault();
                        window.location.href = active.attr('href');
                    }
                    // No active hit: let the submit handler navigate to
                    // the full-page results (do NOT preventDefault here).
                }
            });

            // Typing = live suggestions; explicit submit (Enter with no
            // highlighted hit, or the submit button) = full-page GET so
            // the permalink-aware form action keeps working without JS
            // and with JS alike.
            this.form.on('submit', function() {
                var keyword = self.input.val().trim().substring(0, self.maxLength);

                if (keyword.length < self.minLength) {
                    return false;
                }

                self.abortPending();
                clearTimeout(self.timer);
                self.navigateToFullPage(keyword);
                return false;
            });

            if (this.clear.length) {
                this.clear.on('click', function() {
                    self.abortPending();
                    clearTimeout(self.timer);
                    self.input.val('').trigger('focus');
                    self.hideResults();
                    self.hideError();
                    self.hideClear();
                    self.input.attr('aria-expanded', 'false');
                });
            }

            $(document).on('click', function(e) {
                if (!$(e.target).closest('.widget.search').length) {
                    self.hideResults();
                }
            });

            $(document).on('focusin', function(e) {
                if (!$(e.target).closest('.widget.search').length) {
                    self.hideResults();
                }
            });
        },

        getVars: function() {
            return window.scriptlog_vars || {};
        },

        buildSearchUrl: function(keyword) {
            var vars = this.getVars();
            var base = vars.search_url || '';
            if (!base && vars.site_url) {
                base = vars.site_url + '/search';
            }
            if (!base) {
                var action = this.form.attr('action');
                base = action ? action : '/search';
            }
            var sep = base.indexOf('?') === -1 ? '?q=' : '&q=';
            return base + sep + encodeURIComponent(keyword);
        },

        navigateToFullPage: function(keyword) {
            window.location.href = this.buildSearchUrl(keyword);
        },

        readCache: function(keyword) {
            var entry = this.cache[keyword];
            if (!entry) {
                return null;
            }
            if (new Date().getTime() - entry.at > this.cacheTtl) {
                delete this.cache[keyword];
                return null;
            }
            return entry.data;
        },

        writeCache: function(keyword, data) {
            if (!this.cache[keyword]) {
                this.cacheOrder.push(keyword);
                while (this.cacheOrder.length > this.cacheSize) {
                    var oldest = this.cacheOrder.shift();
                    delete this.cache[oldest];
                }
            }
            this.cache[keyword] = { at: new Date().getTime(), data: data };
        },

        abortPending: function() {
            if (this.xhr !== null) {
                try {
                    this.xhr.abort();
                } catch (abortErr) {
                    // Aborting a finished request throws in old jQuery; safe to ignore.
                }
                this.xhr = null;
            }
            this.seq++;
        },

        performSearch: function(keyword) {
            var self = this;
            var cached = this.readCache(keyword);

            if (cached !== null) {
                this.displayResults(cached);
                return;
            }

            var vars = this.getVars();
            if (!vars.api_url) {
                this.showError(this.i18n.error || 'Search is temporarily unavailable.');
                return;
            }

            this.abortPending();
            var mySeq = ++this.seq;
            this.showLoading();

            this.xhr = $.ajax({
                url: vars.api_url + '/search',
                type: 'GET',
                data: {
                    q: keyword,
                    type: 'all'
                },
                dataType: 'json',
                success: function(response) {
                    self.xhr = null;
                    if (mySeq !== self.seq) {
                        return;
                    }
                    if (response && response.success) {
                        self.writeCache(keyword, response.data);
                        self.displayResults(response.data);
                    } else {
                        var msg = (response && (response.message || (response.error && response.error.message))) || self.i18n.error || 'Search failed';
                        self.showError(msg);
                    }
                },
                error: function(jqXHR, textStatus) {
                    self.xhr = null;
                    if (textStatus === 'abort' || mySeq !== self.seq) {
                        return;
                    }
                    if (jqXHR && jqXHR.status === 429) {
                        self.showError(self.i18n.rate_limited || self.i18n.error || 'Too many requests. Please wait a moment.');
                    } else if (jqXHR && (jqXHR.status === 400 || jqXHR.status === 403)) {
                        self.showError(self.i18n.error || 'Search failed');
                    } else {
                        self.showError(self.i18n.error || 'An error occurred during search. Please try again later.');
                    }
                }
            });
        },

        displayResults: function(data) {
            this.hideError();
            data = data || {};

            var total = this.resolveTotal(data);
            var rawKeyword = data.keyword || this.input.val().trim().substring(0, this.maxLength);
            // Escape BEFORE interpolation: the API echoes the user query.
            var keyword = this.escapeHtml(rawKeyword);
            var safeTotal = this.escapeHtml(String(total));
            this.activeIndex = -1;

            if (!data.results || data.results.length === 0) {
                this.results.html(
                    '<div class="search-no-results" role="option" aria-selected="false">' +
                    this.format(this.i18n.no_results || 'No results found for "%keyword%"', { keyword: keyword }) +
                    '</div>'
                ).addClass('show');
                this.input.attr('aria-expanded', 'true');
                return;
            }

            var countHtml = '<div class="search-result-count" aria-hidden="true">' +
                this.format(this.i18n.count || 'Found %count% result(s)', { count: safeTotal }) +
                '</div>';

            var html = countHtml;
            var self = this;

            $.each(data.results.slice(0, 10), function(index, item) {
                var typeLabel = item.type === 'page'
                    ? (self.i18n.type_page || 'Page')
                    : (self.i18n.type_post || 'Post');
                var dateHtml = item.date
                    ? '<time class="search-result-date">' + self.escapeHtml(self.formatDate(item.date)) + '</time>'
                    : '';

                html +=
                    '<div class="search-result-item" role="option" aria-selected="false" data-index="' + index + '">' +
                    '<a href="' + self.escapeHtml(item.url) + '" tabindex="-1">' +
                    '<div class="search-result-title">' + self.escapeHtml(item.title) + '</div>' +
                    (item.excerpt ? '<div class="search-result-excerpt">' + self.escapeHtml(item.excerpt) + '</div>' : '') +
                    '<div class="search-result-meta">' +
                    '<span class="search-result-type">' + self.escapeHtml(typeLabel) + '</span>' +
                    dateHtml +
                    '</div>' +
                    '</a>' +
                    '</div>';
            });

            if (total > 10) {
                html += '<div class="search-result-more">' +
                        '<a href="' + self.escapeHtml(self.buildSearchUrl(rawKeyword)) + '">' +
                        this.format(this.i18n.viewAll || 'View all %count% results', { count: safeTotal }) +
                        '</a>' +
                        '</div>';
            }

            this.results.html(html).addClass('show');
            this.input.attr('aria-expanded', 'true');
        },

        moveActive: function(step) {
            var items = this.results.find('.search-result-item');
            if (!items.length) {
                return;
            }
            this.activeIndex += step;
            if (this.activeIndex < 0) {
                this.activeIndex = items.length - 1;
            } else if (this.activeIndex >= items.length) {
                this.activeIndex = 0;
            }
            items.attr('aria-selected', 'false').removeClass('active');
            var current = items.eq(this.activeIndex);
            current.attr('aria-selected', 'true').addClass('active');
            var link = current.find('a').first();
            if (link.length) {
                link.focus();
                this.input.attr('aria-activedescendant', '');
            }
        },

        getActiveLink: function() {
            var current = this.results.find('.search-result-item.active a').first();
            if (current.length) {
                return current;
            }
            return this.results.find('.search-result-item[aria-selected="true"] a').first();
        },

        resolveTotal: function(data) {
            if (typeof data.total === 'number' && !isNaN(data.total)) {
                return data.total;
            }
            if (data.pagination && typeof data.pagination.total_items === 'number') {
                return data.pagination.total_items;
            }
            if (data.results && data.results.length) {
                return data.results.length;
            }
            return 0;
        },

        format: function(template, values) {
            return String(template).replace(/%([^%]+)%/g, function(match, key) {
                return Object.prototype.hasOwnProperty.call(values, key) ? String(values[key]) : match;
            });
        },

        formatDate: function(date) {
            if (!date) return '';
            var parts = String(date).split(' ');
            return parts.length ? parts[0] : date;
        },

        showLoading: function() {
            this.results.html('<div class="search-loading" role="status"><i class="fa fa-spinner fa-spin" aria-hidden="true"></i> ' +
                this.escapeHtml(this.i18n.loading || 'Searching...') +
                '</div>').addClass('show');
            this.input.attr('aria-expanded', 'true');
        },

        showError: function(message) {
            this.hideResults();
            this.error.text(message).addClass('show');
        },

        hideError: function() {
            this.error.removeClass('show').text('');
        },

        hideResults: function() {
            this.results.removeClass('show');
            this.input.attr('aria-expanded', 'false');
            this.activeIndex = -1;
        },

        showClear: function() {
            if (this.clear.length) {
                this.clear.removeAttr('hidden');
            }
        },

        hideClear: function() {
            if (this.clear.length) {
                this.clear.attr('hidden', 'hidden');
            }
        },

        escapeHtml: function(text) {
            if (text === null || text === undefined) return '';
            var div = document.createElement('div');
            div.textContent = String(text);
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        SearchWidget.init();
    });

})(jQuery);
