=== Intelligize Digital ChatAssist ===
Contributors: intelligize
Donate link: https://intelligizedigital.com/
Tags: chat, widget, support, customer service, live chat
Requires at least: 5.0
Tested up to: 7.0
Stable tag: 4.0.3
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A powerful chat widget for WordPress. Connect n8n workflows or any chat service via webhook URL. Customize appearance and track engagement.

== Description ==

Intelligize Digital ChatAssist is a flexible and feature-rich chat widget plugin that allows you to integrate any chat service into your WordPress website. Whether you're using n8n workflows, custom chat solutions, AI-powered chatbots, or third-party chat services, Intelligize Digital ChatAssist provides a seamless integration experience.

**Key Features:**

* **Universal Compatibility** - Works with n8n workflows, custom chat services, AI chatbots, Intercom, Crisp, Drift, Tawk.to, LiveChat, or any webhook URL
* **Widget Profiles** - Multiple chat widgets with different URLs per page (homepage, shop, product, cart, checkout)
* **Fully Customizable** - Control every aspect of the widget appearance
* **Analytics & Heatmaps** - Track opens, closes, messages; scroll depth and click heatmaps; goal tracking
* **Smart Display Rules** - Show or hide the widget based on page, time, role, device, geo
* **Marketing** - Campaign tracking, coupon codes, newsletter signup modal
* **Mobile/PWA** - Manifest, service worker, add-to-home-screen support
* **Push Subscriptions** - Collect browser push subscriptions for use with your WebPush service
* **Lightweight** - Minimal performance impact on your site
* **Easy Setup** - Get started in minutes with our intuitive interface

**Perfect For:**

* n8n workflow integration
* Customer support teams
* Sales and lead generation
* AI chatbot integration
* Live chat services
* Custom chat solutions
* Multi-language support

This plugin is designed to be simple yet powerful, giving you complete control over how chat appears on your website while maintaining excellent performance and user experience.

= Features =

* **Universal Chat Integration** - Connect to n8n workflows, custom chat services, AI chatbots, Intercom, Crisp, Drift, Tawk.to, LiveChat, or any webhook URL
* **Widget Profiles** - Multiple chat widgets with different URLs per page/post; conditions for homepage, shop, product, cart, checkout
* **Complete Customization**:
  * Choose widget position (left or right)
  * Set custom widget title and branding
  * Customize colors to match your brand
  * Use emoji or upload custom SVG icons
  * Adjust content zoom level (50% - 150%)
* **Analytics & Goals** - Charts for opens/closes/messages, goal tracking, comparison periods, CSV/PDF export
* **Heatmap** - Scroll depth and click tracking with visualization
* **Smart Display Options**:
  * Show/hide on specific pages
  * Time-based, day-based, role-based, device-based, geo-based rules
  * WooCommerce page targeting (shop, product, cart, checkout)
* **Marketing** - Campaign tracking (?campaign=), coupon codes, newsletter signup modal with webhook
* **Integrations** - Zapier/Webhook, Slack, Discord, CRM (HubSpot/Salesforce), A/B testing, conversion tracking
* **Mobile/PWA** - Manifest, service worker, add-to-home-screen, push subscription collection
* **Mobile-First Design** - Responsive and touch-friendly
* **Performance Optimized** - Lazy loading and minimal resource usage
* **Developer Friendly** - Hooks and filters for extensibility
* **Accessibility Ready** - Keyboard navigation and screen reader support

= Requirements =

* WordPress 5.0 or higher
* A chat service with an existing chat workflow (n8n, custom solution, or third-party service)

== Installation ==

1. Upload the `intelligizedigital-chatassist` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Intelligize Digital ChatAssist in the main admin menu to configure

== Configuration ==

1. **Chat URL**: Enter the full URL of your chat webhook (e.g., `https://your-n8n-instance.com/webhook/your-workflow-id` or `https://your-chat-service.com/webhook/your-chat-id/chat`)
2. **Enable Chat Widget**: Toggle to enable or disable the chat widget on your website
3. **Widget Position**: Choose between right or left side positioning
4. **Chat Widget Title**: Set the title that appears in the chat header
5. **Widget Color**: Select a custom color for the widget button and header
6. **Chat Icon**: Choose an emoji or upload an SVG icon for the chat button
7. **Chat Content Zoom**: Adjust the zoom level of the chat content

== External Services ==

This plugin can connect to several external services depending on which optional features the site administrator enables. No external connections are made by default — every service below requires explicit administrator configuration.

= Chat Service (Custom webhook URL — required to use the widget) =
* **What**: The URL you enter in Settings > Chat URL. This is your own service: an n8n workflow, AI chatbot, or any webhook endpoint you control.
* **Data sent**: Visitor chat messages and (optionally) pre-chat form data (name, email, phone) are sent to this URL.
* **When**: Only when a visitor actively sends a message.
* **Controlled by you**: You own and operate this endpoint.

= Slack (optional) =
* **What**: Slack's Incoming Webhooks API. Activated only when a Slack webhook URL is entered in the Integrations tab.
* **Data sent**: Chat event notifications (widget opened, message sent, lead captured).
* **When**: Only on the selected events, only if configured.
* **Privacy policy**: https://slack.com/privacy-policy

= Discord (optional) =
* **What**: Discord's Webhook API. Activated only when a Discord webhook URL is entered in the Integrations tab.
* **Data sent**: Chat event notifications.
* **When**: Only on the selected events, only if configured.
* **Privacy policy**: https://discord.com/privacy

= Zapier / CRM Webhook (optional) =
* **What**: A generic webhook URL (e.g., Zapier, HubSpot, Salesforce). Activated only when a Webhook or CRM URL is entered in the Integrations tab.
* **Data sent**: Lead data (name, email, phone) and chat events.
* **When**: Only on lead capture or chat events, only if configured.
* **Terms**: Governed by the terms of whichever service you point the URL to.

= Intercom (optional integration) =
* **What**: Loads the Intercom chat widget from widget.intercom.io. Activated only when an Intercom App ID is entered and Intercom is selected as the chat service.
* **Data sent**: Standard Intercom widget initialisation data including visitor information.
* **When**: On every frontend page load when this integration is active.
* **Privacy policy**: https://www.intercom.com/legal/privacy

= Crisp (optional integration) =
* **What**: Loads the Crisp chat widget from client.crisp.chat. Activated only when a Crisp Website ID is entered and Crisp is selected as the chat service.
* **Data sent**: Standard Crisp widget initialisation data.
* **When**: On every frontend page load when this integration is active.
* **Privacy policy**: https://crisp.chat/en/privacy/

= Drift (optional integration) =
* **What**: Loads the Drift chat widget from js.driftt.com. Activated only when a Drift ID is entered and Drift is selected as the chat service.
* **Data sent**: Standard Drift widget initialisation data.
* **When**: On every frontend page load when this integration is active.
* **Privacy policy**: https://www.drift.com/privacy-policy/

= Tawk.to (optional integration) =
* **What**: Loads the Tawk.to chat widget from embed.tawk.to. Activated only when a Tawk.to Property ID and Widget ID are entered.
* **Data sent**: Standard Tawk.to widget initialisation data including visitor information.
* **When**: On every frontend page load when this integration is active.
* **Privacy policy**: https://www.tawk.to/privacy-policy/

= LiveChat (optional integration) =
* **What**: Loads the LiveChat widget from cdn.livechatinc.com. Activated only when a LiveChat License ID is entered.
* **Data sent**: Standard LiveChat widget initialisation data including visitor information.
* **When**: On every frontend page load when this integration is active.
* **Privacy policy**: https://www.livechat.com/legal/privacy-policy/

== Frequently Asked Questions ==

= Can I customize the appearance of the chat widget? =

Yes, you can customize the widget title, position, color, and icon. You can also adjust the zoom level to make the content larger or smaller.

= Does this plugin work with any chat workflows? =

Yes, this plugin works with any chat workflow that is publicly accessible via a webhook URL, including n8n workflows, custom chat solutions, AI chatbots, and third-party chat services.

= Does this plugin work with n8n? =

Yes! Intelligize Digital ChatAssist works perfectly with n8n workflows. Simply create a chat workflow in n8n, make it publicly accessible, and use the webhook URL in Intelligize Digital ChatAssist settings.

== Screenshots ==

1. The chat widget on the frontend
2. Admin settings page

== Changelog ==

= 4.0.3 =
* Update bundled Chart.js library from v4.4.1 to v4.5.0 (latest stable)
* Replace home_url('/wp-includes/...') fallback with includes_url() in PWA manifest
* Move inline style="display:none" attributes on the public widget partial to a dedicated CSS class
* Remove inline style="display:none" from the marketing newsletter modal; toggle via a CSS class instead
* Clean up internal docblock reference to removed custom CSS/JS feature
* Improve checkbox visibility on Display Rules tab: Day-of-Week, Device and WooCommerce Pages selectors now use clearly styled pill-shaped checked/unchecked states
* Move 49+ duplicated static inline style attributes from admin screens to reusable utility classes in the admin stylesheet (margin, flex, grid, width, link, help-box, empty-state, chart-wrapper helpers)
* Replace inline style="display:none" toggles on icon-type sections (emoji/SVG) with a CSS class plus matching jQuery class toggling
* Remove inline style attribute from JS-generated SVG preview image; styling now lives in admin CSS

= 4.0.2 =
* Remove Custom CSS and Custom JavaScript fields (WordPress.org guideline compliance)
* Convert all inline script output to wp_add_inline_script() throughout plugin
* Fix admin menu position to 81 (previously too high at 25)
* Fix widget HTML output to use wp_kses_post()
* Fix GDPR consent script to use dedicated script handle instead of attaching to jquery
* Rename all plugin constants to match slug prefix (INTELLIGIZEDIGITAL_CHATASSIST_)
* Update Tested up to: 7.0

= 4.0.1 =
* Remove bundled Composer vendor directory; push subscription collection works without external libraries
* Push notification sending now correctly defers to an external WebPush service
* Update admin mobile tab to describe VAPID key setup clearly

= 4.0.0 =
* Widget profiles - multiple chat widgets with different URLs per page
* Analytics charts, goal tracking, heatmap (scroll depth & clicks)
* Direct integrations: Intercom, Crisp, Drift, Tawk.to, LiveChat
* Marketing: campaign tracking, coupons, newsletter modal
* Mobile/PWA: manifest, service worker, push notifications
* Conversion attribution, A/B variant stats
* Fix: Save button now works in all tabs

= 3.0.0 =
* WPForms integration, Zapier/Webhook, Slack, Discord, CRM webhook
* A/B testing, conversion tracking, heatmap (scroll depth)
* PDF export, comparison periods, pre-chat to URL
* Unread badge, collapsible layout, typing indicator, sound for new messages

= 2.0.0 =
* Display triggers, proactive message, pre-chat form, GDPR consent
* Extended display rules, REST API, shortcode, WooCommerce/CF7 integrations

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 4.0.0 =
Major update: Widget profiles, analytics charts, heatmaps, direct integrations (Intercom, Crisp, Drift, Tawk, LiveChat), marketing tools, push notifications, and Save button fix for all tabs.

= 1.0.0 =
Initial release
