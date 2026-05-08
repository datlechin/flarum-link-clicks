<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Console;

use Carbon\Carbon;
use Datlechin\LinkClicks\Listener\SyncPostLinks;
use Flarum\Foundation\Config;
use Flarum\Post\CommentPost;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;

/**
 * `php flarum link-clicks:seed-screenshots --force`.
 *
 * Dev-only. Wipes any prior demo-prefixed users / discussions and reseeds a
 * realistic-looking thread with a known click distribution so the README
 * screenshots can be captured without staging real traffic.
 *
 * Theme is TablePro because the package author runs tablepro.app and wants
 * the screenshots to feel like an actual product conversation.
 */
class SeedScreenshotsCommand extends Command
{
    protected $signature = 'link-clicks:seed-screenshots {--force : Confirm the destructive seed}';

    protected $description = 'Seed demo data for Link Clicks README screenshots (dev only).';

    public function handle(
        ConnectionInterface $db,
        Config $config,
        SyncPostLinks $sync,
    ): int {
        if (! $this->option('force')) {
            $this->error('Pass --force. This command wipes any users matching demo_* and discussions starting with [demo].');
            return self::FAILURE;
        }

        $forumHost = parse_url($config->url(), PHP_URL_HOST) ?: 'flarum.test';
        $forumScheme = parse_url($config->url(), PHP_URL_SCHEME) ?: 'http';

        $this->info("Seeding into {$forumScheme}://{$forumHost}");

        $this->wipePriorSeed($db);
        $userIds = $this->createUsers($db);
        [$discussionId, $internalPostUrl, $attachmentUrl] = $this->createDiscussionAndPosts(
            $db,
            $sync,
            $userIds,
            $forumScheme,
            $forumHost,
        );
        $totalClicks = $this->seedClicks($db, $userIds, $discussionId, $attachmentUrl);

        $this->newLine();
        $this->info("Seeded {$totalClicks} clicks across the demo discussion.");
        $this->line("View it at: {$internalPostUrl}");
        $this->line("Admin analytics: {$forumScheme}://{$forumHost}/admin#/extension/datlechin-link-clicks");
        $this->newLine();
        $this->comment('Run `php flarum cache:clear` to invalidate the popular-links widget cache.');

        return self::SUCCESS;
    }

    private function wipePriorSeed(ConnectionInterface $db): void
    {
        $this->line('Wiping prior demo state...');

        $priorUserIds = $db->table('users')->where('username', 'like', 'demo\_%')->pluck('id');
        $priorDiscussionIds = $db->table('discussions')->where('title', 'like', '[demo]%')->pluck('id');

        if ($priorDiscussionIds->isNotEmpty()) {
            $postIds = $db->table('posts')->whereIn('discussion_id', $priorDiscussionIds)->pluck('id');
            $linkIds = $db->table('post_links')->whereIn('post_id', $postIds)->pluck('id');
            $db->table('link_click_events')->whereIn('post_link_id', $linkIds)->delete();
            $db->table('post_links')->whereIn('id', $linkIds)->delete();
            $db->table('posts')->whereIn('id', $postIds)->delete();
            $db->table('discussions')->whereIn('id', $priorDiscussionIds)->delete();
        }

        if ($priorUserIds->isNotEmpty()) {
            $db->table('link_click_events')->whereIn('user_id', $priorUserIds)->delete();
            $db->table('users')->whereIn('id', $priorUserIds)->delete();
        }
    }

    /**
     * @return array<string, int>
     */
    private function createUsers(ConnectionInterface $db): array
    {
        $this->line('Creating demo users...');

        $now = Carbon::now();
        $seeds = [
            ['username' => 'demo_duy', 'email' => 'duy@example.test'],
            ['username' => 'demo_phuong', 'email' => 'phuong@example.test'],
            ['username' => 'demo_hoang', 'email' => 'hoang@example.test'],
            ['username' => 'demo_mai', 'email' => 'mai@example.test'],
            ['username' => 'demo_minh', 'email' => 'minh@example.test'],
        ];

        $ids = [];
        foreach ($seeds as $u) {
            $ids[$u['username']] = $db->table('users')->insertGetId([
                'username' => $u['username'],
                'email' => $u['email'],
                'is_email_confirmed' => 1,
                'password' => password_hash('demo-password-not-for-prod', PASSWORD_BCRYPT),
                'joined_at' => $now->copy()->subDays(rand(30, 200)),
                'last_seen_at' => $now->copy()->subHours(rand(1, 48)),
                'discussion_count' => 0,
                'comment_count' => 0,
            ]);
        }

        return $ids;
    }

    /**
     * @param array<string, int> $userIds
     * @return array{0: int, 1: string, 2: string}
     */
    private function createDiscussionAndPosts(
        ConnectionInterface $db,
        SyncPostLinks $sync,
        array $userIds,
        string $forumScheme,
        string $forumHost,
    ): array {
        $this->line('Creating discussion and posts...');

        $opUserId = $userIds['demo_duy'];
        $opCreatedAt = Carbon::now()->subDays(6);

        $discussionId = $db->table('discussions')->insertGetId([
            'title' => '[demo] Best Postgres GUI in 2026: TablePro vs DBeaver vs DataGrip',
            'slug' => 'best-postgres-gui-2026',
            'user_id' => $opUserId,
            'comment_count' => 0,
            'participant_count' => 0,
            'created_at' => $opCreatedAt,
            'last_posted_at' => $opCreatedAt,
            'last_posted_user_id' => $opUserId,
        ]);

        $internalPostUrl = "{$forumScheme}://{$forumHost}/d/{$discussionId}-best-postgres-gui-2026";
        $attachmentUrl = "{$forumScheme}://{$forumHost}/assets/files/postgres-gui-comparison.pdf";

        $posts = $this->postSeeds($opCreatedAt, $internalPostUrl, $attachmentUrl);

        $firstPostId = null;
        $postNumber = 0;

        foreach ($posts as $idx => $p) {
            $postNumber++;
            $userId = $userIds[$p['user']];

            $postId = $db->table('posts')->insertGetId([
                'discussion_id' => $discussionId,
                'number' => $postNumber,
                'created_at' => $p['created_at'],
                'user_id' => $userId,
                'type' => 'comment',
                'content' => '<t></t>',
                'is_approved' => 1,
            ]);

            $posts[$idx]['post_id'] = $postId;

            $post = CommentPost::query()->findOrFail($postId);
            // Setting `$post->content = "raw markdown"` calls
            // setContentAttribute which runs the formatter once. Don't
            // pre-parse, otherwise the XML gets fed back through the parser
            // and stored double-encoded.
            $post->content = $p['content'];
            $post->save();

            if ($firstPostId === null) {
                $firstPostId = $postId;
            }

            // Call the listener directly instead of dispatching a Posted
            // event, so realtime / notification listeners that need a live
            // Pusher server don't crash the seed when one isn't running.
            $sync->sync($post, deleteOrphans: false);
        }

        $lastPost = end($posts);
        $db->table('discussions')->where('id', $discussionId)->update([
            'comment_count' => $postNumber,
            'participant_count' => count(array_unique(array_column($posts, 'user'))),
            'first_post_id' => $firstPostId,
            'last_post_id' => $lastPost['post_id'] ?? null,
            'last_post_number' => $postNumber,
            'last_posted_at' => $lastPost['created_at'],
            'last_posted_user_id' => $userIds[$lastPost['user']],
        ]);

        return [$discussionId, $internalPostUrl, $attachmentUrl];
    }

    /**
     * @return list<array{user: string, created_at: Carbon, content: string}>
     */
    private function postSeeds(Carbon $opCreatedAt, string $internalPostUrl, string $attachmentUrl): array
    {
        return [
            [
                'user' => 'demo_duy',
                'created_at' => $opCreatedAt,
                'content' => "After three months on macOS I've finally settled on a daily driver for Postgres work. Quick rundown of what I tried and where I landed.\n\n[TablePro](https://tablepro.app/) ended up being the one I open every morning. Native Mac app (no Electron, no JVM warmup), free and open source, and the connection-scoped query history is the feature I didn't know I needed.\n\nHonourable mentions: [DBeaver](https://dbeaver.io/) is still the most feature-complete if you live in 14 different databases at once. [DataGrip](https://www.jetbrains.com/datagrip/) wins if you're already in JetBrains for the day job.\n\nAnyone else made the switch recently?",
            ],
            [
                'user' => 'demo_phuong',
                'created_at' => $opCreatedAt->copy()->addHours(4),
                'content' => "Switched from DBeaver to [TablePro](https://tablepro.app/) about two months ago. Big draw was that it's free and open source with no per-seat license math. [Download](https://tablepro.app/download) is a single dmg, opens instantly.\n\nThe [docs](https://tablepro.app/docs) walked me through importing my saved DBeaver connections in one go. Native drivers (no JDBC) is what made the difference for SQL Server connections that used to time out for me.\n\nThe [tablepro vs dbeaver](https://tablepro.app/compare/dbeaver) page is a fair side-by-side. They're honest about where DBeaver is still ahead.",
            ],
            [
                'user' => 'demo_hoang',
                'created_at' => $opCreatedAt->copy()->addDays(1)->addHours(2),
                'content' => "Counterpoint: still on [DataGrip](https://www.jetbrains.com/datagrip/) here. The refactoring tools, especially \"rename column and propagate to every saved query\", are genuinely irreplaceable for the kind of schema work I do.\n\nThat said I've been keeping an eye on [TablePro](https://tablepro.app/) since the AI assistant landed. Natural-language to SQL with schema context, and you can point it at a local model so nothing leaves the machine. The [tablepro vs datagrip](https://tablepro.app/compare/datagrip) breakdown is also worth reading.\n\nFor Postgres-native tooling, [the official site](https://www.postgresql.org/) is still the best place to start, and pgAdmin is right there.",
            ],
            [
                'user' => 'demo_mai',
                'created_at' => $opCreatedAt->copy()->addDays(2)->addHours(6),
                'content' => "I wrote a longer comparison piece if anyone wants the side-by-side: [postgres-gui-comparison.pdf]({$attachmentUrl}). Covers query speed, schema editing, query history, AI features, and platform support.\n\nMatches what people are saying. [TablePro](https://tablepro.app/) for solo Postgres on macOS, especially with the iCloud sync between Mac and iPhone (genuinely useful for on-call). [DBeaver](https://dbeaver.io/) for multi-database polyglot teams. [DataGrip](https://www.jetbrains.com/datagrip/) if you're already on JetBrains. [Postico](https://eggerapps.at/postico2/) if you want the cleanest UI.\n\nDon't bother with anything cloud-only unless your data is already there.",
            ],
            [
                'user' => 'demo_minh',
                'created_at' => $opCreatedAt->copy()->addDays(3)->addHours(3),
                'content' => "Late to this thread. [Postico](https://eggerapps.at/postico2/) deserves more credit. I keep it open alongside TablePro because the simple results-pane layout is hard to beat for quick lookups.\n\nGoing to test [TablePro docs](https://tablepro.app/docs) properly this week. The [Postgres client overview](https://tablepro.app/postgresql-client) page covers most of what we use day to day. The [blog](https://tablepro.app/blog) has a few release-notes posts that are actually informative.\n\nAlso the [original post]({$internalPostUrl}/2) called out the native architecture and that matches my experience: noticeably faster startup vs Electron-based clients.",
            ],
        ];
    }

    /**
     * @param array<string, int> $userIds
     */
    private function seedClicks(
        ConnectionInterface $db,
        array $userIds,
        int $discussionId,
        string $attachmentUrl,
    ): int {
        $this->line('Synthesizing click events...');

        // Group post_links by URL because the same URL can appear in
        // multiple posts within the discussion. Each post_link row gets its
        // own slice of the URL's click budget so individual post badges are
        // populated, weighted toward the first post (people scroll-read).
        $linksByUrl = $db->table('post_links')
            ->join('posts', 'posts.id', '=', 'post_links.post_id')
            ->where('posts.discussion_id', $discussionId)
            ->orderBy('posts.number')
            ->select('post_links.*', 'posts.number as post_number')
            ->get()
            ->groupBy('url');

        if ($linksByUrl->isEmpty()) {
            $this->error('SyncPostLinks listener did not create any post_links rows. Is the extension enabled?');
            return 0;
        }

        $distribution = [
            'https://tablepro.app/' => 26,
            'https://tablepro.app/docs' => 15,
            'https://tablepro.app/download' => 9,
            'https://tablepro.app/postgresql-client' => 7,
            'https://tablepro.app/compare/dbeaver' => 13,
            'https://tablepro.app/compare/datagrip' => 8,
            'https://tablepro.app/blog' => 5,
            'https://dbeaver.io/' => 7,
            'https://www.jetbrains.com/datagrip/' => 12,
            'https://www.postgresql.org/' => 18,
            'https://eggerapps.at/postico2/' => 4,
            $attachmentUrl => 11,
        ];

        $ipPool = ['203.0.113.41', '203.0.113.42', '203.0.113.43', '198.51.100.17', '198.51.100.18', '192.0.2.5', '192.0.2.6'];
        $uaPool = [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148',
        ];

        $now = Carbon::now();
        $opCreatedAt = $now->copy()->subDays(6);
        $totalClicks = 0;

        foreach ($distribution as $url => $count) {
            $matches = $linksByUrl->get($url) ?? $linksByUrl->get(rtrim($url, '/'));
            if (! $matches || $matches->isEmpty()) {
                $this->line("  (no post_link for {$url})");
                continue;
            }

            // Split the URL's click budget across every post that links to
            // it. Earlier posts (lower number) get a larger share because
            // people read top-down; the last fraction lands wherever rounds
            // dictate.
            $remaining = $count;
            $shares = $this->splitWeighted($count, $matches->count());

            foreach ($matches as $idx => $link) {
                $share = $shares[$idx] ?? 0;
                if ($share === 0) {
                    continue;
                }

                $rows = $this->buildClickRows($link->id, $share, $userIds, $ipPool, $uaPool, $opCreatedAt);

                foreach (array_chunk($rows, 100) as $chunk) {
                    $db->table('link_click_events')->insert($chunk);
                }

                $maxClickedAt = collect($rows)->max('clicked_at');
                $db->table('post_links')->where('id', $link->id)->update([
                    'clicks_count' => count($rows),
                    'last_clicked_at' => $maxClickedAt,
                ]);
            }

            $this->line(sprintf('  + %s -> %d clicks across %d post(s)', $url, $count, $matches->count()));
            $totalClicks += $count;
        }

        return $totalClicks;
    }

    /**
     * Distribute `$total` over `$slots` slots with a heavier weight on the
     * first slot (the OP), tapering off. Returns a list whose sum equals
     * `$total`.
     *
     * @return list<int>
     */
    private function splitWeighted(int $total, int $slots): array
    {
        if ($slots <= 0) {
            return [];
        }
        if ($slots === 1) {
            return [$total];
        }

        $weights = [];
        for ($i = 0; $i < $slots; $i++) {
            $weights[] = max(1, $slots - $i);
        }
        $weightSum = array_sum($weights);

        $shares = [];
        $allocated = 0;
        for ($i = 0; $i < $slots - 1; $i++) {
            $share = (int) floor($total * $weights[$i] / $weightSum);
            $shares[] = $share;
            $allocated += $share;
        }
        $shares[] = $total - $allocated;

        return $shares;
    }

    /**
     * @param array<string, int> $userIds
     * @param list<string> $ipPool
     * @param list<string> $uaPool
     * @return list<array<string, mixed>>
     */
    private function buildClickRows(int $postLinkId, int $count, array $userIds, array $ipPool, array $uaPool, Carbon $opCreatedAt): array
    {
        $loggedIn = (int) round($count * 0.65);
        $guest = (int) round($count * 0.25);
        $anon = $count - $loggedIn - $guest;

        $rows = [];

        for ($i = 0; $i < $loggedIn; $i++) {
            $rows[] = [
                'post_link_id' => $postLinkId,
                'user_id' => $userIds[array_rand($userIds)],
                'ip_address' => $ipPool[array_rand($ipPool)],
                'user_agent' => $uaPool[array_rand($uaPool)],
                'counted' => 1,
                'clicked_at' => $opCreatedAt->copy()->addMinutes(rand(60, 5 * 24 * 60)),
            ];
        }

        for ($i = 0; $i < $guest; $i++) {
            $rows[] = [
                'post_link_id' => $postLinkId,
                'user_id' => null,
                'ip_address' => $ipPool[array_rand($ipPool)],
                'user_agent' => $uaPool[array_rand($uaPool)],
                'counted' => 1,
                'clicked_at' => $opCreatedAt->copy()->addMinutes(rand(60, 5 * 24 * 60)),
            ];
        }

        for ($i = 0; $i < max(0, $anon); $i++) {
            $rows[] = [
                'post_link_id' => $postLinkId,
                'user_id' => null,
                'ip_address' => null,
                'user_agent' => null,
                'counted' => 1,
                'clicked_at' => $opCreatedAt->copy()->addMinutes(rand(60, 5 * 24 * 60)),
            ];
        }

        return $rows;
    }
}
