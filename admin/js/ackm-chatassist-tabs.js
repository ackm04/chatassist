/**
 * ChatAssist Admin Tabs Functionality
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Tab switching
        $('.ackm-chatassist-tab').on('click', function() {
            const tabId = $(this).data('tab');
            
            // Remove active class from all tabs
            $('.ackm-chatassist-tab').removeClass('active');
            $('.ackm-chatassist-tab-content').removeClass('active');
            
            // Add active class to clicked tab
            $(this).addClass('active');
            $('#tab-' + tabId).addClass('active');
        });

        // Time-based checkbox toggle
        $('input[name="ackm_chatassist_time_based"]').on('change', function() {
            if ($(this).is(':checked')) {
                $('#time-settings').slideDown(300);
            } else {
                $('#time-settings').slideUp(300);
            }
        });

        // Role-based checkbox toggle
        $('input[name="ackm_chatassist_role_based"]').on('change', function() {
            if ($(this).is(':checked')) {
                $('#role-settings').slideDown(300);
            } else {
                $('#role-settings').slideUp(300);
            }
        });

        // Icon type toggle
        $('input[name="ackm_chatassist_icon_type"]').on('change', function() {
            const iconType = $(this).val();
            if (iconType === 'emoji') {
                $('#emoji-icon-section').removeClass('ackm-chatassist-hidden').slideDown(300);
                $('#svg-icon-section').slideUp(300, function() { $(this).addClass('ackm-chatassist-hidden'); });
            } else {
                $('#emoji-icon-section').slideUp(300, function() { $(this).addClass('ackm-chatassist-hidden'); });
                $('#svg-icon-section').removeClass('ackm-chatassist-hidden').slideDown(300);
            }
        });

        // Icon selection
        $('.ackm_chatassist-icon-option').on('click', function() {
            const emoji = $(this).data('emoji');
            $('#ackm_chatassist_icon').val(emoji);
            $('#preview-button-icon').text(emoji);
            $('.ackm_chatassist-icon-option').removeClass('selected');
            $(this).addClass('selected');
        });

        // Color preview update
        $('.ackm-chatassist-color-picker-wrapper').on('change', '.ackm-chatassist-color-picker', function() {
            const color = $(this).val();
            $('.ackm-chatassist-color-preview').css('background-color', color);
        });

        // Update color preview on input
        $('#ackm_chatassist_color').on('input', function() {
            const color = $(this).val();
            $('.ackm-chatassist-color-preview').css('background-color', color);
        });
    });

})(jQuery);
