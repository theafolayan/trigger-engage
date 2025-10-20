<?php

use App\Models\Account;
use App\Models\Plan;
use App\Models\TwitterAccount;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Str;
use Predis\Client;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->in('Unit/Twitter');

uses()->beforeEach(function (): void {
    (new Client())->flushdb();
});

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createTwitterAccountForPlanName(string $planName, array $attributes = []): TwitterAccount
{
    $plan = Plan::factory()->create(['name' => $planName]);

    return createTwitterAccountForPlan($plan, $attributes);
}

function createTwitterAccountForPlan(Plan $plan, array $attributes = []): TwitterAccount
{
    $account = Account::factory()->for($plan, 'plan')->create();
    $workspace = Workspace::factory()->for($account, 'account')->create();
    $user = User::factory()->for($workspace)->create([
        'account_id' => $account->id,
    ]);

    $defaults = [
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'twitter_id' => (string) Str::random(12),
        'username' => Str::lower(Str::random(8)),
        'name' => 'Test Twitter Account',
        'profile_image_url' => null,
        'scopes' => [],
        'access_token' => 'token-'.Str::random(16),
        'refresh_token' => null,
        'token_expires_at' => now()->addHour(),
        'connected_at' => now(),
        'disconnected_at' => null,
    ];

    return TwitterAccount::create(array_merge($defaults, $attributes));
}

function something()
{
    // ..
}
