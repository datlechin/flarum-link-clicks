<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks;

use Datlechin\LinkClicks\Api\Controller\ExportLinkClickStatsController;
use Datlechin\LinkClicks\Api\Controller\LinkClickHeatmapController;
use Datlechin\LinkClicks\Api\Controller\LinkClickTimeSeriesController;
use Datlechin\LinkClicks\Api\Controller\ListLinkClickDomainsController;
use Datlechin\LinkClicks\Api\Controller\ListLinkClickersController;
use Datlechin\LinkClicks\Api\Controller\ListLinkClickStatsController;
use Datlechin\LinkClicks\Api\Controller\ListPopularLinksController;
use Datlechin\LinkClicks\Api\Controller\ListUserClickTrailController;
use Datlechin\LinkClicks\Api\Controller\ListUserPopularLinksController;
use Datlechin\LinkClicks\Api\Controller\TestWebhookController;
use Datlechin\LinkClicks\Console\BackfillCommand;
use Datlechin\LinkClicks\Console\DailySchedule;
use Datlechin\LinkClicks\Console\DetectAnomaliesCommand;
use Datlechin\LinkClicks\Console\PurgeEventsCommand;
use Datlechin\LinkClicks\Console\ReconcileCountsCommand;
use Datlechin\LinkClicks\Console\SendDigestCommand;
use Datlechin\LinkClicks\Console\WeeklySchedule;
use Datlechin\LinkClicks\Event\ClickCounted;
use Datlechin\LinkClicks\Event\ClickRecorded;
use Datlechin\LinkClicks\Formatter\ConfigureUrlTemplate;
use Datlechin\LinkClicks\Formatter\RewriteLinksForTracking;
use Datlechin\LinkClicks\Http\Controller\TrackClickController;
use Datlechin\LinkClicks\Listener\BroadcastWhenClickCounted;
use Datlechin\LinkClicks\Listener\DispatchClickWebhook;
use Datlechin\LinkClicks\Listener\SyncPostLinks;
use Datlechin\LinkClicks\Provider\LinkClicksProvider;
use Datlechin\LinkClicks\Service\TagOptOut;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Extend;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Revised;
use Flarum\Post\Post;

return [
    (new Extend\ServiceProvider())
        ->register(LinkClicksProvider::class),

    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Model(Post::class))
        ->hasMany('links', PostLink::class, 'post_id', 'id')
        ->cast('link_clicks_disabled', 'boolean'),

    (new Extend\Formatter())
        ->configure(ConfigureUrlTemplate::class)
        ->render(RewriteLinksForTracking::class),

    (new Extend\Event())
        ->listen(Posted::class, SyncPostLinks::class)
        ->listen(Revised::class, SyncPostLinks::class)
        ->listen(ClickRecorded::class, DispatchClickWebhook::class),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-realtime', fn () => [
            (new Extend\Event())
                ->listen(ClickCounted::class, BroadcastWhenClickCounted::class),
        ])
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new \Flarum\Gdpr\Extend\UserData())
                ->addType(\Datlechin\LinkClicks\Gdpr\LinkClicks::class),
        ])
        ->whenExtensionEnabled('flarum-tags', fn () => [
            (new Extend\ApiResource(\Flarum\Tags\Api\Resource\TagResource::class))
                ->fields(fn () => [
                    Schema\Boolean::make('linkClicksDisabled')
                        ->get(fn ($tag) => resolve(TagOptOut::class)->isTagDisabled((int) $tag->id))
                        ->writable(fn ($tag, $context) => $context->getActor()->isAdmin())
                        ->save(fn ($tag, $value) => resolve(TagOptOut::class)->setTagDisabled((int) $tag->id, (bool) $value)),
                ]),
        ]),

    (new Extend\Routes('forum'))
        ->get('/lcc/track', 'datlechin-link-clicks.track', TrackClickController::class),

    (new Extend\Routes('api'))
        ->get('/discussions/{id}/popular-links', 'datlechin-link-clicks.popular', ListPopularLinksController::class)
        ->get('/users/{id}/popular-links', 'datlechin-link-clicks.user-popular', ListUserPopularLinksController::class)
        ->get('/users/{id}/click-trail', 'datlechin-link-clicks.user-trail', ListUserClickTrailController::class)
        ->get('/link-click-stats', 'datlechin-link-clicks.stats', ListLinkClickStatsController::class)
        ->get('/link-click-stats/domains', 'datlechin-link-clicks.stats.domains', ListLinkClickDomainsController::class)
        ->get('/link-click-stats/heatmap', 'datlechin-link-clicks.stats.heatmap', LinkClickHeatmapController::class)
        ->get('/link-click-stats/timeseries', 'datlechin-link-clicks.stats.timeseries', LinkClickTimeSeriesController::class)
        ->get('/link-click-stats/export', 'datlechin-link-clicks.stats.export', ExportLinkClickStatsController::class)
        ->get('/link-click-stats/{id}/clickers', 'datlechin-link-clicks.stats.clickers', ListLinkClickersController::class)
        ->post('/link-click-stats/webhook/test', 'datlechin-link-clicks.webhook.test', TestWebhookController::class),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->endpoint(
            [Endpoint\Index::class, Endpoint\Show::class],
            function (Endpoint\Index|Endpoint\Show $endpoint): Endpoint\Endpoint {
                return $endpoint->eagerLoad(['links']);
            },
        )
        ->fields(fn () => [
            Schema\Boolean::make('linkClicksDisabled')
                ->writable(fn (Post $post, Context $context) => $context->getActor()->can('edit', $post))
                ->set(function (Post $post, mixed $value): void {
                    $post->setAttribute('link_clicks_disabled', (bool) $value);
                })
                ->save(fn (Post $post) => resolve(SyncPostLinks::class)->sync($post, deleteOrphans: false)),
        ]),

    (new Extend\Console())
        ->command(BackfillCommand::class)
        ->command(PurgeEventsCommand::class)
        ->command(ReconcileCountsCommand::class)
        ->command(DetectAnomaliesCommand::class)
        ->command(SendDigestCommand::class)
        ->schedule(PurgeEventsCommand::class, DailySchedule::class)
        ->schedule(ReconcileCountsCommand::class, DailySchedule::class)
        ->schedule(DetectAnomaliesCommand::class, DailySchedule::class)
        ->schedule(SendDigestCommand::class, WeeklySchedule::class),

    (new Extend\Settings())
        ->default('datlechin-link-clicks.enabled', true)
        ->default('datlechin-link-clicks.track_internal', false)
        ->default('datlechin-link-clicks.min_display_count', 1)
        ->default('datlechin-link-clicks.dedup_window_hours', 24)
        ->default('datlechin-link-clicks.honor_dnt', true)
        ->default('datlechin-link-clicks.event_retention_days', 90)
        ->default('datlechin-link-clicks.bot_user_agents', '')
        ->default('datlechin-link-clicks.tracking_params_strip', '')
        ->default('datlechin-link-clicks.attachment_path_prefixes', '')
        ->default('datlechin-link-clicks.domain_blocklist', '')
        ->default('datlechin-link-clicks.confirm_external_clicks', false)
        ->serializeToForum('linkClicksConfirmExternal', 'datlechin-link-clicks.confirm_external_clicks', 'boolval')
        ->default('datlechin-link-clicks.skip_guests', false)
        ->default('datlechin-link-clicks.open_in_new_window', false)
        ->default('datlechin-link-clicks.webhook_enabled', false)
        ->default('datlechin-link-clicks.webhook_url', '')
        ->default('datlechin-link-clicks.webhook_secret', '')
        ->default('datlechin-link-clicks.anomaly_threshold_ratio', 10)
        ->default('datlechin-link-clicks.anomaly_min_clicks', 20)
        ->default('datlechin-link-clicks.digest_enabled', false)
        ->serializeToForum('linkClicksMinDisplay', 'datlechin-link-clicks.min_display_count', 'intval'),

    (new Extend\User())
        ->registerPreference('datlechin-link-clicks.opt_out', 'boolval', false),
];
