/**
 * Additional admin functionality for intelligizedigital_chatassist Chat Widget
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Make both buttons submit the form (event delegation for all tabs)
        $(document).on('click', '#preview-save-changes, #load-preview-button', function(e) {
            e.preventDefault();
            
            // Show loading state on button
            const $button = $(this);
            const originalText = $button.text();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Saving...');
            
            // Submit the form
            $('#intelligizedigital_chatassist-settings-form').submit();
        });
        
        // Allow Enter key to submit the form as well
        $('#intelligizedigital_chatassist_url').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $('#load-preview-button').click();
                return false;
            }
        });
        
        // Set up iframe loading indicator
        $('#zoom-preview-iframe, #zoom-preview-iframe-full').on('load', function() {
            $('#preview-loading-spinner').css('display', 'none');
        });
        
        // Initialize color picker with live preview update
        if ($.fn.wpColorPicker) {
            $('.intelligizedigital-chatassist-color-picker').wpColorPicker({
                change: function(event, ui) {
                    var color = ui.color.toString();
                    
                    // Update all preview elements with the new color
                    $('#preview-widget-header, #preview-widget-header-full').css('background', 'linear-gradient(135deg, ' + color + ' 0%, ' + adjustColorBrightness(color, -20) + ' 100%)');
                    $('#preview-chat-button').css('background', 'linear-gradient(135deg, ' + color + ' 0%, ' + adjustColorBrightness(color, -20) + ' 100%)');
                    $('.intelligizedigital-chatassist-color-preview').css('background-color', color);
                    
                    // Update loading spinner color
                    $('.preview-loading-spinner div').css('border-top-color', color);
                },
                palettes: true
            });
        }
        
        // Helper function to adjust color brightness
        function adjustColorBrightness(hex, percent) {
            const num = parseInt(hex.replace('#', ''), 16);
            const r = Math.min(255, Math.max(0, (num >> 16) + percent));
            const g = Math.min(255, Math.max(0, ((num >> 8) & 0x00FF) + percent));
            const b = Math.min(255, Math.max(0, (num & 0x0000FF) + percent));
            return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
        }
    });

})(jQuery); 