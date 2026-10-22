/**
 * Unstoppable Order Pause - Countdown Timer
 *
 * @package Unstoppable_Order_Pause
 */

(function($) {
    'use strict';

    var UOPWC_Countdown = {
        
        /**
         * Initialize countdown timers.
         */
        init: function() {
            var $timers = $('.uopwc-countdown');
            
            if ($timers.length === 0) {
                return;
            }
            
            $timers.each(function() {
                var $timer = $(this);
                var timestamp = $timer.data('timestamp');
                
                if (!timestamp) {
                    timestamp = parseInt(uopwc_countdown.timestamp, 10);
                }
                
                if (timestamp) {
                    UOPWC_Countdown.startCountdown($timer, timestamp);
                }
            });
        },

        /**
         * Start a countdown timer.
         *
         * @param {jQuery} $container The timer container element.
         * @param {number} endTimestamp The target timestamp (Unix timestamp).
         */
        startCountdown: function($container, endTimestamp) {
            var $timerDisplay = $container.find('.uopwc-countdown-timer');
            
            // Initial render
            UOPWC_Countdown.renderTimer($timerDisplay, endTimestamp);
            
            // Update every second
            var interval = setInterval(function() {
                var remaining = UOPWC_Countdown.renderTimer($timerDisplay, endTimestamp);
                
                if (remaining <= 0) {
                    clearInterval(interval);
                    // Optionally reload page when countdown ends
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                }
            }, 1000);
        },

        /**
         * Render the timer display.
         *
         * @param {jQuery} $display The timer display element.
         * @param {number} endTimestamp The target timestamp.
         * @return {number} Seconds remaining.
         */
        renderTimer: function($display, endTimestamp) {
            var now = Math.floor(Date.now() / 1000);
            var remaining = endTimestamp - now;
            
            if (remaining <= 0) {
                $display.html('<span class="uopwc-countdown-ended">' + (uopwc_countdown.strings.ended || 'Resuming...') + '</span>');
                return 0;
            }
            
            var time = UOPWC_Countdown.formatTime(remaining);
            var html = '';
            
            if (time.days > 0) {
                html += UOPWC_Countdown.createUnit(time.days, uopwc_countdown.strings.days || 'days');
            }
            
            html += UOPWC_Countdown.createUnit(time.hours, uopwc_countdown.strings.hours || 'hours');
            html += UOPWC_Countdown.createUnit(time.minutes, uopwc_countdown.strings.minutes || 'min');
            html += UOPWC_Countdown.createUnit(time.seconds, uopwc_countdown.strings.seconds || 'sec');
            
            $display.html(html);
            
            return remaining;
        },

        /**
         * Create a countdown unit HTML.
         *
         * @param {number} value The value.
         * @param {string} label The label.
         * @return {string} HTML string.
         */
        createUnit: function(value, label) {
            return '<span class="uopwc-countdown-unit">' +
                   '<span class="uopwc-countdown-value">' + UOPWC_Countdown.padNumber(value) + '</span>' +
                   '<span class="uopwc-countdown-label-small">' + label + '</span>' +
                   '</span>';
        },

        /**
         * Format seconds into time components.
         *
         * @param {number} seconds Total seconds.
         * @return {object} Object with days, hours, minutes, seconds.
         */
        formatTime: function(seconds) {
            var days = Math.floor(seconds / 86400);
            var hours = Math.floor((seconds % 86400) / 3600);
            var minutes = Math.floor((seconds % 3600) / 60);
            var secs = seconds % 60;
            
            return {
                days: days,
                hours: hours,
                minutes: minutes,
                seconds: secs
            };
        },

        /**
         * Pad a number with leading zero.
         *
         * @param {number} num The number.
         * @return {string} Padded string.
         */
        padNumber: function(num) {
            return num < 10 ? '0' + num : String(num);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        UOPWC_Countdown.init();
    });

})(jQuery);
