# Changelog

[Keep a Changelog](https://keepachangelog.com/en/1.1.0/) format, [Semantic Versioning](https://semver.org/).

## [1.1.0] - 2026-05-09

### Added

- Daily clicks chart (30/60/90d) with total, average, and peak summary.
- Top domains rollup.
- Click heatmap, 7×24 weekday/hour grid.
- Device and browser breakdown.
- User click trail drill-down from the clickers modal.
- Per-discussion click stats from the discussion controls dropdown.
- Domain blocklist setting. Supports `*.example.com` wildcards.
- External link confirm dialog (off by default).
- `link-clicks:reconcile` console command. Walks `post_links` and writes back counter drift. `--dry-run` available. Daily-scheduled.
- `link-clicks:build-daily-rollup` console command. Backs the time series chart on large forums. `--rebuild` wipes and recomputes. Daily-scheduled.
- `link-clicks:detect-anomalies` console command. Daily-scheduled.
- `link-clicks:send-digest` console command. Mails weekly summary to admins. Off by default.
- Webhook backoff: `[10s, 1m, 5m, 30m, 2h]`, retries lifted from 3 to 5.
- `X-LinkClicks-Delivery-Id` header on every webhook POST for receiver-side dedup.
- "Send test ping" button on the webhook tab. Synchronous. Surfaces HTTP status.
- API filter `discussion_id` on `/link-click-stats`.
- Forum attribute `canViewLinkClickAnalytics`.
- Settings: `domain_blocklist`, `confirm_external_clicks`, `digest_enabled`, `anomaly_threshold_ratio`, `anomaly_min_clicks`.

### Changed

- `link_click_events` gains `device_class` and `browser_family` columns, populated at click time.
- `post_links` gains a `domain` column so domain rollups don't reparse URLs at query time.
- Time series endpoint reads pre-aggregated daily totals for past days. Today still comes from raw events.

### Fixed

- Confirm dialog now matches tracked links by href pattern. Class-based detection missed posts when the formatter cache was stale.
- Forum-side init no longer crashes on `app.forum.attribute()` because of Flarum 2.x boot order.
- `UserClickTrailModal` no longer crashes when its title placeholder collided with the translator's reserved `user` key.

## [1.0.0] - 2026-05-09

First release.

### Counting

- A small badge with the click count appears next to every link in a post once people start clicking.
- Each person counts once per link per 24 hours by default. Configurable.
- Authors don't add to the count when they click their own links.
- Bots and Do-Not-Track requests are skipped. Admin can extend the bot list.
- Tracking query params like `utm_*`, `fbclid`, `gclid`, `mc_*`, `_ga`, `igshid` are stripped before counting so variants of the same URL merge into one. List is extendable.
- Internal links to discussions stay merged across renames. Linking to the same discussion under an old slug and a new slug counts together.
- Files attached to posts (`/assets/files/...` and other configurable prefixes) get their own counter and stay tracked even if you turn off general internal-link tracking.

### Widgets

- "Popular links" sidebar widget on each discussion. Shows the most-clicked external links from the discussion's posts.
- "Most clicked links" widget on user profiles. Shows what people clicked most across the user's posts.
- Live updates: when `flarum/realtime` is installed, badges tick up without a page reload.

### Admin

- Settings, Analytics, and Webhook split into three tabs on the extension page.
- Analytics: filter by date range, scope (all / external / internal / attachments), or tag. Sortable table. CSV export. Click a unique-users count to see exactly who clicked the link, with timestamps.
- Webhook: optional outgoing notification when a click is recorded. Toggle on, paste a URL, optionally set a shared secret for signature verification.

### Privacy

- Forum-wide setting to skip guests entirely.
- Per-user opt-out preference in account settings.
- Per-post toggle for authors and moderators from the post controls menu. Existing badges disappear; future clicks don't count. Already-rendered links keep working.
- Per-tag opt-out when `flarum/tags` is installed. Discussions tagged with a flagged tag are never tracked.
- GDPR export, anonymize, and delete when `flarum/gdpr` is installed. Delete keeps badge counts consistent.
- Daily cleanup of old click events with a configurable retention window. Set retention to `0` to keep them forever.

### Tooling

- `php flarum link-clicks:backfill` registers links from posts that existed before the extension was enabled.

[1.1.0]: https://github.com/datlechin/flarum-link-clicks/releases/tag/v1.1.0
[1.0.0]: https://github.com/datlechin/flarum-link-clicks/releases/tag/v1.0.0
