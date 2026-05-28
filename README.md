# ChatAssist by Ajay

> A lightweight, privacy-first WordPress chat widget plugin that connects any chat URL (n8n workflows, custom AI bots, third-party providers) to your site — with analytics, A/B testing, WooCommerce integration, multi-widget profiles, and PWA push.

<p>
  <a href="https://wordpress.org/plugins/intelligizedigital-chatassist/"><img alt="WordPress Plugin Version" src="https://img.shields.io/wordpress/plugin/v/intelligizedigital-chatassist?style=flat-square"></a>
  <a href="https://wordpress.org/plugins/intelligizedigital-chatassist/"><img alt="WordPress Plugin Downloads" src="https://img.shields.io/wordpress/plugin/dt/intelligizedigital-chatassist?style=flat-square"></a>
  <img alt="WordPress" src="https://img.shields.io/wordpress/plugin/wp-version/intelligizedigital-chatassist?style=flat-square">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-7.4%2B-blue?style=flat-square">
  <img alt="License" src="https://img.shields.io/badge/license-GPLv2%2B-green?style=flat-square">
</p>

---

## ☕ Support this project

If this plugin saves you time or makes you money, consider tossing the maintainer a coffee. Every tip helps keep updates flowing and the support inbox open.

<p>
  <a href="https://github.com/sponsors/ackm04">
    <img src="https://img.shields.io/badge/Sponsor-%E2%9D%A4-ea4aaa?style=for-the-badge&logo=github-sponsors&logoColor=white" alt="Sponsor on GitHub">
  </a>
  <a href="https://www.buymeacoffee.com/ackm04">
    <img src="https://img.shields.io/badge/Buy_me_a_coffee-FFDD00?style=for-the-badge&logo=buy-me-a-coffee&logoColor=black" alt="Buy Me a Coffee">
  </a>
  <a href="https://ko-fi.com/ackm04">
    <img src="https://img.shields.io/badge/Ko--fi-F16061?style=for-the-badge&logo=ko-fi&logoColor=white" alt="Ko-fi">
  </a>
  <a href="https://paypal.me/officialajayindia">
    <img src="https://img.shields.io/badge/PayPal-00457C?style=for-the-badge&logo=paypal&logoColor=white" alt="PayPal">
  </a>
</p>

Even **$3** for a coffee buys an hour of focused bug-squashing. 🙏

---

## ✨ Features

- **Plug-and-play chat URL** — drop any webhook URL (n8n, custom chat service, AI agent endpoint) and you're live
- **Pre-chat lead form** — capture name / email / phone before chat opens; pass to chat URL
- **Smart display rules** — show on specific pages, devices, days, times, user roles, geo locations
- **WooCommerce-aware** — target Shop / Product / Cart / Checkout / Account pages
- **Built-in analytics** — opens, closes, messages, conversions, daily breakdowns, PDF export
- **A/B testing** — split test variants of your chat experience
- **Multiple widget profiles** — different chat URLs per page (sales widget on `/pricing`, support on `/docs`)
- **Live preview & zoom** — see exactly how your widget looks before saving
- **Goal & conversion tracking** — WooCommerce order events and custom events
- **Marketing tools** — newsletter modal with exit-intent / on-chat-open / post-message timing
- **PWA + push notifications** — installable web-app manifest and Web Push (with external VAPID service)
- **Native integrations** — bootstrap snippets for Intercom, Crisp, Drift, Tawk.to, LiveChat
- **Webhooks** — outbound events to Slack, Discord, CRM (HubSpot/Salesforce formats), generic JSON
- **GDPR consent banner** — built-in consent flow before loading any tracking
- **Accessibility-first** — keyboard navigation, focus rings, screen-reader-friendly markup
- **Fully translatable** — `.pot` file included; loads `.mo` from `/languages`
- **REST API** — read-only analytics endpoints with capability checks

---

## 📦 Installation

### From WordPress.org (recommended)
1. WP Admin → **Plugins → Add New**
2. Search "ChatAssist by Ajay"
3. Install → Activate
4. Navigate to **ChatAssist by Ajay** in the admin menu

### From this repo
```bash
git clone https://github.com/erp-linker/erplinker-chatassist.git intelligizedigital-chatassist
zip -r intelligizedigital-chatassist.zip intelligizedigital-chatassist -x "*.git*" "*.DS_Store" "*/screenshots/*"
# Upload the zip in WP Admin → Plugins → Add New → Upload Plugin
```

---

## 🔧 Quick start

1. Activate the plugin
2. Open **ChatAssist by Ajay** in the WP admin menu
3. Paste your **Chat URL** (any chat webhook — n8n, AI bot, etc.)
4. Pick a **position** (right/left), **color**, and **icon**
5. Save — the widget appears on every front-end page

That's it. Browse the other tabs (Display Rules, Analytics, Marketing, Integrations) to fine-tune behavior.

---

## 🧰 Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- jQuery (bundled with WP core)
- HTTPS recommended (required for PWA, push, and most third-party chat embeds)

---

## 🔐 Security

All admin endpoints are nonce-protected and capability-gated (`manage_ackm_chatassist` capability with `manage_options` fallback). All user input is sanitized on save and escaped on output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`). All inline scripts use `wp_add_inline_script()` with `wp_json_encode()`'d data. SVG icons are rendered as `<img>` only (no inline embedding). Found a security issue? Please email **ackm04@gmail.com** rather than opening a public issue.

---

---

## 📜 Changelog

See [readme.txt](readme.txt) for the full changelog.

---

## 📄 License

This plugin is licensed under the **GNU General Public License v2 or later**.
See [LICENSE](LICENSE) for full text or visit https://www.gnu.org/licenses/gpl-2.0.html.

---

## 💛 Made with love by Ajay

If you ship this on a client site or use it in production, drop me a line at **ackm04@gmail.com** — I love seeing how people use it.

**[☕ Buy me a coffee](https://www.buymeacoffee.com/ackm04)** • **[❤️ Sponsor on GitHub](https://github.com/sponsors/ackm04)** • **[🌟 Star this repo](https://github.com/erp-linker/erplinker-chatassist)**
