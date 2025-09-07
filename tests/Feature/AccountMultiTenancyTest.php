<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class);

it('prevents cross-account access to workspaces', function (): void {
    $plan = Plan::where('name', 'Free')->first();
    $accountA = Account::factory()->create(['subscription_plan_id' => $plan->id]);
    $accountB = Account::factory()->create(['subscription_plan_id' => $plan->id]);
    $workspaceA = Workspace::factory()->create(['account_id' => $accountA->id]);
    $workspaceB = Workspace::factory()->create(['account_id' => $accountB->id]);

    $userA = User::factory()->create([
        'workspace_id' => $workspaceA->id,
        'account_id' => $accountA->id,
        'password' => bcrypt('secret'),
    ]);

    $token = $userA->createToken('api')->plainTextToken;

    getJson('/api/v1/ping', [
        'Authorization' => 'Bearer '.$token,
        'X-Workspace' => $workspaceA->slug,
    ])->assertOk();

    getJson('/api/v1/ping', [
        'Authorization' => 'Bearer '.$token,
        'X-Workspace' => $workspaceB->slug,
    ])->assertStatus(403);
});
