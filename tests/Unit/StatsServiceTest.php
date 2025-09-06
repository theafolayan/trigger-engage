<?php

declare(strict_types=1);

use App\Enums\DeliveryStatus;
use App\Models\Contact;
use App\Models\Delivery;
use App\Models\Event;
use App\Models\Workspace;
use App\Services\StatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('calculates totals from data', function (): void {
    $workspace = Workspace::factory()->create();

    Contact::factory()->for($workspace)->count(2)->create();
    Contact::factory()->for($workspace)->create([
        'status' => \App\Enums\ContactStatus::Unsubscribed,
    ]);

    Delivery::factory()->for($workspace)->state([
        'status' => DeliveryStatus::Sent,
        'sent_at' => now(),
    ])->create();
    Delivery::factory()->for($workspace)->state([
        'status' => DeliveryStatus::Bounced,
        'sent_at' => now(),
    ])->create();

    Event::factory()->for($workspace)->create([
        'name' => 'open',
    ]);
    Event::factory()->for($workspace)->create([
        'name' => 'click',
    ]);

    $stats = app(StatsService::class)->totals($workspace);

    expect($stats['contacts']['total'])->toBe(3)
        ->and($stats['contacts']['active'])->toBe(2)
        ->and($stats['deliveries']['sent'])->toBe(2)
        ->and($stats['deliveries']['bounced'])->toBe(1)
        ->and($stats['rates']['open'])->toBeFloat()
        ->and($stats['rates']['click'])->toBeFloat();
});

it('groups deliveries per day', function (): void {
    $workspace = Workspace::factory()->create();

    Delivery::factory()->for($workspace)->state([
        'status' => DeliveryStatus::Sent,
        'sent_at' => now()->subDay(),
    ])->create();
    Delivery::factory()->for($workspace)->state([
        'status' => DeliveryStatus::Sent,
        'sent_at' => now(),
    ])->count(2)->create();

    $data = app(StatsService::class)->deliveriesPerDay($workspace);

    expect($data)->toHaveCount(2)
        ->and($data[now()->subDay()->format('Y-m-d')])->toBe(1)
        ->and($data[now()->format('Y-m-d')])->toBe(2);
});
