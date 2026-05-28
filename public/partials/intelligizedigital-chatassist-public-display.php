<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The public-facing view for the chat widget.
 */
$intelligizedigital_chatassist_icon_type = intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_icon_type', 'emoji');
$intelligizedigital_chatassist_icon = intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_icon', '💬');
$intelligizedigital_chatassist_svg_icon = intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_svg_icon', '');
$intelligizedigital_chatassist_position = apply_filters('intelligizedigital_chatassist_widget_position', intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_position', 'right'));
$intelligizedigital_chatassist_title = apply_filters('intelligizedigital_chatassist_widget_title', intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_title', 'Chat Support'));
$intelligizedigital_chatassist_chat_url = intelligizedigital_chatassist_get_chat_url();
$intelligizedigital_chatassist_pre_chat = intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_pre_chat_form', 'no') === 'yes';
$intelligizedigital_chatassist_pre_chat_fields = array_map('trim', explode(',', intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_pre_chat_fields', 'name,email')));
$intelligizedigital_chatassist_unread_badge = intelligizedigital_chatassist_get_option('intelligizedigital_chatassist_unread_badge', 'no') === 'yes';
?>
<div id="intelligizedigital-chatassist-container" class="intelligizedigital-chatassist-container intelligizedigital-chatassist-position-<?php echo esc_attr($intelligizedigital_chatassist_position); ?>">
    <div id="intelligizedigital-chatassist-popup" class="intelligizedigital-chatassist-popup">
        <div class="intelligizedigital-chatassist-header">
            <div class="intelligizedigital-chatassist-title"><?php echo esc_html($intelligizedigital_chatassist_title); ?></div>
            <button type="button" id="intelligizedigital-chatassist-close" class="intelligizedigital-chatassist-close">&times;</button>
        </div>
        <?php if ($intelligizedigital_chatassist_pre_chat && !empty($intelligizedigital_chatassist_pre_chat_fields)) : ?>
        <div id="intelligizedigital-chatassist-prechat-wrapper" class="intelligizedigital-chatassist-prechat-wrapper intelligizedigital-chatassist-hidden">
            <div id="intelligizedigital-chatassist-prechat">
                <form id="intelligizedigital-chatassist-prechat-form">
                    <?php if (in_array('name', $intelligizedigital_chatassist_pre_chat_fields, true)) : ?>
                    <p><label><?php esc_html_e('Name', 'intelligizedigital-chatassist'); ?></label><input type="text" name="name" required /></p>
                    <?php endif; ?>
                    <?php if (in_array('email', $intelligizedigital_chatassist_pre_chat_fields, true)) : ?>
                    <p><label><?php esc_html_e('Email', 'intelligizedigital-chatassist'); ?></label><input type="email" name="email" required /></p>
                    <?php endif; ?>
                    <?php if (in_array('phone', $intelligizedigital_chatassist_pre_chat_fields, true)) : ?>
                    <p><label><?php esc_html_e('Phone', 'intelligizedigital-chatassist'); ?></label><input type="tel" name="phone" /></p>
                    <?php endif; ?>
                    <p><button type="button" id="intelligizedigital-chatassist-prechat-submit" class="intelligizedigital-chatassist-prechat-submit"><?php esc_html_e('Start Chat', 'intelligizedigital-chatassist'); ?></button></p>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <div class="intelligizedigital-chatassist-frame-container<?php echo ($intelligizedigital_chatassist_pre_chat && !empty($intelligizedigital_chatassist_pre_chat_fields)) ? ' intelligizedigital-chatassist-hidden' : ''; ?>">
            <div id="intelligizedigital-chatassist-loading" class="intelligizedigital-chatassist-loading"></div>
            <iframe id="intelligizedigital-chatassist-iframe" data-src="<?php echo esc_url($intelligizedigital_chatassist_chat_url); ?>" frameborder="0"></iframe>
        </div>
    </div>
    <button type="button" id="intelligizedigital-chatassist-button" class="intelligizedigital-chatassist-button">
        <?php if ($intelligizedigital_chatassist_unread_badge) : ?><span class="intelligizedigital-chatassist-unread-badge intelligizedigital-chatassist-hidden">0</span><?php endif; ?>
        <?php if ($intelligizedigital_chatassist_icon_type === 'emoji'): ?>
            <span class="intelligizedigital-chatassist-icon"><?php echo esc_html($intelligizedigital_chatassist_icon); ?></span>
        <?php else: ?>
            <?php if (!empty($intelligizedigital_chatassist_svg_icon)): ?>
                <span class="intelligizedigital-chatassist-svg-icon">
                    <?php 
                    $intelligizedigital_chatassist_attachment_id = attachment_url_to_postid($intelligizedigital_chatassist_svg_icon);
                    if ($intelligizedigital_chatassist_attachment_id) {
                        echo wp_get_attachment_image($intelligizedigital_chatassist_attachment_id, array(24, 24), false, array(
                            'alt' => esc_attr__('Chat', 'intelligizedigital-chatassist'),
                            'class' => 'svg-icon'
                        ));
                    } else {
                        echo wp_kses_post(intelligizedigital_chatassist_display_svg($intelligizedigital_chatassist_svg_icon, array(24, 24), array(
                            'alt' => esc_attr__('Chat', 'intelligizedigital-chatassist'),
                            'class' => 'svg-icon'
                        )));
                    }
                    ?>
                </span>
            <?php else: ?>
                <span class="intelligizedigital-chatassist-icon">💬</span>
            <?php endif; ?>
        <?php endif; ?>
    </button>
</div>
