# Roadmap — Unstoppable Order Pause for WooCommerce

Planning document for the free plugin (this repo). The Pro add-on, when it
exists, lives in a separate private repo; items that belong on the Pro side are
marked **(Pro)**.

Each item is tagged:

- **[Discussed]** — raised in prior project work or notes.
- **[New]** — surfaced from a code review of the current codebase (v1.2.0).

Priorities: **P1** correctness/security, **P2** reliability & compatibility,
**P3** features & UX, **P4** nice-to-have.

> Nothing here is scheduled for release. Releases are cut manually per the
> Release workflow in `CLAUDE.md` / `README.md` and deploy to WordPress.org only
> on a published GitHub Release.

---

## Now — housekeeping

- **[Discussed] Resolve the accidental SVN cross-commit.** Plugin files were
  once committed into a different plugin's WordPress.org SVN repository. Confirm
  this plugin's SVN trunk/tags are clean and that no stray paths remain in the
  wrong repo. (Ops task, not a code change.)
- **[New] Keep `Tested up to` current.** Now at 7.1.3. Bump as WordPress ships
  each maintenance/major release and the plugin is re-checked.
- **[New] `CHANGELOG.md` now mirrors the real history** (done this cycle; keep it
  in sync with `readme.txt` on every release).

---

## Milestone v1.3.x — reliability & block checkout (P1–P2)

- **[New] P1 — Enforce the pause on the block-based Checkout (Store API).** _(tracked in #1)_
  Current enforcement runs through `woocommerce_check_cart_items`,
  `woocommerce_is_purchasable`, and a classic-checkout redirect. The Cart/Checkout
  Blocks compatibility flag is declared, but a store using the block Checkout can
  bypass the classic validation path. Add Store API–level validation (e.g.
  `woocommerce_store_api_cart_errors` / checkout validation callbacks) so a paused
  store genuinely rejects block-checkout orders. **This is the highest-value fix:
  the whole point of the plugin is that paused means paused.**
- **[New] P2 — Reliable timed auto-resume via scheduling.** _(tracked in #2)_ Expiry of a timed
  pause (e.g. "Pause 30 Minutes") is currently evaluated on page load
  (`is_orders_paused()` / `check_scheduled_pause()` on `init`). On a low-traffic
  store with no visitors, orders stay paused past the intended resume time. Move
  auto-resume onto WooCommerce's Action Scheduler (preferred — already bundled) or
  WP-Cron, scheduled at the exact resume timestamp, with the page-load check kept
  as a fallback.
- **[New] P2 — Countdown for weekly schedule mode.** _(tracked in #3)_ `get_countdown_data()`
  computes the next open time for `daily` mode only; `weekly` mode returns no
  countdown. Compute the next opening slot for weekly schedules too.

---

## Milestone v1.4.x — features & UX (P3)

- **[New] P3 — Admin-bar "pause until next open" shortcut** for stores on a
  daily/weekly schedule, so a manual pause can align to the configured reopen time
  in one click.
- **[New] P3 — Accessibility pass on the frontend pause popup.** Add ARIA roles,
  focus trapping, and ESC-to-close to the `.uopwc-popup` overlay.
- **[New] P3 — Tighten "Hide Add to Cart" CSS.** The paused-state CSS hides a
  broad selector list including `.quantity`; review for over-hiding on some themes
  and page builders now that `woocommerce_is_purchasable` already blocks server-side.
- **[New] P4 — WP-CLI command** (`wp uopwc pause|resume`) for operators and cron
  wrappers.
- **[New] P4 — Suite tie-in:** log pause/resume events to the Unstoppable Activity
  Tracker plugin when it's active (via the existing `uopwc_orders_paused` /
  `uopwc_orders_resumed` actions).

---

## Pro / Base44 track (P3) **(Pro)**

- **[Discussed] Wire up licensing / Pro gating.** _(tracked in #4)_ `includes/class-uop-license.php`
  implements activate/validate/deactivate against the Base44 endpoint, but the
  class is **not currently instantiated** by the main bootstrap
  (`init_components()` creates Admin, Pause_Handler, and Admin_Bar only). Decide
  whether the License tab/UI ships in the free plugin as groundwork or moves
  entirely to the Pro repo, and wire it in accordingly.
- **[Discussed] Scaffold the Base44 Pro app** and record its app ID in
  `CLAUDE.md` (the "Base44 Pro mapping" section is a placeholder today).
- **(Pro) P3** — candidate premium features: per-product / per-category pause,
  multiple named schedules, holiday calendar import, email/Slack notification on
  pause, customer "notify me when orders resume" capture.

---

## Ongoing — security & compatibility (P1–P2)

- **[New] Maintain WordPress.org compliance:** no open vulnerabilities, keep
  `Tested up to` and `WC tested up to` current, Plugin Check CI green.
- **[New] Re-verify HPOS + Cart/Checkout Blocks compatibility** against each new
  WooCommerce major (both flags are declared in the bootstrap today).
- **[New] Keep the AJAX surface tight.** The admin-bar toggle already verifies a
  nonce and `manage_woocommerce`; preserve that on any new endpoints.
- **[New] Revisit the local-timezone `date()` phpcs:ignores** only if the stored
  option format is ever migrated — they exist to preserve values written by
  earlier releases.
