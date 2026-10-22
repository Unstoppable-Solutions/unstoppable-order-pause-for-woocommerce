# Unstoppable Order Pause for WooCommerce

[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/unstoppable-order-pause-for-woocommerce.svg)](https://wordpress.org/plugins/unstoppable-order-pause-for-woocommerce/)
[![WordPress Plugin Downloads](https://img.shields.io/wordpress/plugin/dt/unstoppable-order-pause-for-woocommerce.svg)](https://wordpress.org/plugins/unstoppable-order-pause-for-woocommerce/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2+-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)

One-click order pause from the admin toolbar. Hit the button when the kitchen's slammed, when you're closed for the holiday, or when stock issues mean you shouldn't be taking new orders — and un-pause the moment you're ready.

> **End users:** install from the WordPress Plugin Directory — [wordpress.org/plugins/unstoppable-order-pause-for-woocommerce](https://wordpress.org/plugins/unstoppable-order-pause-for-woocommerce/)

## Features

- **Admin-bar toggle** — pause and resume from anywhere in your dashboard, no settings page required
- **Custom paused message** — tell customers why orders are paused (holiday, closed, prep time)
- **Scheduled pause windows** — set a start / end time, auto-resume
- **Cart + checkout blocking** — "Add to Cart" and checkout both honor the pause
- **Role-aware** — admins can still test the store while customers see the pause message
- **Restaurant-friendly** — the use case this was built around; works for any store that needs a stop button

## Development

### Release workflow

Releases are deployed to WordPress.org automatically by GitHub Actions:

1. Finish the feature work on `main`
2. Bump the version in `unstoppable-order-pause-for-woocommerce.php` (header + any constant), `readme.txt` (Stable tag), and add a Changelog entry
3. Commit and push `main`
4. On GitHub, create a new **Release** tagged `vX.Y.Z` — the deploy workflow fires and pushes to WordPress.org SVN

### CI

Every push and pull request runs the official WordPress Plugin Check against the codebase.

## License

GPL-2.0-or-later. See the `License` header in `unstoppable-order-pause-for-woocommerce.php`.

## Author

Developed and maintained by [Unstoppable Solutions](https://unstoppablesolutions.app).
