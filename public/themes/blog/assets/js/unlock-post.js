/**
 * AJAX Post Unlock Functionality
 * Handles password-protected post unlock via AJAX
 * 
 * Works with both SEO-friendly URLs and query string URLs:
 * - Uses scriptlog_vars.api_url for API endpoint (always SEO-friendly)
 * - The API routing is independent of blog permalink settings
 * 
 * Security features:
 * - Input sanitization (server-side)
 * - XSS prevention in results
 * - Session-based unlock state
 */
(function($) {
    'use strict';
    
    var PostUnlock = {
        init: function() {
            this.forms = $('.unlock-post-form');
            
            if (this.forms.length === 0) {
                return;
            }
            
            this.bindEvents();
        },
        
        bindEvents: function() {
            var self = this;
            
            this.forms.on('submit', function(e) {
                e.preventDefault();
                self.handleSubmit($(this));
            });
        },
        
        handleSubmit: function($form) {
            var self = this;
            var postId = $form.data('post-id');
            var password = $form.find('.post-password-input').val().trim();
            var $error = $form.siblings('.unlock-post-error');
            var $loading = $form.siblings('.unlock-post-loading');
            var $passwordForm = $form.closest('.password-protected-post');
            var $contentContainer = $('#unlocked-content-' + postId);
            
            if (!password) {
                $error.text('Please enter a password').show();
                return;
            }
            
            $form.find('.unlock-post-btn').prop('disabled', true);
            $error.hide();
            $loading.show();
            
            $.ajax({
                url: scriptlog_vars.api_url + '/posts/' + postId + '/unlock',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ password: password }),
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data && response.data.content) {
                        $passwordForm.fadeOut(300, function() {
                            $contentContainer.html(response.data.content).fadeIn(300);
                        });
                    } else {
                        $error.text(response.message || 'Unable to unlock post').show();
                        self.resetForm($form, $loading);
                    }
                },
                error: function(xhr, status, error) {
                    var message = 'An error occurred. Please try again.';
                    
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            message = response.message;
                        }
                    } catch (e) {
                        if (xhr.status === 401) {
                            message = 'Incorrect password. Please try again.';
                        }
                    }
                    
                    $error.text(message).show();
                    self.resetForm($form, $loading);
                }
            });
        },
        
        resetForm: function($form, $loading) {
            $form.find('.unlock-post-btn').prop('disabled', false);
            $loading.hide();
        }
    };
    
    $(document).ready(function() {
        PostUnlock.init();
    });
    
})(jQuery);
