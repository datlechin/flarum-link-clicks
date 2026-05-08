# PRD — Link Clicks (Flarum 2.x extension)

> Inspired by Discourse's "Link Click Counters" — show how often each link in a post has been clicked, display the count inline next to the link, and surface popular links per discussion.

- **Composer package:** `datlechin/flarum-link-clicks`
- **Display title:** `Link Clicks`
- **PHP namespace:** `Datlechin\LinkClicks`
- **Author:** Ngo Quoc Dat <datlechin@gmail.com>
- **Status:** Draft v1.1
- **Date:** 2026-05-08
- **Target:** Flarum 2.x (`^2.0.0-rc.1`)
- **License:** MIT

---

## 1. TL;DR

Add a small badge `[N]` next to every `http(s)` link in a post showing how many distinct users have clicked it. Clicks are routed through a server-side redirect endpoint (`/lcc/track`), recorded as individual events in a new table, deduplicated per-user-per-day, and rendered back into post HTML at format-render time. Counts update live for viewers via the existing `flarum-realtime` extension. A per-discussion "Popular links" sidebar widget and a forum-wide admin analytics page round out the MVP.

---

## 2. Problem & Motivation

### Problem
Long Flarum discussions accumulate dozens of links — tutorials, references, sources, off-topic detours. Readers have no signal about which ones are worth their time. Authors have no signal about whether the resources they shared were useful. Moderators have no signal about which external links the community engages with.

### Why now
- Discourse has had this feature for years and it consistently surfaces in top-N lists of "what makes Discourse feel different from other forum software."
- No equivalent exists in the Flarum ecosystem (verified 2026-05-08): the closest packages either track only magnet links (`tryhackx/flarum-magnet-link`) or push events to third-party analytics (`FriendsOfFlarum/analytics`, `blomstra/fathom-analytics`) — neither renders an inline social-proof badge on the post itself.
- Flarum 2.x ships `flarum-realtime` as a first-class extension, which gives us a clean way to **beat** Discourse's known limitation that click counts only update on page reload.

### Why this design over alternatives
Considered and rejected during research:
- **Client-side beacon only** — loses clicks when JS is off, easily blocked by adblockers, harder to do anti-abuse server-side.
- **Onebox/rich-preview embedding** — different feature, larger scope.
- **Push to external analytics** — misses the "inline social proof" UX, which is the actual value prop.

---

## 3. Goals & Non-Goals

### Goals (MVP)
- G1. Display a click-count badge next to every `http(s)` link in rendered post HTML.
- G2. Track clicks via a server-side redirect endpoint that 302s to the destination after recording.
- G3. Apply Discourse-style anti-abuse rules so the displayed count reflects distinct interest, not refresh-spamming.
- G4. Update badges in real time for viewers of the same discussion (via `flarum-realtime`).
- G5. Provide a per-discussion "Popular links" sidebar widget.
- G6. Provide an admin page listing the most-clicked links forum-wide with date filters.

### Non-goals (out of scope for MVP)
- N1. Onebox/rich-preview cards. Out of scope; orthogonal feature.
- N2. Outbound link warning interstitials.
- N3. UTM injection or affiliate-link rewriting.
- N4. Per-user click history exposed as a public profile section.
- N5. Cross-forum/global popularity (only this forum's own data).
- N6. Tracking non-`http(s)` schemes (`mailto:`, `magnet:`, etc.).

---

## 4. Personas & User Stories

### Reader (anonymous or logged-in)
- *As a reader skimming a long thread, I want to see which links others found worth clicking, so I can prioritize my reading.*
- *As a reader, I want clicking the badge or the link itself to take me to the destination immediately — the count must not slow me down.*

### Author
- *As a post author, I want to know which of my shared resources actually got used.*
- *As a post author, my own clicks on my own links shouldn't inflate the count.*

### Moderator / Admin
- *As an admin, I want to see the most popular external destinations my community is sending traffic to (potential partnership, abuse, or signal of interests).*
- *As an admin, I want to disable counters on specific tags / per-extension if needed.*
- *As an admin/DPO, I want to be confident the extension is GDPR-safe out of the box.*

---

## 5. Functional Requirements

### 5.1 What gets a badge
- All `<a href="http://...">` and `<a href="https://...">` rendered inside post content.
- **Both** internal and external links (per design decision; admin can toggle internal off — see §10).
- Badge shows only when count ≥ 1 (no `[0]` clutter).
- Badge format: small pill with count, accessible label `aria-label="Đã được click N lần"`.

### 5.2 Click tracking flow
1. At post render time, the formatter rewrites every qualifying `<a href>` from `https://example.com/x` → `/lcc/track?u=<base64url>&p=<post_id>` and adds `data-clicks="N"` for CSS rendering.
2. User clicks → browser hits `/lcc/track`.
3. The endpoint validates the encoded URL (signature or HMAC, see §6.3), records an event, then issues a `302` redirect to the original URL.
4. The user's browser follows the redirect.

### 5.3 Anti-abuse / counted-click rules
A click is **recorded** in the event log if:
- The HTTP method is `GET` (no POST/HEAD).
- The `User-Agent` is not in the bot blocklist (reuse Flarum's existing user-agent inspection where possible; otherwise ship a small static list).

A click is **counted toward the displayed badge** if **all** of:
- The clicker is not the post's author.
- For logged-in users: the same `(user_id, post_id, normalized_url)` has not been counted in the past 24h.
- For anonymous users: the same `(ip_hash, post_id, normalized_url)` has not been counted in the past 24h. `ip_hash = sha256(ip + daily_salt)`; `daily_salt` rotates every UTC midnight.

Events that fail the "counted" rule are still written to the log (with a `counted=false` flag) so that retroactive analytics and abuse investigation are possible, but they don't increment the displayed counter.

### 5.4 Click types
- **Left-click (incl. ctrl/cmd/shift):** counted.
- **Middle-click:** browsers don't fire a reliable `click` for these on `<a>`, but because we're using a real `href` redirect, the browser navigates regardless and the request is captured server-side. ✅ Better than Discourse here.
- **Right-click "Copy link":** does not navigate, not counted. Acceptable.

### 5.5 Real-time updates (G4)
- When a click is `counted`, broadcast a `linkClickCounted` event scoped to the post's discussion, payload `{ post_id, url_id, count }`.
- Forum frontend subscribes to discussion stream (already wired by `flarum-realtime`); the JS handler updates the matching link's `data-clicks` attribute → CSS re-renders the badge.

### 5.6 Popular links widget (G5)
- Sidebar widget on discussion view.
- Shows top 5 links in this discussion by counted-click total.
- Each row: favicon, title (best-effort: link text or hostname), count.
- Empty state: hide the widget entirely (no "no clicks yet" placeholder).

### 5.7 Admin analytics (G6)
- Admin page under Extensions → Link Click Counter.
- Filters: date range (default last 30d), tag (optional), internal/external/all.
- Table: URL, total counted clicks, unique users, first seen, last seen.
- Export CSV button.

### 5.8 Permissions
- New permission: `discussion.viewLinkClickCounts` (default: everyone, including guests). Lets admins hide counters from guests if desired.
- New permission: `linkClickCounter.viewAnalytics` (default: admin group only).

---

## 6. Technical Design

### 6.1 Extension scaffold

**Folder layout** — mirrors first-party extensions in `framework/extensions/{likes,tags,sticky}`:
```
packages/flarum-link-clicks/
├── composer.json
├── extend.php
├── README.md
├── LICENSE.md
├── CHANGELOG.md
├── .github/workflows/        (CI from flarum-cli scaffold)
├── .gitignore  .gitattributes  .editorconfig  .styleci.yml  .prettierrc
├── js/
│   ├── package.json
│   ├── tsconfig.json          (TS, theo first-party flarum/tags)
│   ├── webpack.config.js      (single file: const config = require('flarum-webpack-config'))
│   ├── admin.js  forum.js     (entry points, do flarum-cli sinh)
│   └── src/
│       ├── admin/             (settings page)
│       ├── forum/             (realtime listener, sidebar widget)
│       └── common/            (shared models, types)
├── less/
│   ├── admin.less
│   └── forum.less
├── locale/
│   ├── en.yml
│   └── vi.yml
├── migrations/
│   ├── 2026_05_08_000000_create_post_links_table.php
│   └── 2026_05_08_000001_create_link_click_events_table.php
├── src/                       (PSR-4: Datlechin\LinkClicks\)
│   ├── Api/
│   │   ├── Controller/
│   │   │   ├── TrackClickController.php
│   │   │   ├── ListPopularLinksController.php
│   │   │   └── ListLinkStatsController.php   (admin analytics)
│   │   └── Resource/
│   │       └── PostLinkResource.php           (JSON:API resource)
│   ├── Event/
│   │   ├── ClickRecorded.php
│   │   └── ClickCounted.php
│   ├── Formatter/
│   │   └── RewriteLinksForTracking.php
│   ├── Listener/
│   │   └── BroadcastCountedClick.php
│   ├── PostLink.php                            (Eloquent model)
│   ├── LinkClickEvent.php                      (Eloquent model)
│   ├── Access/
│   │   └── PostLinkPolicy.php
│   └── Service/
│       ├── ClickCounter.php                    (anti-abuse rule engine)
│       ├── UrlNormalizer.php
│       └── TrackingUrlSigner.php               (HMAC sign/verify)
└── tests/
    ├── phpunit.unit.xml
    ├── phpunit.integration.xml
    ├── unit/
    └── integration/
```

Một số chi tiết quan trọng theo first-party convention:
- Models nằm **flat** trực tiếp dưới `src/` (như `framework/extensions/likes/src/Post.php` không phải `src/Models/Post.php` nếu là core domain). PostLink và LinkClickEvent là core model của extension → để flat.
- Mỗi feature group có **sub-namespace riêng**: `Api/Controller/`, `Event/`, `Listener/`, `Access/`, `Service/`. Không gom hết vào một `Helpers/` chung chung.
- Test split rõ `unit/` vs `integration/`, có config riêng — copy y nguyên từ `flarum/likes/tests/`.

**Canonical `composer.json`** — tuân thủ chính xác pattern của `flarum/likes`, `flarum/sticky`, `flarum/subscriptions` (4-space indent, single topical keyword, `branch-alias`, `funding`, `optional-dependencies`, `minimum-stability: dev`, scripts test):

```json
{
    "name": "datlechin/flarum-link-clicks",
    "description": "Show a click count next to each link in a post.",
    "type": "flarum-extension",
    "keywords": [
        "discussion"
    ],
    "license": "MIT",
    "support": {
        "issues": "https://github.com/datlechin/flarum-link-clicks/issues",
        "source": "https://github.com/datlechin/flarum-link-clicks"
    },
    "homepage": "https://github.com/datlechin/flarum-link-clicks",
    "funding": [
        {
            "type": "github",
            "url": "https://github.com/sponsors/datlechin"
        }
    ],
    "authors": [
        {
            "name": "Ngo Quoc Dat",
            "email": "datlechin@gmail.com",
            "role": "Developer"
        }
    ],
    "require": {
        "php": "^8.2",
        "flarum/core": "^2.0.0-rc.1"
    },
    "require-dev": {
        "flarum/testing": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "Datlechin\\LinkClicks\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Datlechin\\LinkClicks\\Tests\\": "tests/"
        }
    },
    "extra": {
        "branch-alias": {
            "dev-main": "1.x-dev"
        },
        "flarum-extension": {
            "title": "Link Clicks",
            "category": "feature",
            "optional-dependencies": [
                "flarum/realtime"
            ],
            "icon": {
                "name": "fas fa-arrow-pointer",
                "backgroundColor": "#3B82F6",
                "color": "#fff"
            }
        },
        "flarum-cli": {
            "modules": {
                "admin": true,
                "forum": true,
                "js": true,
                "jsCommon": true,
                "css": true,
                "gitConf": true,
                "githubActions": true,
                "prettier": true,
                "typescript": true,
                "bundlewatch": false,
                "backendTesting": true,
                "editorConfig": true,
                "styleci": true
            }
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "scripts": {
        "test": [
            "@test:unit",
            "@test:integration"
        ],
        "test:unit": "phpunit -c tests/phpunit.unit.xml",
        "test:integration": "phpunit -c tests/phpunit.integration.xml",
        "test:setup": "@php tests/integration/setup.php"
    },
    "scripts-descriptions": {
        "test": "Runs all tests.",
        "test:unit": "Runs all unit tests.",
        "test:integration": "Runs all integration tests.",
        "test:setup": "Sets up a database for use with integration tests. Execute this only once."
    }
}
```

**Khác biệt so với pattern phổ biến trong cộng đồng (đã được loại bỏ ở đây):**
| Anti-pattern thường gặp | Đã sửa theo first-party |
|---|---|
| `keywords: ["flarum", "flarum-extension", "link-click", ...]` | `["discussion"]` — single topical keyword. `flarum`/`flarum-extension` đã suy ra từ `type` |
| 2-space indent | 4-space indent |
| Thiếu `branch-alias` | Có `branch-alias.dev-main` để Composer resolve dev-main → 1.x-dev |
| Hard-depend `flarum/realtime` | Đặt vào `optional-dependencies` (extension vẫn hoạt động khi realtime tắt, chỉ bỏ live update) |
| Thiếu `minimum-stability: dev` + `prefer-stable: true` | Thêm vào — cho phép cài bản dev của Flarum core khi đang phát triển |
| Thiếu `funding` field | Thêm — convention của tất cả first-party |
| Khai báo `flarum-cli.modules` lung tung | Bật `typescript: true` + `jsCommon: true` để khớp với extension lớn (`flarum/tags`) |
| Self-referencing `repositories: [{ type: path, url: ../../*/* }]` trong stand-alone repo | **Bỏ** — đó là pattern monorepo; extension độc lập không cần. Thêm lại nếu sau này submit vào monorepo |
| `php` version không khai báo | Thêm `"php": "^8.2"` để Composer fail sớm trên môi trường thấp |
| `autoload-dev` thiếu | Có — bắt buộc nếu chạy được `phpunit` |

### 6.2 Hook points in Flarum 2.x
- `Extend\Frontend('forum')` — load forum JS/LESS.
- `Extend\Frontend('admin')` — load admin JS.
- `Extend\Formatter` — register a render callback that walks `<a>` elements and rewrites href + adds `data-clicks`.
- `Extend\Routes('forum')` — register `GET /lcc/track`.
- `Extend\Routes('api')` — register popular-links + admin-analytics endpoints.
- `Extend\ApiResource(PostResource)` — add `linkClicks` field eager-loaded with post show/list.
- `Extend\Conditional()->whenExtensionEnabled('flarum-realtime', ...)` — wire `linkClickCounted` broadcast.
- `Extend\Settings()` — defaults for admin toggles (see §10).
- `Extend\Policy()` — view-counts permission.
- `Extend\Locales(__DIR__.'/locale')`.

### 6.3 Redirect endpoint security
- Encoded URL parameter `u` is **HMAC-signed** with the forum's `APP_KEY` so users can't forge `/lcc/track?u=arbitrary` to count fake clicks or abuse the forum as an open redirector.
- Endpoint validates: signature, post existence, post visibility for the requesting user (returns `404` otherwise — don't leak post IDs).
- After validation, performs a `302` redirect with `Referrer-Policy: no-referrer-when-downgrade` to preserve normal browser behavior.

### 6.4 URL normalization
Before checking the per-day dedup, URLs are normalized:
- Lowercase scheme + host.
- Strip default ports (`:80`, `:443`).
- Strip trailing `/` on paths.
- Sort query parameters alphabetically.
- Drop common tracking params: `utm_*`, `fbclid`, `gclid`, `ref`.

This normalization is done once at post-render time when generating `url_id`, and the same normalized form is used as the dedup key.

---

## 7. Data Model

### 7.1 `post_links` (aggregate / lookup table)
Canonical record for each (post, normalized URL) pair. One row per unique link instance per post.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `post_id` | bigint FK → `posts.id` ON DELETE CASCADE | |
| `discussion_id` | bigint FK → `discussions.id` ON DELETE CASCADE | denormalized for cheap "popular per discussion" queries |
| `url` | text | normalized form |
| `url_hash` | binary(32) | sha256(url), indexed |
| `is_internal` | bool | whether host matches forum host |
| `clicks_count` | int unsigned NOT NULL DEFAULT 0 | materialized counter, what the badge reads |
| `first_seen_at` | timestamp | |
| `last_clicked_at` | timestamp NULL | |

Indexes: `(post_id)`, `(discussion_id, clicks_count DESC)`, `(url_hash)`.

### 7.2 `link_click_events` (raw event log)
Append-only. Powers anti-abuse, analytics, and time-series queries.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `post_link_id` | bigint FK → `post_links.id` ON DELETE CASCADE | |
| `user_id` | bigint NULL FK → `users.id` ON DELETE SET NULL | NULL for anonymous |
| `ip_hash` | binary(32) NULL | sha256(ip + daily_salt); NULL for logged-in users |
| `user_agent_family` | varchar(32) NULL | bucketed (chrome / firefox / safari / bot / other) — not full UA |
| `counted` | bool NOT NULL | whether this event incremented `post_links.clicks_count` |
| `clicked_at` | timestamp | |

Indexes: `(post_link_id, clicked_at)`, `(user_id, post_link_id, clicked_at)` for the 24h dedup probe, `(clicked_at)` for retention sweeps.

### 7.3 Retention
- Raw events older than **90 days** are aggregated into a daily rollup table `link_click_daily(post_link_id, day, counted_count)` and the underlying rows deleted.
- `clicks_count` on `post_links` is never reset.
- Configurable via admin setting `linkClickCounter.eventRetentionDays` (default 90).

### 7.4 Sync between event log and aggregate counter
- Inside the redirect endpoint, in a single transaction:
  1. Insert into `link_click_events` with computed `counted` flag.
  2. If `counted`, `UPDATE post_links SET clicks_count = clicks_count + 1, last_clicked_at = NOW() WHERE id = ?`.
- A nightly job verifies the counter matches `COUNT(*) WHERE counted=true` for the prior week and self-heals drift, logging a warning if drift > 1%.

---

## 8. API Surface

| Method | Path | Auth | Purpose |
|---|---|---|---|
| `GET` | `/lcc/track?u=<sig>&p=<post_id>` | optional | Record click + 302 redirect |
| `GET` | `/api/discussions/{id}/popular-links` | session | Top N links for sidebar widget |
| `GET` | `/api/link-click-stats` | admin | Forum-wide analytics (paginated, filterable) |
| `GET` | `/api/link-click-stats.csv` | admin | CSV export of the same |

Posts list/show endpoints gain a `linkClicks` relationship (sparse-fieldset opt-in) returning `{url, count}[]` per post — used by the forum frontend to populate `data-clicks` attributes when posts hydrate.

---

## 9. Frontend / UX

### 9.0 Design principles
- **Bám sát Flarum 2.x design system.** Không tạo style mới — chỉ tái sử dụng component và CSS custom property hiện có (`--muted-color`, `--control-bg`, `--control-color`, `--text-color`, `--border-radius`, `--pill-bg`, `--pill-color`). Tuyệt đối không hardcode color, không hardcode pixel size, không custom shadow/gradient.
- **Microcopy tự nhiên.** Tất cả chuỗi hiển thị viết qua locale file (`en.yml`, `vi.yml`); không hard-code trong JS/PHP. Ngôn ngữ Việt phải đọc tự nhiên — không dùng "lượt click", "lần được click" (mang vibe dịch máy); ưu tiên cách nói cộng đồng diễn đàn quen thuộc. Xem §9.5.
- **Pluralization đúng.** Dùng `app.translator.trans` với count parameter để locale tự xử lý số nhiều — không nối chuỗi tay.
- **A11y mặc định.** Mỗi badge có `aria-label` đầy đủ, không chỉ là số. Tab/keyboard navigation phải hoạt động — link là `<a>` thật, redirect server-side, nên không cần thêm gì cho keyboard.
- **Không animation cho counter increment.** Realtime update đổi `data-clicks` lặng lẽ. Animation gây phân tâm khi đọc post dài.
- **Không dependency mới.** Toàn bộ frontend dùng Mithril + helper sẵn của Flarum (`app.translator`, `app.store`, `app.session`, `extend`/`override` từ `flarum/extend`). Không thêm React, Vue, axios, lodash, dayjs riêng.

### 9.1 Inline badge bên cạnh link

**Markup phát ra từ formatter (server-side render):**
```html
<a href="/lcc/track?u=…&p=42"
   data-clicks="17"
   class="LinkClicks-link">
  example.com
</a>
```

**Style — viết bằng LESS, dùng đúng CSS custom property của Flarum:**
```less
// less/forum.less
.LinkClicks-link {
  &[data-clicks]:not([data-clicks="0"])::after {
    content: attr(data-clicks);
    display: inline-block;
    margin-inline-start: 4px;
    padding: 0 6px;
    font-size: 11px;
    line-height: 16px;
    font-weight: 600;
    border-radius: var(--border-radius);
    background: var(--control-bg);
    color: var(--control-color);
    vertical-align: 1px;
  }
}
```
Lý do dùng `--control-bg` / `--control-color` thay vì `--muted-color`: counter là một control nhỏ (giống pill), không phải metadata mờ. Hai biến này tự động đổi đúng theo dark mode mà Flarum đã wire sẵn. **Không** thêm `!important`, **không** override font-family, **không** đổi `transition`.

Counter `0` không hiện (selector `:not([data-clicks="0"])`).

**Tooltip on hover** dùng component `flarum/common/components/Tooltip` (đã có sẵn). Nội dung tooltip lấy từ locale, ví dụ vi: `"{count} người đã mở"` — xem §9.5.

### 9.2 Cập nhật real-time

JS forum lắng nghe broadcast `linkClickCounted` qua API của `flarum-realtime` (extension là dependency mềm — `whenExtensionEnabled`):

```ts
// js/src/forum/realtime.ts
import app from 'flarum/forum/app';

export default function listenForLinkClicks() {
  app.realtime?.subscribe('discussion', discussionId, (event: any) => {
    if (event.type !== 'linkClickCounted') return;

    const selector = `a.LinkClicks-link[data-post-id="${event.postId}"][data-url-id="${event.urlId}"]`;
    document.querySelectorAll<HTMLAnchorElement>(selector).forEach((a) => {
      a.dataset.clicks = String(event.count);
      a.setAttribute('aria-label', app.translator.trans(
        'datlechin-link-clicks.forum.link_aria_label',
        { count: event.count }
      ) as string);
    });
  });
}
```

Không dùng `setInterval` polling. Không re-render Mithril toàn bộ post — chỉ chạm `data-clicks` attribute, để CSS tự re-render counter.

### 9.3 Widget "Link nổi bật" trên sidebar discussion

**Mount point.** Dùng extender `extend` của Flarum lên `DiscussionPage.prototype.sidebarItems()` — chèn item sau `controls` và trước `subscriptions` (nếu có), để widget không phá thứ tự sidebar mặc định khi tắt extension.

**Component.** Kế thừa từ Mithril component thuần, render bên trong cấu trúc sidebar chuẩn:
```tsx
// js/src/forum/components/PopularLinksWidget.tsx
import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Placeholder from 'flarum/common/components/Placeholder';

export default class PopularLinksWidget extends Component {
  // … oninit fetches /api/discussions/{id}/popular-links
  view() {
    if (this.loading) return <LoadingIndicator />;
    if (!this.links?.length) return null; // ẩn hoàn toàn khi rỗng — không placeholder

    return (
      <div className="Sidebar-section LinkClicks-popular">
        <h4 className="Sidebar-sectionTitle">
          {app.translator.trans('datlechin-link-clicks.forum.popular_links_title')}
        </h4>
        <ul className="LinkClicks-popular-list">
          {this.links.map(link => (
            <li>
              <a href={link.trackUrl} className="LinkClicks-popular-item">
                <span className="LinkClicks-popular-host">{link.host}</span>
                <span className="LinkClicks-popular-count">{link.count}</span>
              </a>
            </li>
          ))}
        </ul>
      </div>
    );
  }
}
```

**Style** — tái dụng `Sidebar-section` + `Sidebar-sectionTitle` đã có trong core; chỉ thêm rất ít LESS cho list item:
```less
.LinkClicks-popular-list {
  list-style: none;
  padding: 0;
  margin: 0;
}
.LinkClicks-popular-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  padding: 6px 0;
  color: var(--text-color);

  &:hover { color: var(--link-color); }
}
.LinkClicks-popular-host {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.LinkClicks-popular-count {
  flex-shrink: 0;
  font-size: 12px;
  color: var(--muted-color);
}
```

Không icon emoji `🔗`. Không favicon (favicon yêu cầu fetch external → privacy risk + render shift). Chỉ hostname text + count text — gọn, an toàn, dễ đọc.

### 9.4 Trang admin

Kế thừa `flarum/admin/components/ExtensionPage` — pattern chuẩn của Flarum, đảm bảo settings panel, header, breadcrumb đều khớp với tất cả extension khác.

```tsx
// js/src/admin/components/LinkClicksPage.tsx
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
// …
export default class LinkClicksPage extends ExtensionPage {
  content() {
    return [
      this.settingsSection(),  // re-uses ExtensionPage.buildSettingComponent
      this.analyticsSection(), // bảng top-clicked link
    ];
  }
}
```

**Settings panel** dùng `this.buildSettingComponent({ type: 'switch' | 'number' | 'text', setting: 'datlechin-link-clicks.<key>', label: app.translator.trans(...) })` — không tự render `<input>` raw.

**Bảng analytics** dùng cấu trúc table chuẩn:
- `<table className="Table">`
- Sort header click → re-fetch với `?sort=…`
- Pagination dùng `flarum/common/components/Pagination`
- Date range dùng 2 input `type="date"` (HTML5 native), label qua locale; không pull date-picker library

CSV export: link `<a href="/api/link-click-stats.csv?…" download>` — không cần JS.

### 9.5 Locale & microcopy

Mọi chuỗi nằm trong `locale/en.yml` và `locale/vi.yml`. Không string nào hard-code trong JSX/PHP.

**`locale/en.yml`:**
```yaml
datlechin-link-clicks:
  forum:
    link_aria_label: "Opened by {count} {count, plural, one{person} other{people}}"
    link_tooltip: "{count} {count, plural, one{person has opened this} other{people have opened this}}"
    popular_links_title: "Popular links"
  admin:
    settings:
      title: "Link Clicks"
      enabled_label: "Show click counts on links"
      enabled_help: "When off, links render as normal — no badge, no tracking."
      track_internal_label: "Count clicks on links to this forum"
      track_internal_help: "Off by default for new installations to keep counts focused on outbound resources."
      retention_days_label: "Keep raw click events for"
      retention_days_help: "Older events are summarised into daily totals and removed."
      dedup_window_label: "Don't count the same person twice within"
    analytics:
      title: "Click analytics"
      empty: "No clicks yet."
      column_url: "Link"
      column_count: "Clicks"
      column_unique: "Unique people"
      column_first_seen: "First click"
      column_last_seen: "Last click"
      export_csv: "Export CSV"
```

**`locale/vi.yml`** — copy do người Việt viết, không dịch máy:
```yaml
datlechin-link-clicks:
  forum:
    link_aria_label: "{count} người đã mở"
    link_tooltip: "{count} người đã mở link này"
    popular_links_title: "Link nổi bật"
  admin:
    settings:
      title: "Link Clicks"
      enabled_label: "Hiện số người mở mỗi link"
      enabled_help: "Khi tắt, link hiển thị như bình thường — không có nhãn, không ghi nhận."
      track_internal_label: "Đếm cả link trỏ về diễn đàn này"
      track_internal_help: "Mặc định tắt để chỉ tập trung vào link ra ngoài."
      retention_days_label: "Giữ lại lịch sử click trong"
      retention_days_help: "Sau thời hạn này, các click được gộp lại theo ngày và xoá bản ghi gốc."
      dedup_window_label: "Không tính trùng cùng một người trong"
    analytics:
      title: "Báo cáo click"
      empty: "Chưa có click nào."
      column_url: "Link"
      column_count: "Click"
      column_unique: "Số người"
      column_first_seen: "Click đầu"
      column_last_seen: "Click gần nhất"
      export_csv: "Xuất CSV"
```

**Quy tắc microcopy:**
- Không dùng "lượt", "lần" trừ khi cần thiết (nghe sách vở). "{count} người đã mở" nghe đời thường hơn "17 lần được click".
- Không dùng dấu `()` để giải thích. Help text dài thì viết câu hoàn chỉnh ở `*_help`.
- Không dùng emoji trong copy.
- Số lớn: hiển thị nguyên bản đến 999, từ 1000 trở lên format `1.2k`/`12k` (helper `flarum/common/utils/abbreviateNumber` đã có).

### 9.6 Phân vùng class CSS

Tất cả class CSS của extension prefix `LinkClicks-` (PascalCase-Kebab — convention của Flarum core). Không dùng class chung chung như `.badge`, `.counter`, `.popular`. Không pollute global namespace.

---

## 10. Settings

| Key | Default | Description |
|---|---|---|
| `linkClickCounter.enabled` | `true` | Master switch |
| `linkClickCounter.trackInternal` | `true` | Track links to this forum's own host |
| `linkClickCounter.minDisplayCount` | `1` | Hide badge below this count |
| `linkClickCounter.dedupWindowHours` | `24` | Per-user/IP dedup window |
| `linkClickCounter.eventRetentionDays` | `90` | Raw event log retention |
| `linkClickCounter.botUserAgents` | `[curl, wget, Googlebot, ...]` | Comma-separated UA fragments to ignore |

---

## 11. Privacy & GDPR

- **Anonymous users:** only `sha256(ip + daily_salt)` stored, never raw IP. Salt rotates daily so cross-day correlation isn't possible.
- **Logged-in users:** `user_id` stored only for the dedup window; raw event rolled into anonymous daily aggregate after 90 days (configurable).
- **User Agent:** only a bucketed family (`chrome`/`firefox`/`safari`/`bot`/`other`), not the full string.
- **GDPR data export:** the extension hooks into Flarum's data-export endpoint (provided by `flarum-gdpr`) to include the user's recent click events.
- **GDPR erasure:** on user delete, `user_id` is set to NULL via FK rule; `ip_hash` was never linked.
- **DNT header:** if `DNT: 1` is sent, the redirect still happens but no event row is written (configurable; default = honor DNT).
- The PRD explicitly avoids adding fingerprinting fallbacks (no canvas, no localStorage tokens).

---

## 12. Performance

### Hot path: every post render
- Formatter rewrite is O(links per post). Avoid DB calls in the rewrite itself — `data-clicks` values come from a single eager-loaded `post.linkClicks` join, not per-link queries.
- `clicks_count` is materialized on `post_links`, so render is a join, not an aggregate.

### Hot path: every click
- Single insert + single update inside one transaction. `(post_link_id, user_id|ip_hash)` lookup for the 24h dedup uses a composite index; the dedup probe is `EXISTS (… WHERE clicked_at > NOW() - INTERVAL 24 HOUR)` against that index.

### Capacity sketch
- 1M posts × 3 links/post avg × 5 clicks/link/lifetime = 15M event rows. With retention rollup at 90d, steady state is much smaller. Comfortable on default MySQL/MariaDB/PostgreSQL.

### Caching
- No HTTP cache on `/lcc/track` (must hit server every time).
- Popular-links widget response: cache 60s per discussion.

---

## 13. Build & Rollout Phases

The MVP commits to all four user-selected scope items, but they can ship in slices:

**Phase 1 — Core badge (target: week 1–2)**
- Migrations, models, formatter rewrite, redirect endpoint, anti-abuse, badge CSS.
- Ship as v0.1 to `discuss.flarum.org` extension index.

**Phase 2 — Real-time (target: week 3)**
- Realtime broadcast, frontend subscriber.
- Released as v0.2.

**Phase 3 — Popular links widget (target: week 4)**
- Sidebar component + API endpoint.
- v0.3.

**Phase 4 — Admin analytics (target: week 5–6)**
- Admin page, CSV export.
- v0.4 → v1.0 once stable for ~2 weeks on a test forum.

---

## 14. Success Metrics

After 30 days on a real forum:
- ≥ 60% of posts containing links accumulate at least one counted click. (Below this = badge isn't surfacing useful info.)
- p95 latency added by `/lcc/track` redirect < 80ms.
- 0 PII-related incidents (no raw IPs found in logs/DB).
- ≥ 1 admin reports finding the analytics page useful for moderation or partnerships.

---

## 15. Open Questions

1. **Tag-level opt-out?** Should admins be able to disable the counter on a per-tag basis (e.g., spam-prone Marketplace tag)? *Lean: yes, low effort, defer to phase 4.*
2. **Migration for existing posts.** When the extension is enabled on a forum with millions of existing posts, do we backfill `post_links` rows for all of them, or only on next post-render? *Lean: lazy backfill on render — avoids giant migration on install.*
3. **Counted-click rule for trusted bots.** Should we explicitly count clicks from logged-in API tokens belonging to integrations (e.g., Slack relay)? *Lean: no by default; add an admin allowlist if requested.*
4. **Internal-link slug-mismatch problem.** Discourse fails on this. We can sidestep by matching internal links against discussion ID rather than slug. *Action: confirm during phase 1 implementation.*
5. **Realtime fallback when `flarum-realtime` is not installed.** Polling? Or just disable real-time gracefully? *Lean: graceful disable, document the dependency.*

---

## 16. Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| Open-redirect abuse | Med | High | HMAC-signed `u` parameter, validate against forum origin allowlist where possible |
| Click-count manipulation | High | Low | Per-user/IP dedup, author exclusion, raw event log for forensics |
| Adblocker false positives blocking `/lcc/track` | Low | Med | Use first-party path, no third-party requests, plain HTML `<a>` with real href |
| DB bloat on busy forums | Med | Med | 90-day retention + daily rollup + monitoring on row count |
| SEO concerns from rewritten links | Low | Med | We rewrite at render-time only for browser HTML; raw post body in DB stays unchanged. Crawlers see redirected hrefs but the redirect is a clean 302 to the canonical URL |

---

## 17. Appendix: Comparison to Discourse

| Aspect | Discourse | This extension |
|---|---|---|
| Tracking method | Server redirect | Server redirect (parity) |
| Anti-abuse 24h rule | ✅ | ✅ (parity) |
| Author excluded | ✅ | ✅ (parity) |
| Real-time updates | ❌ requires reload | ✅ via `flarum-realtime` |
| Onebox click counting | ⚠️ broken | N/A (no onebox) |
| Internal-link counting | ⚠️ slug-mismatch bugs | Match by discussion ID |
| Admin analytics page | partial | ✅ dedicated page + CSV |
| GDPR-aware retention | unclear | ✅ explicit 90d + IP-hash with daily salt |
