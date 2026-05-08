<?php

/*
 * This file is part of datlechin/flarum-link-clicks.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\LinkClicks\Tests\Unit\Service;

use Datlechin\LinkClicks\Service\TagOptOut;
use Flarum\Testing\unit\TestCase;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class TagOptOutTest extends TestCase
{
    private function dbWithoutTable(): ConnectionInterface
    {
        $schema = Mockery::mock(SchemaBuilder::class);
        $schema->shouldReceive('hasTable')->with('link_clicks_tag_settings')->andReturn(false);

        $db = Mockery::mock(ConnectionInterface::class);
        $db->shouldReceive('getSchemaBuilder')->andReturn($schema);

        return $db;
    }

    private function dbWithTable(Builder $tableBuilder): ConnectionInterface
    {
        $schema = Mockery::mock(SchemaBuilder::class);
        $schema->shouldReceive('hasTable')->with('link_clicks_tag_settings')->andReturn(true);

        $db = Mockery::mock(ConnectionInterface::class);
        $db->shouldReceive('getSchemaBuilder')->andReturn($schema);
        $db->shouldReceive('table')->with('link_clicks_tag_settings')->andReturn($tableBuilder);

        return $db;
    }

    #[Test]
    public function isTagDisabled_returns_false_when_table_missing(): void
    {
        $service = new TagOptOut($this->dbWithoutTable());

        $this->assertFalse($service->isTagDisabled(1));
    }

    #[Test]
    public function isDiscussionOptedOut_returns_false_when_table_missing(): void
    {
        $service = new TagOptOut($this->dbWithoutTable());

        $this->assertFalse($service->isDiscussionOptedOut(1));
    }

    #[Test]
    public function setTagDisabled_no_ops_when_table_missing(): void
    {
        // Mockery would explode if any DB write was attempted; the absence
        // of an `updateOrInsert` expectation is the assertion.
        $service = new TagOptOut($this->dbWithoutTable());
        $service->setTagDisabled(5, true);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function isTagDisabled_returns_true_when_row_marked_disabled(): void
    {
        $tableBuilder = Mockery::mock(Builder::class);
        $tableBuilder->shouldReceive('where')->with('tag_id', 7)->andReturnSelf();
        $tableBuilder->shouldReceive('value')->with('disabled')->andReturn(1);

        $service = new TagOptOut($this->dbWithTable($tableBuilder));

        $this->assertTrue($service->isTagDisabled(7));
    }

    #[Test]
    public function setTagDisabled_true_upserts_a_row(): void
    {
        $tableBuilder = Mockery::mock(Builder::class);
        $tableBuilder->shouldReceive('updateOrInsert')
            ->once()
            ->with(['tag_id' => 9], ['disabled' => true]);

        $service = new TagOptOut($this->dbWithTable($tableBuilder));
        $service->setTagDisabled(9, true);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function setTagDisabled_false_deletes_the_row(): void
    {
        $tableBuilder = Mockery::mock(Builder::class);
        $tableBuilder->shouldReceive('where')->with('tag_id', 9)->andReturnSelf();
        $tableBuilder->shouldReceive('delete')->once();

        $service = new TagOptOut($this->dbWithTable($tableBuilder));
        $service->setTagDisabled(9, false);

        $this->addToAssertionCount(1);
    }
}
