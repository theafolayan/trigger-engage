<?php

declare(strict_types=1);

use App\Models\Automation;
use App\Models\Contact;
use App\Models\Template;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

it('seeder creates demo workspace and prints curl', function (): void {
    Artisan::call('make:demo');
    $output = Artisan::output();

    expect($output)->toContain('curl')
        ->and(Workspace::where('slug', 'demo')->exists())->toBeTrue()
        ->and(Contact::count())->toBe(1)
        ->and(Template::count())->toBe(1)
        ->and(Automation::count())->toBe(1);
});
