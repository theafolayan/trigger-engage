<?php

use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\Contact;
use App\Models\Delivery;
use App\Models\Template;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('workspace has many contacts', function () {
    $workspace = Workspace::factory()->has(Contact::factory()->count(2))->create();

    expect($workspace->contacts)->toHaveCount(2);
});

it('contact belongs to workspace', function () {
    $workspace = Workspace::factory()->create();
    $contact = Contact::factory()->for($workspace)->create();

    expect($contact->workspace->is($workspace))->toBeTrue();
});

it('delivery belongs to contact template automation', function () {
    $workspace = Workspace::factory()->create();
    $contact = Contact::factory()->for($workspace)->create();
    $template = Template::factory()->for($workspace)->create();
    $automation = Automation::factory()->for($workspace)->create();

    $delivery = Delivery::factory()
        ->for($workspace)
        ->for($contact)
        ->for($template)
        ->for($automation)
        ->create();

    expect($delivery->contact->is($contact))->toBeTrue()
        ->and($delivery->template->is($template))->toBeTrue()
        ->and($delivery->automation->is($automation))->toBeTrue();
});

it('automation steps have unique uid', function () {
    $automation = Automation::factory()->create();
    $uid = 'step-uid';

    AutomationStep::factory()->for($automation)->create(['uid' => $uid]);

    expect(fn () => AutomationStep::factory()->for($automation)->create(['uid' => $uid]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
