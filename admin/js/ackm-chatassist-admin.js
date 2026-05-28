/**
 * ChatAssist Chat Widget Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize color pickers
        $('.ackm-chatassist-color-picker').wpColorPicker({
            change: function(event, ui) {
                // Get the color value
                const colorValue = ui.color.toString();
                
                // Update all color-dependent elements in the preview
                updateColorInPreview(colorValue);
            },
            palettes: true
        });

        // Function to update all color elements in the preview
        function updateColorInPreview(colorValue) {
            // Update header background with gradient
            $('#preview-widget-header, #preview-widget-header-full').css('background', 'linear-gradient(135deg, ' + colorValue + ' 0%, ' + adjustColorBrightness(colorValue, -20) + ' 100%)');
            
            // Update loading spinner
            $('.preview-loading-spinner div').css('border-top-color', colorValue);
            
            // Update button color with gradient
            $('#preview-chat-button').css('background', 'linear-gradient(135deg, ' + colorValue + ' 0%, ' + adjustColorBrightness(colorValue, -20) + ' 100%)');
            
            // Update SVG preview background if it exists
            $('.ackm-chatassist-svg-preview').css('background-color', colorValue);
            
            // Update color preview box
            $('.ackm-chatassist-color-preview').css('background-color', colorValue);
        }
        
        // Helper function to adjust color brightness
        function adjustColorBrightness(hex, percent) {
            const num = parseInt(hex.replace('#', ''), 16);
            const r = Math.min(255, Math.max(0, (num >> 16) + percent));
            const g = Math.min(255, Math.max(0, ((num >> 8) & 0x00FF) + percent));
            const b = Math.min(255, Math.max(0, (num & 0x0000FF) + percent));
            return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
        }

        // Handle color input direct changes (for browsers that support color inputs)
        $('#ackm_chatassist_color').on('input', function() {
            updateColorInPreview($(this).val());
        });

        // Handle icon selection
        $('.ackm_chatassist-icon-option').on('click', function() {
            const emoji = $(this).data('emoji');
            $('#ackm_chatassist_icon').val(emoji);
            $('#preview-button-icon').text(emoji);
            $('.ackm_chatassist-icon-option').removeClass('selected');
            $(this).addClass('selected');
        });
        
        // Handle icon type toggle
        $('input[name="ackm_chatassist_icon_type"]').on('change', function() {
            const iconType = $(this).val();
            if (iconType === 'emoji') {
                $('#emoji-icon-section').removeClass('ackm-chatassist-hidden').show();
                $('#svg-icon-section').addClass('ackm-chatassist-hidden').hide();

                // Update the preview button icon to show emoji
                const emoji = $('#ackm_chatassist_icon').val() || '💬';
                $('#preview-button-icon').text(emoji);
            } else {
                $('#emoji-icon-section').addClass('ackm-chatassist-hidden').hide();
                $('#svg-icon-section').removeClass('ackm-chatassist-hidden').show();
                
                // If SVG is already selected, try to show it in the preview
                const svgUrl = $('#ackm_chatassist_svg_icon').val();
                if (svgUrl) {
                    $('#preview-button-icon').html('<img src="' + svgUrl + '" alt="Icon" class="ackm-chatassist-preview-svg">');
                }
            }
        });
        
        // Handle SVG upload
        $('#upload_svg_button').on('click', function(e) {
            e.preventDefault();
            
            // Create a media frame
            const frame = wp.media({
                title: 'Select or Upload SVG Icon',
                button: {
                    text: 'Use this icon'
                },
                multiple: false
            });
            
            // When an image is selected in the media frame...
            frame.on('select', function() {
                // Get media attachment details from the frame state
                const attachment = frame.state().get('selection').first().toJSON();
                
                // Set the value of the input field
                $('#ackm_chatassist_svg_icon').val(attachment.url);
                
                // Update button preview too if svg is selected
                if ($('input[name="ackm_chatassist_icon_type"]:checked').val() === 'svg') {
                    $('#preview-button-icon').html(`<img src="${attachment.url}" alt="Icon" style="max-width: 60%; max-height: 60%; filter: brightness(0) invert(1);">`);
                }
                
                // Update SVG preview
                if ($('.ackm-chatassist-svg-preview').length) {
                    $('.ackm-chatassist-svg-preview').html(`<img src="${attachment.url}" alt="SVG Icon">`);
                } else {
                    const previewHtml = `<div class="ackm-chatassist-svg-preview" style="margin-top: 15px;"><img src="${attachment.url}" alt="SVG Icon"></div>`;
                    $('.ackm_chatassist-svg-upload').append(previewHtml);
                }
                
                $('.ackm_chatassist-svg-upload').addClass('has-image');
            });
            
            // Open the media library frame
            frame.open();
        });
        
        // Handle zoom slider
        function updateZoomPreview(zoomValue) {
            const scale = zoomValue / 100;
            
            // Update iframe preview if it exists
            const $previewIframe = $('#zoom-preview-iframe');
            if ($previewIframe.length) {
                // For scaling, handle differently based on zoom level
                if (scale < 1) {
                    // When zooming out, adjust width/height to ensure content fits
                    $previewIframe.css({
                        'transform': 'scale(' + scale + ')',
                        'transform-origin': 'top left',
                        'width': (100 / scale) + '%',
                        'height': (100 / scale) + '%'
                    });
                } else {
                    // When zooming in or at 100%, keep width/height at 100%
                    $previewIframe.css({
                        'transform': 'scale(' + scale + ')',
                        'transform-origin': 'top left',
                        'width': '100%',
                        'height': '100%'
                    });
                }
            }
        }
        
        // Initialize zoom preview
        updateZoomPreview($('#ackm_chatassist_zoom').val());
        
        // Handle zoom slider changes
        $('#ackm_chatassist_zoom_slider').on('input', function() {
            const zoomValue = $(this).val();
            $('#ackm_chatassist_zoom').val(zoomValue);
            updateZoomPreview(zoomValue);
        });
        
        // Handle zoom input changes
        $('#ackm_chatassist_zoom').on('input', function() {
            let zoomValue = $(this).val();
            
            // Enforce min/max boundaries
            zoomValue = Math.max(50, Math.min(150, zoomValue));
            $(this).val(zoomValue);
            
            $('#ackm_chatassist_zoom_slider').val(zoomValue);
            updateZoomPreview(zoomValue);
        });
        
        // Update the preview title when widget title changes
        $('#ackm_chatassist_title').on('input', function() {
            const title = $(this).val() || 'Chat Support';
            $('#preview-widget-title, #preview-widget-title-full').text(title);
        });
        
        // Handle position changes for the preview text
        $('#ackm_chatassist_position').on('change', function() {
            const position = $(this).val();
            const positionText = ackmChatAssistSettings.positionTemplate.replace('%s', position);
            $('#preview-position-text').text(positionText);
        });
        
        // Handle iframe load error
        $('#zoom-preview-iframe').on('error', function() {
            $(this).parent().html('<div style="padding: 15px; color: #d63638;">Error loading preview. Please check your ChatAssist Chat URL.</div>');
        });

        // Trigger Save when clicking any "Save Changes" button (event delegation for all tabs)
        $(document).on('click', '#preview-save-changes', function(e) {
            e.preventDefault();
            
            // Show loading state for the button
            const $button = $(this);
            const originalText = $button.text();
            $button.prop('disabled', true).css('opacity', '0.7').text('Saving...');
            
            // Submit the form
            $('#ackm_chatassist-settings-form').submit();
            
            // Restore button state after a short delay (visual feedback)
            setTimeout(function() {
                $button.prop('disabled', false).css('opacity', '1').text(originalText);
                
                // Flash success message
                const $successMessage = $('<div>', {
                    class: 'notice notice-success is-dismissible inline',
                    style: 'padding: 10px; margin: 0 0 0 15px; display: inline-block;',
                    html: '<p>Settings saved successfully!</p>'
                });
                
                $button.after($successMessage);
                
                // Auto-remove the message after 3 seconds
                setTimeout(function() {
                    $successMessage.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 3000);
            }, 1000);
        });
        
        // Also handle the form submission to update the preview immediately if URL changed
        $('#ackm_chatassist-settings-form').on('submit', function() {
            // Store the current URL to check if it changed
            const currentUrl = $('#ackm_chatassist_url').val();
            const currentUrlField = $('<input type="hidden" name="previous_url" />').val(currentUrl);
            $(this).append(currentUrlField);
        });
        
    });

})(jQuery); 