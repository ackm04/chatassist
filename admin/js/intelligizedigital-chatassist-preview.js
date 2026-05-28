/**
 * Preview functionality for intelligizedigital_chatassist Chat Widget
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Preview functionality
        var previewButton = $('#load-preview-button');
        var previewContainer = $('#intelligizedigital_chatassist-chat-widget-preview');
        var widgetUrl = $('#intelligizedigital_chatassist_url').val();
        var widgetPosition = $('#intelligizedigital_chatassist_position').val() || 'right';
        var widgetTitle = $('#intelligizedigital_chatassist_title').val() || 'Chat Support';
        var widgetColor = $('#intelligizedigital_chatassist_color').val() || '#667eea';
        var widgetIcon = $('#intelligizedigital_chatassist_icon').val() || '💬';
        var widgetIconType = $('input[name="intelligizedigital_chatassist_icon_type"]:checked').val() || 'emoji';
        var widgetSvgIcon = $('#intelligizedigital_chatassist_svg_icon').val() || '';
        var widgetZoom = $('#intelligizedigital_chatassist_zoom').val() || '100';
        
        // Device preview functionality
        var deviceButtons = $('.intelligizedigital_chatassist-chat-widget-preview-device-button');
        var previewFrame = $('.intelligizedigital_chatassist-chat-widget-preview-frame');
        
        deviceButtons.on('click', function() {
            var device = $(this).data('device');
            deviceButtons.removeClass('active');
            $(this).addClass('active');
            
            previewFrame.removeClass('desktop tablet mobile');
            previewFrame.addClass(device);
        });
        
        // Preview chat button functionality
        var previewChatButton = $('.intelligizedigital_chatassist-chat-widget-preview-button');
        var previewChatPopup = $('.intelligizedigital_chatassist-chat-widget-preview-popup');
        var previewChatClose = $('.intelligizedigital_chatassist-chat-widget-preview-close');
        
        previewChatButton.on('click', function() {
            previewChatPopup.addClass('open');
        });
        
        previewChatClose.on('click', function() {
            previewChatPopup.addClass('closing');
            setTimeout(function() {
                previewChatPopup.removeClass('open closing');
            }, 300);
        });
        
        // Update preview when settings change
        function updatePreview() {
            widgetUrl = $('#intelligizedigital_chatassist_url').val();
            widgetPosition = $('#intelligizedigital_chatassist_position').val() || 'right';
            widgetTitle = $('#intelligizedigital_chatassist_title').val() || 'Chat Support';
            widgetColor = $('#intelligizedigital_chatassist_color').val() || '#667eea';
            widgetIcon = $('#intelligizedigital_chatassist_icon').val() || '💬';
            widgetIconType = $('input[name="intelligizedigital_chatassist_icon_type"]:checked').val() || 'emoji';
            widgetSvgIcon = $('#intelligizedigital_chatassist_svg_icon').val() || '';
            widgetZoom = $('#intelligizedigital_chatassist_zoom').val() || '100';
            
            // Update position
            var previewWidget = $('.intelligizedigital_chatassist-preview-widget');
            previewWidget.removeClass('left right');
            previewWidget.addClass(widgetPosition);
            
            // Update title
            $('#preview-widget-title, #preview-widget-title-full').text(widgetTitle);
            
            // Update color with gradient
            var darkerColor = adjustColorBrightness(widgetColor, -20);
            $('#preview-widget-header, #preview-widget-header-full').css('background', 'linear-gradient(135deg, ' + widgetColor + ' 0%, ' + darkerColor + ' 100%)');
            $('#preview-chat-button').css('background', 'linear-gradient(135deg, ' + widgetColor + ' 0%, ' + darkerColor + ' 100%)');
            
            // Update icon
            if (widgetIconType === 'emoji') {
                $('#preview-button-icon').text(widgetIcon);
            } else {
                if (widgetSvgIcon) {
                    $('#preview-button-icon').html('<img src="' + widgetSvgIcon + '" alt="Chat" style="max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);">');
                } else {
                    $('#preview-button-icon').text('💬');
                }
            }
        }
        
        // Helper function to adjust color brightness
        function adjustColorBrightness(hex, percent) {
            const num = parseInt(hex.replace('#', ''), 16);
            const r = Math.min(255, Math.max(0, (num >> 16) + percent));
            const g = Math.min(255, Math.max(0, ((num >> 8) & 0x00FF) + percent));
            const b = Math.min(255, Math.max(0, (num & 0x0000FF) + percent));
            return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
        }
        
        // Initialize preview
        updatePreview();
        
        // Update preview when settings change
        $('#intelligizedigital_chatassist_title, #intelligizedigital_chatassist_icon').on('input', updatePreview);
        $('#intelligizedigital_chatassist_color').wpColorPicker({
            change: function(event, ui) {
                setTimeout(updatePreview, 100);
            }
        });
        $('#intelligizedigital_chatassist_position, input[name="intelligizedigital_chatassist_icon_type"]').on('change', updatePreview);
        $('#intelligizedigital_chatassist_zoom').on('input', updatePreview);
        
        // Handle emoji selection
        $('.intelligizedigital_chatassist-icon-option').on('click', function() {
            var emoji = $(this).data('emoji');
            $('#intelligizedigital_chatassist_icon').val(emoji);
            $('.intelligizedigital_chatassist-icon-option').removeClass('selected');
            $(this).addClass('selected');
            updatePreview();
        });
        
        // Media uploader for SVG icon
        var mediaUploader;
        $('#upload_svg_button').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Select or Upload SVG Icon',
                button: {
                    text: 'Use this icon'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#intelligizedigital_chatassist_svg_icon').val(attachment.url);
                $('.intelligizedigital_chatassist-svg-preview').html('<img src="' + attachment.url + '" alt="SVG Icon">');
                updatePreview();
            });
            
            mediaUploader.open();
        });
        
        // Load preview button
        previewButton.on('click', function() {
            // Save settings first
            $('#intelligizedigital_chatassist-settings-form').submit();
            
            // Show preview after a short delay
            setTimeout(function() {
                previewContainer.show();
                updatePreview();
            }, 500);
        });
    });

})(jQuery); 