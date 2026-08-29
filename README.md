# Link Clicks

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md) [![Latest Stable Version](https://img.shields.io/packagist/v/datlechin/flarum-link-clicks.svg)](https://packagist.org/packages/datlechin/flarum-link-clicks) [![Total Downloads](https://img.shields.io/packagist/dt/datlechin/flarum-link-clicks.svg)](https://packagist.org/packages/datlechin/flarum-link-clicks)

Shows how many people opened each link in a post.

![Badge in a post](screenshots/badge.png)

`#hashtags` and `@mentions` are links too, so they get their own counts.

## Install

```sh
composer require datlechin/flarum-link-clicks
php flarum migrate
```

Enable it in **Admin → Extensions**.

## Features

For readers:

- Click counts on links, `#hashtags` and `@mentions`.
- Trending hashtags on the index, ranked by rise in click rate rather than lifetime total.
- Popular links per discussion, most-clicked links per profile.
- Live counts with `flarum/realtime`, no reload.

For moderators and admins:

- Analytics with filters and CSV export: daily chart, top domains, weekday-by-hour heatmap, device split.
- Who clicked a link, and every link a user clicked.
- Per-discussion stats from the discussion controls menu.

| Popular links | Profile | Who clicked |
|---|---|---|
| ![Popular links widget](screenshots/popular-widget.png) | ![Most clicked links on user profile](screenshots/user-widget.png) | ![Drill-down](screenshots/drilldown-modal.png) |

## Settings

**Admin → Extensions → Link Clicks**, three tabs: Settings, Analytics, Webhook.

![Admin analytics tab](screenshots/admin-analytics.png)

### Counting

| Setting | Default | What it does |
|---|---|---|
| `enabled` | `true` | Master switch. Off means no badges, no recording. |
| `min_display_count` | `1` | Hide the badge below this number. |
| `dedup_window_hours` | `24` | One person counts once per link in this window. |
| `track_internal` | `false` | Also count links back to the forum. Attachments count either way. |
| `track_tag_mentions` | `true` | Count `#hashtag` clicks. Needs `flarum/mentions` and `flarum/tags`. |
| `track_user_mentions` | `true` | Count `@username` clicks. Needs `flarum/mentions`. |
| `track_post_mentions` | `true` | Count post-mention clicks. Needs `flarum/mentions`. |

### Privacy

| Setting | Default | What it does |
|---|---|---|
| `honor_dnt` | `true` | Skip recording on `DNT: 1`. The link still works. |
| `skip_guests` | `false` | Count logged-in users only. |
| `event_retention_days` | `90` | Delete events older than this. `0` keeps everything. |
| `bot_user_agents` | | Extra User-Agent fragments to treat as bots, one per line. |

### Links

| Setting | Default | What it does |
|---|---|---|
| `open_in_new_window` | `false` | Open tracked links in a new tab. |
| `confirm_external_clicks` | `false` | Ask before sending a reader off the forum. |
| `domain_blocklist` | | Hosts to ignore, one per line. `*.example.com` covers subdomains. |
| `tracking_params_strip` | | Extra query params to strip so URL variants merge. `utm_*`, `fbclid`, `gclid`, `mc_*`, `igshid`, `_ga` already covered. Trailing `*` matches a prefix. |
| `attachment_path_prefixes` | | Extra paths to treat as attachments. `/assets/files/` is built in. |

### Reports

| Setting | Default | What it does |
|---|---|---|
| `trending_enabled` | `true` | Show trending hashtags on the index. |
| `trending_min_clicks` | `5` | Ignore hashtags below this many clicks in the last counted day. |
| `digest_enabled` | `false` | Email admins a weekly summary, Monday morning. |
| `anomaly_threshold_ratio` | `10` | Warn when a day's clicks exceed the prior six-day average by this much. |
| `anomaly_min_clicks` | `20` | Skip the check below this volume. |

### Permission

**View link click analytics**. Admins have it by default; grant it to other groups in **Admin → Permissions**.

## Webhook

Each recorded click is POSTed to your URL. Set a secret to sign the body.

```json
{
  "event": "click_recorded",
  "counted": true,
  "post_link": {
    "id": 123,
    "source": "url",
    "url": "https://example.com/page",
    "label": null,
    "is_internal": false,
    "is_attachment": false,
    "post_id": 456,
    "discussion_id": 789,
    "clicks_count": 42
  },
  "actor": { "user_id": 10, "username": "alice" },
  "ip_address": "192.168.1.1",
  "user_agent": "Mozilla/5.0 ...",
  "clicked_at": "2026-05-09T12:34:56Z"
}
```

- `source` is `url`, `tag_mention`, `user_mention` or `post_mention`. `label` is a display name (`#support`, `@alice`) for mentions, `null` for plain links.
- `counted` is `false` when the click hit a dedup or self-click rule.
- `actor` is `null` for guests.
- With a secret, the signature arrives in `X-LinkClicks-Signature: sha256=<hex>` over the raw body.

Delivery is queued and retried. The Webhook tab has a **Send test ping** button.

## Console commands

All scheduled already; run them by hand only if you want to.

| Command | What it does |
|---|---|
| `link-clicks:backfill` | Register links in posts written before the extension was enabled. Safe to re-run. `--chunk=N`, `--from-id=X`. |
| `link-clicks:reconcile` | Recount from recorded events and correct drift. `--dry-run` to report only. |
| `link-clicks:build-daily-rollup` | Aggregate events into the daily table behind the chart. `--rebuild` starts over. |
| `link-clicks:purge-events` | Delete events past the retention window. |
| `link-clicks:detect-anomalies` | Log a warning when a day's clicks spike. |
| `link-clicks:send-digest` | Email the weekly summary now. |

## Privacy and GDPR

Each recorded click stores an IP address and User-Agent, personal data under GDPR. You need to disclose the collection, pick a lawful basis (legitimate interest usually fits), and set `event_retention_days`. Install `flarum/gdpr` and access, export and erasure requests are handled for you.

Defaults lean private: bots dropped, `DNT: 1` honoured, authors can't inflate their own counts, and the tracking URL never carries the destination.

## Adding your own trackable

Implement `TrackableSource`, which declares the formatter tag to read, how to find targets, and where a click lands. Register it:

```php
(new Datlechin\LinkClicks\Extend\TrackableSources())
    ->add(MyPollVoteSource::class),
```

Extraction, rendering, recording, analytics, GDPR and the console commands all pick it up.

## Sponsors

If this is useful, [sponsoring on GitHub](https://github.com/sponsors/datlechin) helps me keep maintaining it.

[Packagist](https://packagist.org/packages/datlechin/flarum-link-clicks) · [GitHub](https://github.com/datlechin/flarum-link-clicks) · [Discuss](https://discuss.flarum.org/d/39223)
