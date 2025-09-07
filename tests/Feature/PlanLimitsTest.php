<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Plan;
use App\Models\Workspace;
use App\Services\PlanEnforcer;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('caps free plan email sends', function (): void {
    $free = Plan::where('name', 'Free')->first();
    $account = Account::factory()->create(['subscription_plan_id' => $free->id]);
    $workspace = Workspace::factory()->create(['account_id' => $account->id]);

    $enforcer = new PlanEnforcer(new \App\Services\UsageTracker());

    $enforcer->recordEmailsSent($workspace, 2000);
    $enforcer->recordEmailsSent($workspace, 1);
})->throws(RuntimeException::class);

it('allows pro plan higher quota', function (): void {
    $pro = Plan::where('name', 'Pro')->first();
    $account = Account::factory()->create(['subscription_plan_id' => $pro->id]);
    $workspace = Workspace::factory()->create(['account_id' => $account->id]);

    $enforcer = new PlanEnforcer(new \App\Services\UsageTracker());

    $enforcer->recordEmailsSent($workspace, 5000);

    expect(true)->toBeTrue();
});
