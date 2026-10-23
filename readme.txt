=== Unstoppable Order Pause for WooCommerce ===
Contributors: paramedic192
Tags: woocommerce, pause orders, store closed, holiday mode, restaurant
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Pause WooCommerce orders instantly from the admin bar. Perfect for restaurants, busy shops, or any store that needs to stop accepting orders.

== Description ==

**Unstoppable Order Pause** gives you instant control over your WooCommerce store's order acceptance. Whether you're a restaurant closing for the night, a busy shop needing a quick break, or any business that needs to temporarily pause orders, this plugin makes it simple. Use it as a holiday mode, a "store closed" toggle, or a way to stop accepting orders whenever you need to.

### Quick Access from Admin Bar

Toggle order pausing directly from the WordPress admin bar, under the Options Studio menu. No need to navigate to settings - just click and pause!

### Key Features

* **Instant Pause/Resume** - One-click toggle from the admin bar
* **Quick Duration Options** - Pause for 30 minutes, 1 hour, 2 hours, or rest of day
* **Custom Pause Duration** - Set an exact date and time to resume orders
* **Daily Schedule** - Automatically pause orders outside business hours
* **Weekly Schedule** - Set different hours for each day of the week
* **Customer Notice** - Display a customizable message when orders are paused
* **Countdown Timer** - Show customers when orders will resume
* **Hide Add to Cart** - Optionally hide purchase buttons when paused
* **Checkout Block** - Prevent checkout completion during paused periods

### Perfect For

* **Restaurants & Food Delivery** - Close orders during prep time or after hours
* **Service Businesses** - Stop bookings during busy periods
* **Limited Stock Shops** - Pause when inventory needs restocking
* **Any WooCommerce Store** - Take a break without losing sales information

### Options Studio Integration

This plugin integrates with Unstoppable Solutions' Options Studio, providing a unified admin experience across all our WooCommerce plugins. Access all your Unstoppable plugins from one convenient menu.

== External Services ==

This plugin connects to an external license server to activate and validate optional premium license keys. Core order-pause functionality works fully without a license; this service is contacted only when you choose to use a license key.

**Unstoppable Solutions License Server (hosted on the Base44 platform)**

* **Endpoint contacted:** https://fm-web-solutions-plugin-hub-d05f2182.base44.app/functions (the validateLicense, activateLicense, and deactivateLicense functions).
* **Used for:** Activating, validating, and deactivating a premium license key.
* **When:** Only when a site administrator clicks Activate or Deactivate on the License tab in the plugin settings. The plugin makes no automatic or background requests to this service; viewing the settings page reads a locally cached status and does not contact the server.
* **Data sent:** The license key you enter, your site URL, your site name (on activation only), and the plugin slug.
* **Service provider:** Unstoppable Solutions, via the Base44 hosting platform.
* **Website:** [https://unstoppablesolutions.app](https://unstoppablesolutions.app)
* **Terms of Service:** [https://unstoppablesolutions.app/terms](https://unstoppablesolutions.app/terms)
* **Privacy Policy:** [https://unstoppablesolutions.app/privacy](https://unstoppablesolutions.app/privacy)

== Installation ==

1. Upload the `unstoppable-order-pause` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Options Studio → Order Pause to configure settings
4. Use the quick toggle in the admin bar to pause/resume orders

== Frequently Asked Questions ==

= How do I quickly pause orders? =

After activating the plugin, you'll see the Options Studio menu in your WordPress admin bar at the top of the screen. Click on it and select "Orders: Active" to access quick pause options. You can choose from preset durations or pause indefinitely.

= Will paused orders affect existing orders? =

No, pausing only prevents NEW orders from being placed. Any existing orders in your system will not be affected and can be processed normally.

= Can customers still browse products when orders are paused? =

Yes, customers can still browse your shop and view products. However, depending on your settings, the "Add to Cart" button may be hidden and checkout will be blocked. A notice will inform them that orders are temporarily paused.

= Can I schedule daily closing times? =

Yes! Go to Options Studio → Order Pause → Schedule tab, select "Daily Schedule" mode, and set your opening and closing times. Orders will automatically pause outside these hours.

= Does this work with WooCommerce Subscriptions? =

The pause feature applies to all new orders. Existing subscriptions with scheduled renewals will continue to process normally. If you need to pause subscription renewals, you should manage those directly in WooCommerce Subscriptions settings.

= How do I customize the pause message? =

Go to Options Studio → Order Pause → Settings tab. You can customize the message shown to customers and use the {time} placeholder to show when orders will resume.

== Screenshots ==

1. Dashboard - Quick status overview and pause controls
2. Admin Bar - Quick toggle directly from the toolbar
3. Settings - Customize messages and display options
4. Schedule - Set up daily or weekly operating hours
5. Frontend Notice - What customers see when orders are paused

== Changelog ==

= 1.2.0 =
* New: setting to show or hide the Order Pause toggle in the WordPress admin bar (shown by default)

= 1.1.6 =
* Maintenance release

= 1.1.5 =
* Shortened the readme short description to meet the 150-character limit

= 1.1.4 =
* Clarified the External Services disclosure with the exact license-server endpoint and request details

= 1.1.3 =
* Use literal prefixed names for license option/transient keys

= 1.1.2 =
* Updated text domain and internal prefixes to match the plugin slug for WordPress.org compliance

= 1.1.1 =
* Updated admin display name to "Unstoppable Order Pause for WooCommerce" for consistent branding
* Optimized readme tags and description for search (pause orders, store closed, holiday mode)

= 1.1.0 =
* WordPress.org submission preparation
* Fixed inline styles and scripts for compliance (converted to wp_add_inline_style/wp_enqueue_script)
* Added wp_unslash() before sanitization functions
* Removed deprecated load_plugin_textdomain() call
* Added Cart/Checkout Blocks compatibility declaration
* Updated Plugin URI format

= 1.0.8 =
* Simplified JavaScript detection - back to basics approach
* Uses jQuery click handler instead of capturing phase
* Detects add-to-cart by class names, href, and button text
* More reliable popup display

= 1.0.7 =
* Fixed popup not showing on add to cart buttons
* Simplified and improved button detection logic
* Better balance between catching add-to-cart and excluding close buttons
* Cleaner code structure with separate selector arrays

= 1.0.6 =
* Fixed false positive detection on close/dismiss buttons
* Added smart exclusion for X buttons, close icons, and dismiss buttons
* Checks class names, aria-labels, titles, and text content to identify close buttons
* Prevents popup from triggering when closing drawers, modals, or popups

= 1.0.5 =
* Improved add to cart button detection for food ordering plugins
* Now uses capturing phase event listener to intercept before other handlers
* Added support for Orderable, WooFood, RestroPress, and other food ordering plugins
* Added text-based detection for buttons containing "Add" text
* Better popup close button handling

= 1.0.4 =
* Added popup message when customers click add to cart during pause
* Customers now see a clear "Orders Currently Paused" message instead of nothing happening
* Improved user experience with styled popup notification
* Uses the custom pause message from settings

= 1.0.3 =
* Fixed "Hide Add to Cart" setting to actually hide buttons visually with CSS
* Added comprehensive CSS selectors to hide various add to cart button styles
* Improved compatibility with custom themes and page builders

= 1.0.2 =
* Fixed dashicons not displaying in admin bar dropdown
* Ensured orders default to active state on fresh install
* Improved admin bar icon styling

= 1.0.1 =
* Added WooCommerce HPOS (High-Performance Order Storage) compatibility declaration
* Fixed WooCommerce features compatibility warning

= 1.0.0 =
* Initial release
* Quick pause/resume from admin bar
* Preset duration options (30 min, 1 hour, 2 hours, rest of day)
* Custom pause duration with date/time picker
* Daily schedule mode
* Weekly schedule mode
* Customizable customer notice
* Countdown timer display
* Hide add to cart option
* Block checkout option
* Options Studio integration

== Upgrade Notice ==

= 1.0.0 =
Initial release of Unstoppable Order Pause for WooCommerce.
