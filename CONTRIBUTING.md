# Contributing to ChatAssist by Ajay

Thanks for thinking about contributing! 🎉

## Quick guidelines

1. **Open an issue first** for anything larger than a typo — saves us both time
2. **One concern per PR** — small, focused diffs get merged faster
3. **Follow WordPress Coding Standards** — run `phpcs --standard=WordPress` before pushing
4. **Test on a clean WP install** with `WP_DEBUG = true` and `WP_DEBUG_LOG = true`
5. **Don't bump the version** in your PR — Ajay handles that on release

## Local setup

```bash
git clone https://github.com/erp-linker/erplinker-chatassist.git
cd erplinker-chatassist

# Symlink into your local WordPress plugins directory
ln -s "$(pwd)" /path/to/wordpress/wp-content/plugins/intelligizedigital-chatassist
```

Activate the plugin in WP Admin and you're ready to hack.

## Code style

- PHP: WordPress Coding Standards (`phpcs --standard=WordPress`)
- JS: standard ES5/ES6 (no transpiler step in this plugin)
- CSS: BEM-ish, prefix everything with `ackm-chatassist-`
- Always escape on output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`)
- Always sanitize on save (`sanitize_text_field`, `sanitize_email`, `esc_url_raw`, etc.)
- Never use inline `<script>` or `<style>` blocks — use `wp_add_inline_script` / `wp_add_inline_style`
- All admin actions require `current_user_can()` + nonce verification

## Security disclosure

Found a security vulnerability? **Do not open a public issue.** Email **intelligizedigital@gmail.com** with details and I'll respond within 48 hours.

## Sponsoring

This project is maintained on personal time. If it helps your business, please consider:

- ☕ [Buy me a coffee](https://www.buymeacoffee.com/ackm04)
- ❤️ [Sponsor on GitHub](https://github.com/sponsors/ackm04)
- 💸 [Tip via Ko-fi](https://ko-fi.com/ackm04)
- 💳 [PayPal](https://paypal.me/officialajayindia)

Even a one-time $3 tip fuels a real coffee, which fuels real bug fixes. 🙏
