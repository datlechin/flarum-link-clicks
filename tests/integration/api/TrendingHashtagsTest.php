<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Tests\Integration\Api;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The trending endpoint reads the daily rollup, which deliberately never
 * covers today, so "recent" is the last fully counted day measured against the
 * six before it.
 */
class TrendingHashtagsTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-link-clicks');

        $this->prepareDatabase([
            User::class => [$this->normalUser()],
            Discussion::class => [
                ['id' => 1, 'title' => 'test', 'created_at' => Carbon::now(), 'last_posted_at' => Carbon::now(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'number' => 1, 'created_at' => Carbon::now(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>x</p></t>', 'is_private' => 0],
            ],
            'post_links' => [
                $this->hashtagRow(1, 'steady', 1),
                $this->hashtagRow(2, 'surging', 2),
            ],
        ]);
    }

    #[Test]
    public function an_empty_rollup_returns_nothing_rather_than_failing(): void
    {
        $body = $this->get();

        $this->assertSame([], $body);
    }

    /**
     * The reason for ranking on velocity at all: a tag with a long history of
     * heavy traffic should lose to one that just took off, even though the
     * established tag has far more clicks in total.
     */
    #[Test]
    public function a_surging_hashtag_outranks_a_busier_but_steady_one(): void
    {
        $this->seedRollup(postLinkId: 1, perDay: 40, recent: 40);
        $this->seedRollup(postLinkId: 2, perDay: 1, recent: 20);

        $body = $this->get();

        $this->assertCount(2, $body);
        $this->assertSame('#surging', $body[0]['label']);
        $this->assertSame('#steady', $body[1]['label']);
    }

    /**
     * Without smoothing a brand-new hashtag divides by a zero baseline, and a
     * single click would outrank everything established.
     */
    #[Test]
    public function a_brand_new_hashtag_does_not_run_away_with_the_ranking(): void
    {
        $this->seedRollup(postLinkId: 1, perDay: 30, recent: 90);
        $this->seedRollup(postLinkId: 2, perDay: 0, recent: 6);

        $body = $this->get();

        $this->assertSame('#steady', $body[0]['label'], 'a real surge should still beat a tag with no history at all');
    }

    #[Test]
    public function a_hashtag_below_the_floor_never_trends(): void
    {
        $this->setting('datlechin-link-clicks.trending_min_clicks', 10);
        $this->seedRollup(postLinkId: 2, perDay: 0, recent: 9);

        $this->assertSame([], $this->get());
    }

    #[Test]
    public function the_widget_can_be_turned_off(): void
    {
        $this->setting('datlechin-link-clicks.trending_enabled', false);
        $this->seedRollup(postLinkId: 2, perDay: 1, recent: 50);

        $this->assertSame([], $this->get());
    }

    /**
     * @return array<string, mixed>
     */
    private function hashtagRow(int $id, string $name, int $tagId): array
    {
        return [
            'id' => $id,
            'post_id' => 1,
            'discussion_id' => 1,
            'source' => 'tag_mention',
            'source_id' => $tagId,
            'url' => 'https://forum.test/t/'.$name,
            'label' => '#'.$name,
            'url_hash' => hash('sha256', 'tag_mention:'.$tagId),
            'is_internal' => 1,
            'clicks_count' => 0,
            'first_seen_at' => Carbon::now(),
        ];
    }

    private function seedRollup(int $postLinkId, int $perDay, int $recent): void
    {
        $rows = [[
            'date' => Carbon::yesterday()->toDateString(),
            'post_link_id' => $postLinkId,
            'count' => $recent,
        ]];

        // The six days before the one being measured form the baseline.
        for ($day = 2; $day <= 7; $day++) {
            if ($perDay > 0) {
                $rows[] = [
                    'date' => Carbon::yesterday()->subDays($day - 1)->toDateString(),
                    'post_link_id' => $postLinkId,
                    'count' => $perDay,
                ];
            }
        }

        $this->database()->table('link_click_daily')->insert($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function get(): array
    {
        $response = $this->send($this->request('GET', '/api/trending-hashtags', ['authenticatedAs' => 2]));

        $this->assertSame(200, $response->getStatusCode());

        return json_decode((string) $response->getBody(), true);
    }
}
