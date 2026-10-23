# Changelog

All notable user-facing changes are documented here. For WordPress.org users,
the same changelog lives in `readme.txt` and renders on the plugin's directory
page. This file follows the format described at https://keepachangelog.com.

Release dates for versions shipped before this file was introduced are not
recorded individually; they correspond to the WordPress.org release history for
this plugin.

## [Unreleased]

_No user-facing changes yet._

## [1.2.0]

### Added

- Setting to show or hide the Order Pause toggle in the WordPress admin bar (shown by default).

## [1.1.6]

- Maintenance release.

## [1.1.5]

### Changed

- Shortened the readme short description to meet the 150-character limit.

## [1.1.4]

### Changed

- Clarified the External Services disclosure with the exact license-server endpoint and request details.

## [1.1.3]

### Changed

- Use literal prefixed names for license option/transient keys.

## [1.1.2]

### Changed

- Updated text domain and internal prefixes to match the plugin slug for WordPress.org compliance.

## [1.1.1]

### Changed

- Updated admin display name to "Unstoppable Order Pause for WooCommerce" for consistent branding.
- Optimized readme tags and description for search (pause orders, store closed, holiday mode).

## [1.1.0]

### Added

- Cart/Checkout Blocks compatibility declaration.

### Changed

- WordPress.org submission preparation.
- Converted inline styles and scripts to `wp_add_inline_style()` / `wp_enqueue_script()` for compliance.
- Added `wp_unslash()` before sanitization functions.
- Updated Plugin URI format.

### Removed

- Deprecated `load_plugin_textdomain()` call (WordPress.org handles loading).

## [1.0.8]

### Changed

- Simplified add-to-cart detection to a jQuery click handler matching by class names, href, and button text.

## [1.0.7]

### Fixed

- Add-to-cart popup not showing on some buttons; improved button-detection logic and code structure.

## [1.0.6]

### Fixed

- False-positive detection on close/dismiss buttons; added smart exclusion for X/close/dismiss controls so the popup no longer triggers when closing drawers, modals, or popups.

## [1.0.5]

### Changed

- Improved add-to-cart detection for food-ordering plugins (Orderable, WooFood, RestroPress and others) using a capturing-phase listener and text-based detection.

## [1.0.4]

### Added

- Styled popup message shown when customers click add-to-cart during a pause, using the custom pause message.

## [1.0.3]

### Fixed

- "Hide Add to Cart" now visually hides buttons with CSS; broadened selectors for themes and page builders.

## [1.0.2]

### Fixed

- Dashicons not displaying in the admin-bar dropdown.
- Ensured orders default to active on fresh install.
- Improved admin-bar icon styling.

## [1.0.1]

### Added

- WooCommerce HPOS (High-Performance Order Storage) compatibility declaration.

### Fixed

- WooCommerce features compatibility warning.

## [1.0.0]

### Added

- Initial release.
- Quick pause/resume from the admin bar.
- Preset durations (30 min, 1 hour, 2 hours, rest of day) and custom date/time.
- Daily and weekly schedule modes.
- Customizable customer notice and countdown timer.
- Hide add-to-cart and block-checkout options.
- Options Studio integration.
