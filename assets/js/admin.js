/**
 * Unstoppable Order Pause - Admin JavaScript
 *
 * @package Unstoppable_Order_Pause
 */

(function($) {
    'use strict';

    var UOPWC_Admin = {
        
        /**
         * Initialize.
         */
        init: function() {
            this.bindEvents();
            this.initModeSelection();
        },

        /**
         * Bind events.
         */
        bindEvents: function() {
            // Toggle pause button
            $(document).on('click', '#uopwc-toggle-pause', this.handleTogglePause);
            
            // Quick pause buttons
            $(document).on('click', '.uopwc-quick-pause', this.handleQuickPause);
            
            // Custom pause form
            $(document).on('submit', '#uopwc-custom-pause-form', this.handleCustomPause);
            
            // Settings form
            $(document).on('submit', '#uopwc-settings-form, #uopwc-schedule-form', this.handleSaveSettings);
            
            // Pause mode selection
            $(document).on('change', 'input[name="pause_mode"]', this.handleModeChange);
            
            // License form
            $(document).on('submit', '#uopwc-license-form', this.handleLicenseActivate);
            $(document).on('click', '#uopwc-deactivate-license', this.handleLicenseDeactivate);
            
            // Check for updates
            $(document).on('click', '#uopwc-check-updates', this.handleCheckUpdates);
        },

        /**
         * Initialize mode selection styling.
         */
        initModeSelection: function() {
            var $selected = $('input[name="pause_mode"]:checked').closest('.uopwc-mode-option');
            $selected.addClass('selected');
        },

        /**
         * Handle pause mode change.
         */
        handleModeChange: function() {
            var mode = $(this).val();
            
            // Update selected styling
            $('.uopwc-mode-option').removeClass('selected');
            $(this).closest('.uopwc-mode-option').addClass('selected');
            
            // Show/hide relevant schedule sections
            if (mode === 'daily') {
                $('.uopwc-daily-schedule').slideDown();
                $('.uopwc-weekly-schedule').slideUp();
            } else if (mode === 'weekly') {
                $('.uopwc-daily-schedule').slideUp();
                $('.uopwc-weekly-schedule').slideDown();
            } else {
                $('.uopwc-daily-schedule').slideUp();
                $('.uopwc-weekly-schedule').slideUp();
            }
        },

        /**
         * Handle toggle pause.
         */
        handleTogglePause: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var originalText = $btn.html();
            
            $btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0;"></span> ' + uopwc_admin.strings.pausing);
            
            $.ajax({
                url: uopwc_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'uopwc_toggle_pause',
                    nonce: uopwc_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || uopwc_admin.strings.error);
                        $btn.prop('disabled', false).html(originalText);
                    }
                },
                error: function() {
                    alert(uopwc_admin.strings.error);
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        },

        /**
         * Handle quick pause.
         */
        handleQuickPause: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var duration = $btn.data('duration');
            
            UOPWC_Admin.doPause(duration, '');
        },

        /**
         * Handle custom pause form.
         */
        handleCustomPause: function(e) {
            e.preventDefault();
            
            var pauseUntil = $('#uopwc-pause-until-date').val();
            var reason = $('#uopwc-pause-reason').val();
            
            if (!pauseUntil) {
                alert('Please select a date and time.');
                return;
            }
            
            UOPWC_Admin.doPause('custom', reason, pauseUntil);
        },

        /**
         * Perform pause action.
         */
        doPause: function(duration, reason, pauseUntil) {
            var $btn = $('.uopwc-quick-pause, #uopwc-custom-pause-form button[type="submit"]');
            $btn.prop('disabled', true);
            
            $.ajax({
                url: uopwc_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'uopwc_quick_pause',
                    nonce: uopwc_admin.nonce,
                    duration: duration,
                    reason: reason || '',
                    pause_until: pauseUntil || ''
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || uopwc_admin.strings.error);
                        $btn.prop('disabled', false);
                    }
                },
                error: function() {
                    alert(uopwc_admin.strings.error);
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Handle save settings.
         */
        handleSaveSettings: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var $status = $form.find('.uopwc-save-status');
            var originalText = $btn.html();
            
            $btn.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0;"></span> ' + uopwc_admin.strings.saving);
            $status.text('').removeClass('success error');
            
            // Serialize form data
            var data = $form.serialize();
            data += '&action=uopwc_save_settings&nonce=' + uopwc_admin.nonce;
            
            $.ajax({
                url: uopwc_admin.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        $status.text(uopwc_admin.strings.saved).addClass('success');
                    } else {
                        $status.text(response.data.message || uopwc_admin.strings.error).addClass('error');
                    }
                    $btn.prop('disabled', false).html(originalText);
                    
                    // Clear status after 3 seconds
                    setTimeout(function() {
                        $status.text('').removeClass('success error');
                    }, 3000);
                },
                error: function() {
                    $status.text(uopwc_admin.strings.error).addClass('error');
                    $btn.prop('disabled', false).html(originalText);
                }
            });
        },

        /**
         * Handle license activation.
         */
        handleLicenseActivate: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var licenseKey = $form.find('#uopwc-license-key').val();
            
            if (!licenseKey) {
                alert('Please enter a license key.');
                return;
            }
            
            var $btn = $form.find('button[type="submit"]');
            $btn.prop('disabled', true).text('Activating...');
            
            $.ajax({
                url: uopwc_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'uopwc_activate_license',
                    nonce: $form.find('#uopwc_license_nonce').val(),
                    license_key: licenseKey
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'License activation failed.');
                        $btn.prop('disabled', false).text('Activate License');
                    }
                },
                error: function() {
                    alert('Connection error. Please try again.');
                    $btn.prop('disabled', false).text('Activate License');
                }
            });
        },

        /**
         * Handle license deactivation.
         */
        handleLicenseDeactivate: function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to deactivate your license?')) {
                return;
            }
            
            var $btn = $(this);
            $btn.prop('disabled', true).text('Deactivating...');
            
            $.ajax({
                url: uopwc_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'uopwc_deactivate_license',
                    nonce: $('#uopwc_license_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'License deactivation failed.');
                        $btn.prop('disabled', false).text('Deactivate License');
                    }
                },
                error: function() {
                    alert('Connection error. Please try again.');
                    $btn.prop('disabled', false).text('Deactivate License');
                }
            });
        },

        /**
         * Handle check for updates.
         */
        handleCheckUpdates: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $status = $('.uopwc-update-status');
            
            $btn.prop('disabled', true);
            $status.html('<span class="spinner is-active" style="float: none;"></span> Checking...');
            
            $.ajax({
                url: uopwc_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'uopwc_check_updates',
                    nonce: uopwc_admin.nonce
                },
                success: function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        if (response.data.update_available) {
                            $status.html('<span style="color: #d63638;">Update available: v' + response.data.version + '</span>');
                        } else {
                            $status.html('<span style="color: #00a32a;">✓ You have the latest version.</span>');
                        }
                    } else {
                        $status.html('<span style="color: #d63638;">Check failed.</span>');
                    }
                },
                error: function() {
                    $btn.prop('disabled', false);
                    $status.html('<span style="color: #d63638;">Connection error.</span>');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        UOPWC_Admin.init();
    });

})(jQuery);
