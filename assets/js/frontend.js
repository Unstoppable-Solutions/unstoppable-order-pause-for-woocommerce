/**
 * Unstoppable Order Pause - Frontend JavaScript
 *
 * Shows popup message when customers try to add items during pause.
 *
 * @package Unstoppable_Order_Pause
 */

(function($) {
    'use strict';

    if (typeof uopwc_frontend === 'undefined' || !uopwc_frontend.is_paused) {
        return;
    }

    // Simple approach: intercept all clicks and check if it's an add-to-cart type button
    $(document).on('click', function(e) {
        var $target = $(e.target);
        var $button = $target.closest('button, a, [role="button"], input[type="submit"]');
        
        // Skip our own popup
        if ($target.closest('.uopwc-popup-overlay').length > 0) {
            return;
        }
        
        // Check element and button text
        var text = ($target.text() + ' ' + $button.text()).toLowerCase();
        var classes = ($target.attr('class') || '') + ' ' + ($button.attr('class') || '');
        var href = $button.attr('href') || '';
        
        // Is this an add to cart button?
        var isAddToCart = false;
        
        // Check classes
        if (classes.match(/add.to.cart|add_to_cart|addtocart|single_add_to_cart/i)) {
            isAddToCart = true;
        }
        
        // Check href
        if (href.indexOf('add-to-cart') !== -1) {
            isAddToCart = true;
        }
        
        // Check text - but be specific
        if (text.match(/^add\s*$|^add\s+to\s+cart|^add\s+to\s+bag|^add\s+to\s+order|^add\s+\$/i)) {
            isAddToCart = true;
        }
        
        // Check for price button pattern like "$4.00 Add" or "Add $4.00"
        if ($button.length && text.match(/add/i) && text.match(/\$[\d.,]+/)) {
            isAddToCart = true;
        }
        
        if (isAddToCart) {
            e.preventDefault();
            e.stopPropagation();
            showPopup();
            return false;
        }
    });
    
    // Also intercept form submissions
    $(document).on('submit', 'form.cart, form.variations_form', function(e) {
        e.preventDefault();
        e.stopPropagation();
        showPopup();
        return false;
    });
    
    function showPopup() {
        if ($('.uopwc-popup-overlay').length > 0) {
            return;
        }
        
        var html = '<div class="uopwc-popup-overlay">' +
            '<div class="uopwc-popup">' +
                '<div class="uopwc-popup-icon">⏸️</div>' +
                '<h3 class="uopwc-popup-title">' + escapeHtml(uopwc_frontend.popup_title) + '</h3>' +
                '<p class="uopwc-popup-message">' + escapeHtml(uopwc_frontend.message) + '</p>' +
                '<button type="button" class="uopwc-popup-close">' + escapeHtml(uopwc_frontend.close_text) + '</button>' +
            '</div>' +
        '</div>';
        
        $('body').append(html);
        $('body').css('overflow', 'hidden');
        
        // Close handlers
        $('.uopwc-popup-overlay').on('click', function(e) {
            if ($(e.target).hasClass('uopwc-popup-overlay')) {
                closePopup();
            }
        });
        
        $('.uopwc-popup-close').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            closePopup();
        });
    }
    
    function closePopup() {
        $('.uopwc-popup-overlay').fadeOut(200, function() {
            $(this).remove();
        });
        $('body').css('overflow', '');
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Close on escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            closePopup();
        }
    });

})(jQuery);
