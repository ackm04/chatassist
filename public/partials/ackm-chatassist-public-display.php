<?php
// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The public-facing view for the chat widget.
 */
$ackm_chatassist_icon_type = ackm_chatassist_get_option('ackm_chatassist_icon_type', 'emoji');
$ackm_chatassist_icon = ackm_chatassist_get_option('ackm_chatassist_icon', '💬');
$ackm_chatassist_svg_icon = ackm_chatassist_get_option('ackm_chatassist_svg_icon', '');
$ackm_chatassist_position = apply_filters('ackm_chatassist_widget_position', ackm_chatassist_get_option('ackm_chatassist_position', 'right'));
$ackm_chatassist_title = apply_filters('ackm_chatassist_widget_title', ackm_chatassist_get_option('ackm_chatassist_title', 'Chat Support'));
$ackm_chatassist_chat_url = ackm_chatassist_get_chat_url();
$ackm_chatassist_pre_chat = ackm_chatassist_get_option('ackm_chatassist_pre_chat_form', 'no') === 'yes';
$ackm_chatassist_pre_chat_fields = array_map('trim', explode(',', ackm_chatassist_get_option('ackm_chatassist_pre_chat_fields', 'name,email')));
$ackm_chatassist_unread_badge = ackm_chatassist_get_option('ackm_chatassist_unread_badge', 'no') === 'yes';
?>
<div id="ackm-chatassist-container" class="ackm-chatassist-container ackm-chatassist-position-<?php echo esc_attr($ackm_chatassist_position); ?>">
    <div id="ackm-chatassist-popup" class="ackm-chatassist-popup">
        <div class="ackm-chatassist-header">
            <div class="ackm-chatassist-title"><?php echo esc_html($ackm_chatassist_title); ?></div>
            <button type="button" id="ackm-chatassist-close" class="ackm-chatassist-close">&times;</button>
        </div>
        <?php if ($ackm_chatassist_pre_chat && !empty($ackm_chatassist_pre_chat_fields)) : ?>
        <div id="ackm-chatassist-prechat-wrapper" class="ackm-chatassist-prechat-wrapper ackm-chatassist-hidden">
            <div id="ackm-chatassist-prechat">
                <form id="ackm-chatassist-prechat-form">
                    <?php if (in_array('name', $ackm_chatassist_pre_chat_fields, true)) : ?>
                    <p><label><?php esc_html_e('Name', 'intelligizedigital-chatassist'); ?></label><input type="text" name="name" required /></p>
                    <?php endif; ?>
                    <?php if (in_array('email', $ackm_chatassist_pre_chat_fields, true)) : ?>
                    <p><label><?php esc_html_e('Email', 'intelligizedigital-chatassist'); ?></label><input type="email" name="email" required /></p>
                    <?php endif; ?>
                    <?php if (in_array('phone', $ackm_chatassist_pre_chat_fields, true)) : ?>
                    <p><label><?php esc_html_e('Phone', 'intelligizedigital-chatassist'); ?></label><input type="tel" name="phone" /></p>
                    <?php endif; ?>
                    <p><button type="button" id="ackm-chatassist-prechat-submit" class="ackm-chatassist-prechat-submit"><?php esc_html_e('Start Chat', 'intelligizedigital-chatassist'); ?></button></p>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <div class="ackm-chatassist-frame-container<?php echo ($ackm_chatassist_pre_chat && !empty($ackm_chatassist_pre_chat_fields)) ? ' ackm-chatassist-hidden' : ''; ?>">
            <div id="ackm-chatassist-loading" class="ackm-chatassist-loading"></div>
            <iframe id="ackm-chatassist-iframe" data-src="<?php echo esc_url($ackm_chatassist_chat_url); ?>" frameborder="0"></iframe>
        </div>
    </div>
    <button type="button" id="ackm-chatassist-button" class="ackm-chatassist-button">
        <?php if ($ackm_chatassist_unread_badge) : ?><span class="ackm-chatassist-unread-badge ackm-chatassist-hidden">0</span><?php endif; ?>
        <?php if ($ackm_chatassist_icon_type === 'emoji'): ?>
            <span class="ackm-chatassist-icon"><?php echo esc_html($ackm_chatassist_icon); ?></span>
        <?php else: ?>
            <?php if (!empty($ackm_chatassist_svg_icon)): ?>
                <span class="ackm-chatassist-svg-icon">
                    <?php 
                    $ackm_chatassist_attachment_id = attachment_url_to_postid($ackm_chatassist_svg_icon);
                    if ($ackm_chatassist_attachment_id) {
                        echo wp_get_attachment_image($ackm_chatassist_attachment_id, array(24, 24), false, array(
                            'alt' => esc_attr__('Chat', 'intelligizedigital-chatassist'),
                            'class' => 'svg-icon'
                        ));
                    } else {
                        echo wp_kses_post(ackm_chatassist_display_svg($ackm_chatassist_svg_icon, array(24, 24), array(
                            'alt' => esc_attr__('Chat', 'intelligizedigital-chatassist'),
                            'class' => 'svg-icon'
                        )));
                    }
                    ?>
                </span>
            <?php else: ?>
                <span class="ackm-chatassist-icon">💬</span>
            <?php endif; ?>
        <?php endif; ?>
    </button>
</div>
