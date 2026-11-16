# Roadmap — Unstoppable Order Pause for WooCommerce

Planning document for the free plugin (this repo). The Pro add-on, when it
exists, lives in a separate private repo; items that belong on the Pro side are
marked **(Pro)**.

_As of v1.2.2._

**Engineering priorities** (correctness / reliability work): **P1** correctness &
security, **P2** reliability & compatibility, **P3** features & UX, **P4**
nice-to-have.

**Product priorities** (requested features, below): **Essential → High → Medium →
Future**, as set by the product owner.

> Nothing here is scheduled for release. Releases are cut manually per the
> Release workflow in `CLAUDE.md` / `README.md` and deploy to WordPress.org only
> on a published GitHub Release.

---

## Design principles (decide before building the scheduling features)

These shape most of the requested features and should be settled first, because
today the plugin has a single notion of "paused / not paused."

- **Two independent schedules.** Separate *business hours* from *online ordering
  hours*:
  - **Business hours** — when the physical business is open (for display, status,
    Google Business Profile, etc.).
  - **Online ordering hours** — when WooCommerce actually accepts new orders.
  - They are not the same window. Example (Scoop Dogg): open 11:00 AM, business
    closes 7:00 PM, but online ordering stops at 6:45 PM. The store can be "open"
    while online ordering is closed, and vice versa.
- **Don't block legitimate advance orders.** Ordering-hours / closed-now
  enforcement must apply to *order-for-now*, not to orders scheduled for a future
  pickup/delivery day or time. If a customer schedules pickup for a future day,
  the plugin must allow that even while "ordering now" is paused. (Implies a
  future-order/scheduling concept distinct from the current blanket pause.)
- **Backward compatibility.** The existing manual pause + daily/weekly schedule
  must keep working as-is while this richer model is layered in.

---

## Shipped recently

- **v1.2.2** — restored the WordPress.org directory banner/icon (removed in the
  1.2.1 deploy because `.wordpress-org/` didn't yet contain them). Assets-only.
- **v1.2.1** — fixed the settings-save bug where saving one settings tab reset the
  other tab's options (the admin-bar toggle kept turning off); promoted the
  admin-bar status + quick toggle to a top-level toolbar item; moved "Tested up
  to" out of the plugin header (WordPress.org compliance); added directory
  screenshots.

---

## Near-term — correctness & reliability (P1–P2)

- **P1 — Enforce the pause on the block-based Checkout (Store API).** _(issue #1)_
  Enforcement currently runs through `woocommerce_check_cart_items`,
  `woocommerce_is_purchasable`, and a classic-checkout redirect. A store using the
  block Checkout can bypass the classic validation path. Add Store API–level
  validation (e.g. `woocommerce_store_api_cart_errors` / checkout validation) so a
  paused store genuinely rejects block-checkout orders. **Highest-value fix.**
- **P2 — Reliable timed auto-resume via scheduling.** _(issue #2)_ A timed pause
  (e.g. "Pause 30 Minutes") expires only on page load, so on a low-traffic store
  orders stay paused past the resume time. Move auto-resume onto Action Scheduler
  (bundled with WooCommerce) or WP-Cron, with the page-load check as a fallback.
- **P2 — Countdown for weekly schedule mode.** _(issue #3)_ `get_countdown_data()`
  computes the next open time for `daily` mode only; weekly mode returns none.

### Housekeeping
- **Keep `Tested up to` current** — major.minor only (currently `7.1`). Bump to
  `7.2` when it ships (~Dec 2026) and the plugin is re-checked.
- **`.wordpress-org/` is the single source of truth for listing assets** — the
  deploy mirrors it and deletes anything missing. Always confirm banner + icon +
  all screenshots are present before releasing. (Documented in `CLAUDE.md`.)
- **SVN cross-commit** — earlier, plugin files were committed into another
  plugin's SVN repo. Confirm this plugin's trunk/tags are clean (believed
  resolved; verify and then drop this item).

---

## Product features — requested

Grouped by product priority. Many depend on the **two-schedule** model above.

### Essential
- **Holiday & vacation schedules** — close ordering (and/or business) for a date
  range (e.g. Dec 24–26, or a week off), with a customer-facing message and an
  automatic reopen. Distinct from the weekly recurring schedule.
- **One-time special hours** — override normal hours for a single date (e.g. close
  early the day before a holiday) without disturbing the recurring schedule.
- **Display business hours on the website** — front-end display of hours and
  current open/closed (and ordering open/closed) status. Shortcode + block (see
  the Gutenberg block item). Reads the *business hours* schedule.

### High
- **Recurring annual closures** — holidays that repeat every year (e.g. always
  closed Dec 25 / July 4), set once.
- **Scheduled announcement banners** — time-boxed notices shown on the storefront
  (e.g. "Holiday hours this week"), independent of whether ordering is paused.
  Generalizes the current paused-notice system into scheduled banners.
- **Separate ordering hours from business hours** — the core of the two-schedule
  model (see Design principles). Lets a store stop taking online orders before it
  physically closes.
- **Gutenberg hours/status block** — a block that renders business hours and live
  open/ordering status; pairs with "display business hours on the website."

### Medium
- **Multiple daily opening periods** — split hours within a day (e.g. 11:00–14:00
  and 17:00–21:00). Extends the current single open/close per day.

### Future
- **Google Business Profile integration** — sync business hours / special hours /
  status to the store's Google Business Profile.

---

## Later features & UX (P3–P4)

- **P3 — Admin-bar "pause until next open" shortcut** for scheduled stores, so a
  manual pause aligns to the configured reopen time in one click.
- **P3 — Accessibility pass on the frontend pause popup** — ARIA roles, focus
  trapping, ESC-to-close on the `.uopwc-popup` overlay.
- **P3 — Tighten "Hide Add to Cart" CSS** — the paused-state CSS hides a broad
  selector list (incl. `.quantity`); review for over-hiding now that
  `woocommerce_is_purchasable` blocks server-side.
- **P4 — WP-CLI command** (`wp uopwc pause|resume`) for operators and cron.
- **P4 — Activity Tracker tie-in** — log pause/resume events to the Unstoppable
  Activity Tracker plugin when active (via `uopwc_orders_paused` /
  `uopwc_orders_resumed`).

---

## Pro / Base44 track **(Pro)**

- **Decide the home for licensing / Pro gating.** _(issue #4)_
  `includes/class-uop-license.php` implements activate/validate/deactivate against
  the Base44 endpoint but is **not instantiated** by the bootstrap. Decide whether
  the License tab ships in the free plugin as groundwork or moves entirely to the
  Pro repo.
- **Scaffold the Base44 Pro app** and record its app ID in `CLAUDE.md` (the
  "Base44 Pro mapping" section is a placeholder).
- **Candidate Pro features** — per-product / per-category pause; multiple named
  schedules; holiday calendar import (subscribe to an external calendar feed);
  email/Slack notification on pause; customer "notify me when orders resume"
  capture; advanced future-order / pre-order scheduling.

---

## Ongoing — security & compatibility (P1–P2)

- **Maintain WordPress.org compliance** — no open vulnerabilities, keep
  `Tested up to` / `WC tested up to` current, Plugin Check CI green.
- **Re-verify HPOS + Cart/Checkout Blocks compatibility** against each new
  WooCommerce major (both flags declared in the bootstrap).
- **Keep the AJAX surface tight** — admin-bar and settings endpoints verify a
  nonce and `manage_woocommerce`; preserve that on any new endpoints.
- **Local-timezone `date()` phpcs:ignores** — revisit only if the stored option
  format is migrated; they exist to preserve values written by earlier releases.
