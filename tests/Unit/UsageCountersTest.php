<?php

declare(strict_types=1);

use App\Models\Workspace;
use App\Models\UsageCounter;
use App\Services\UsageTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('increments counters and resets monthly', function (): void {
    Carbon::setTestNow(Carbon::create(2024, 1, 15));
    $workspace = Workspace::factory()->create();
    $tracker = new UsageTracker();

    $tracker->recordEmailsSent($workspace);
    $tracker->recordEventsIngested($workspace, 2);
    $tracker->recordContactsCreated($workspace);

    $counter = UsageCounter::first();
    expect($counter->emails_sent)->toBe(1)
        ->and($counter->events_ingested)->toBe(2)
        ->and($counter->contacts_created)->toBe(1);

    Carbon::setTestNow(Carbon::create(2024, 2, 1));
    $tracker->recordEmailsSent($workspace);
    $newCounter = UsageCounter::where('month', 2)->first();

    expect($newCounter->emails_sent)->toBe(1)
        ->and($newCounter->events_ingested)->toBe(0);
});
