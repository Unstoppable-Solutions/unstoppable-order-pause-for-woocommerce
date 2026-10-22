/**
 * Unstoppable Order Pause - Admin Bar JavaScript
 *
 * Handles quick pause/resume actions from the WordPress admin bar.
 *
 * @package Unstoppable_Order_Pause
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        var nonce = uopwc_adminbar.nonce;
        var ajaxUrl = uopwc_adminbar.ajax_url;
        var strings = uopwc_adminbar.strings;
        
        // Handle pause actions
        $(document).on('click', '#wp-admin-bar-uopwc-pause-30 .ab-item, #wp-admin-bar-uopwc-pause-60 .ab-item, #wp-admin-bar-uopwc-pause-120 .ab-item, #wp-admin-bar-uopwc-pause-day .ab-item, #wp-admin-bar-uopwc-pause-indefinite .ab-item', function(e) {
            e.preventDefault();
            
            var $item = $(this).closest('li');
            var duration = 'indefinite';
            
            if ($item.attr('id') === 'wp-admin-bar-uopwc-pause-30') duration = '30';
            else if ($item.attr('id') === 'wp-admin-bar-uopwc-pause-60') duration = '60';
            else if ($item.attr('id') === 'wp-admin-bar-uopwc-pause-120') duration = '120';
            else if ($item.attr('id') === 'wp-admin-bar-uopwc-pause-day') duration = 'day';
            
            performAction('pause', duration);
        });
        
        // Handle resume action
        $(document).on('click', '#wp-admin-bar-uopwc-resume-orders .ab-item', function(e) {
            e.preventDefault();
            performAction('resume');
        });
        
        function performAction(action, duration) {
            var $parent = $('#wp-admin-bar-uopwc-order-pause');
            $parent.addClass('uopwc-loading');
            
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'uopwc_admin_bar_toggle',
                    pause_action: action,
                    duration: duration || '',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Reload to update admin bar
                        location.reload();
                    } else {
                        alert(response.data.message || strings.error);
                        $parent.removeClass('uopwc-loading');
                    }
                },
                error: function() {
                    alert(strings.connection_error);
                    $parent.removeClass('uopwc-loading');
                }
            });
        }
    });
})(jQuery);
