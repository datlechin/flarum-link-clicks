# Link Clicks

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/datlechin/flarum-link-clicks.svg)](https://packagist.org/packages/datlechin/flarum-link-clicks) [![Total Downloads](https://img.shields.io/packagist/dt/datlechin/flarum-link-clicks.svg)](https://packagist.org/packages/datlechin/flarum-link-clicks)

A Flarum extension that puts a click count next to every link in a post — including `#hashtags` and `@mentions`.

![Badge in a post](screenshots/badge.png)

## What it does

Every `http(s)` link grows a small badge once people start clicking. The badge inherits your theme and dark mode automatically. The number tells everyone reading the post how many people opened that link.

On top of the badge:

- **Hashtags and mentions are links too.** `#hashtag`, `@username` and post mentions all point somewhere, so they are counted alongside ordinary links. Each is identified by the tag, user or post it points at, so renaming one keeps its history in one piece instead of starting over.
- **Trending hashtags** widget on the forum index, ranked by how far a hashtag's click rate has risen above its own weekly average rather than by lifetime total.
- **Popular links** sidebar widget on each discussion.
- **Most clicked links** widget on user profiles.
- **Live updates** when `flarum/realtime` is installed. Badges tick up without a page reload.
- **Admin analytics** with filters, CSV export, and a per-link drill-down that shows who clicked. Daily clicks chart, top domains rollup, weekday-by-hour heatmap, and a mobile / tablet / desktop split.
- **Per-discussion click stats**. Mods get a "Click stats" item in the discussion controls dropdown, scoped to that thread.
- **User click trail**. Drill from a clicker into every link that user has opened.
- **Webhook** to forward click events. Exponential backoff retries, dedup header, and a "Send test ping" button.
- **Console tooling**: backfill, counter reconcile, daily rollup, anomaly detection, weekly digest.
- **Domain blocklist** and an optional **confirm-before-leaving** dialog for outbound clicks.
- **Privacy controls**: forum-wide skip-guests, per-user opt-out, author per-post toggle, tag-level opt-out (with `flarum/tags`), and full GDPR export / anonymize / delete (with `flarum/gdpr`).

| Popular links sidebar | Most clicked links on profile |
|---|---|
| ![Popular links widget](screenshots/popular-widget.png) | ![Most clicked links on user profile](screenshots/user-widget.png) |

## Installation

```sh
composer require datlechin/flarum-link-clicks
```

Enable from Admin → Extensions.

## Updating

```sh
composer update datlechin/flarum-link-clicks
php flarum migrate
php flarum cache:clear
```

## Configuration

The extension page (Admin → Extensions → Link Clicks) has three tabs.

![Admin analytics tab](screenshots/admin-analytics.png)

### Settings

| Key | Default | What it does |
|---|---|---|
| `enabled` | `true` | Master switch. Off means no badges and no recording. |
| `track_internal` | `false` | Track links pointing back at the forum. Off keeps the focus on outbound traffic. Attachments are tracked regardless. |
| `min_display_count` | `1` | Hide the badge below this number. |
| `honor_dnt` | `true` | Skip recording when the request carries `DNT: 1`. The redirect still works. |
| `skip_guests` | `false` | Drop all guest clicks. Logged-in users only. |
| `dedup_window_hours` | `24` | A given user (or guest IP) only counts once per link in this window. |
| `event_retention_days` | `90` | Daily job removes click events older than this. Set to `0` to keep everything. |
| `bot_user_agents` |  | Extra User-Agent fragments treated as bots. One per line. |
| `tracking_params_strip` |  | Extra query params to strip before counting. One per line. Trailing `*` matches a prefix. Defaults already cover `utm_*`, `fbclid`, `gclid`, `mc_*`, `igshid`, `_ga` and others. |
| `attachment_path_prefixes` |  | Extra URL path prefixes treated as attachments. One per line. `/assets/files/` is built in. |
| `domain_blocklist` |  | Hosts to skip entirely. One per line. `*.example.com` matches every subdomain. Posts referencing these hosts get no badge and clicks aren't recorded. |
| `track_tag_mentions` | `true` | Count clicks on `#hashtags`. Needs `flarum/mentions` and `flarum/tags`. |
| `track_user_mentions` | `true` | Count clicks on `@username` mentions. Needs `flarum/mentions`. |
| `track_post_mentions` | `true` | Count clicks on post mentions. Needs `flarum/mentions`. |
| `trending_enabled` | `true` | Show the trending hashtags widget on the forum index. |
| `trending_min_clicks` | `5` | A hashtag below this many clicks in the last counted day never trends, however sharply it rose. |
| `confirm_external_clicks` | `false` | Show a browser confirm dialog when a reader clicks an external tracked link. |
| `digest_enabled` | `false` | Mail every administrator a plain-text summary of the past week's clicks every Monday morning. |
| `anomaly_threshold_ratio` | `10` | Daily anomaly check logs a warning when the past 24 hours' click volume exceeds the prior six-day average by this ratio. |
| `anomaly_min_clicks` | `20` | Floor below which the anomaly check is skipped, so quiet forums don't fire on every blip. |

### Webhook

Each recorded click is sent to your URL as a JSON POST. Toggle it on, paste a URL, optionally set a shared secret to sign the request body.

```json
{
  "event": "click_recorded",
  "counted": true,
  "post_link": { "id": 123, "url": "https://example.com/page", "source": "url", "label": null, "is_internal": false, "is_attachment": false, "post_id": 456, "discussion_id": 789, "clicks_count": 42 },
  "actor": { "user_id": 10, "username": "alice" },
  "ip_address": "192.168.1.1",
  "user_agent": "Mozilla/5.0 ...",
  "clicked_at": "2026-05-09T12:34:56Z"
}
```

`source` is `url`, `tag_mention`, `user_mention` or `post_mention`, and `label` carries a display name (`#support`, `@alice`) for the mention sources, `null` for plain links. Both are additive — a receiver written against the earlier payload keeps working, and `url` still names a real destination. `actor` is `null` for guest clicks. `counted` is `false` when the click hit a dedup or self-click rule (the badge didn't tick up). When a secret is set, the signature is sent in `X-LinkClicks-Signature: sha256=<hex>`, computed over the raw body. Delivery is async and retried a few times on failure.

![Drill-down: who clicked this link](screenshots/drilldown-modal.png)

### Permissions

Adds one permission: **View link click analytics**. Admins have it by default. Grant to other groups from Admin → Permissions.

## Extending

Anything clickable in a post can be counted. Implement `Datlechin\LinkClicks\Contract\TrackableSource` — it declares the TextFormatter tag to read, how to find targets in a post, and where a click should land — and register it:

```php
(new Datlechin\LinkClicks\Extend\TrackableSources())
    ->add(MyPollVoteSource::class),
```

The new source then flows through extraction, rendering, click recording, the analytics endpoints, GDPR export and the console tooling without any further wiring.

## Console commands

| Command | What it does |
|---|---|
| `link-clicks:backfill` | Registers links from posts that existed before the extension was enabled. Safe to re-run. `--chunk=N`, `--from-id=X`. |
| `link-clicks:reconcile` | Walks every tracked link and writes back any drift between the stored counter and the actual recorded events. `--dry-run` reports without writing. Daily-scheduled. |
| `link-clicks:build-daily-rollup` | Aggregates raw events into the daily rollup table that powers the time-series chart on large forums. First run backfills from the oldest event; subsequent runs resume. `--rebuild` wipes and recomputes. Daily-scheduled. |
| `link-clicks:detect-anomalies` | Logs a warning when the past day's click volume jumps versus the prior six-day average. Daily-scheduled. |
| `link-clicks:purge-events` | Removes click events older than the retention window. Daily-scheduled. |
| `link-clicks:send-digest` | Mails the weekly summary to administrators. Weekly-scheduled (Monday 06:00). |

## Privacy and GDPR

The extension stores the IP address and User-Agent of each recorded click. Under GDPR these are personal data. As the forum operator you're responsible for:

- Disclosing the collection in your privacy notice.
- Choosing a lawful basis (legitimate interest is the usual fit for engagement analytics).
- Setting a retention window (the daily purge handles this).
- Honouring access and erasure requests. Install `flarum/gdpr` to expose them automatically.

Defaults that lean toward privacy: bots are dropped, `DNT: 1` is honoured, authors can't inflate their own counts, the redirect URL never contains the destination.

## Sponsors

If this extension is useful to you, [sponsoring on GitHub](https://github.com/sponsors/datlechin) helps me keep building and maintaining open source for Flarum.

## Links

- [Packagist](https://packagist.org/packages/datlechin/flarum-link-clicks)
- [GitHub](https://github.com/datlechin/flarum-link-clicks)
- [Discuss](https://discuss.flarum.org/d/39223)
