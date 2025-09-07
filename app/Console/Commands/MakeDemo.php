<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AutomationStepKind;
use App\Models\Automation;
use App\Models\AutomationStep;
use App\Models\Contact;
use App\Models\SmtpSetting;
use App\Models\Template;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeDemo extends Command
{
    protected $signature = 'make:demo';

    protected $description = 'Seed demo workspace and sample data';

    public function handle(): int
    {
        $workspace = Workspace::create([
            'name' => 'Demo Workspace',
            'slug' => 'demo',
        ]);

        $user = User::create([
            'workspace_id' => $workspace->id,
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'password' => 'password',
        ]);

        $token = $user->createToken('api')->plainTextToken;

        SmtpSetting::create([
            'workspace_id' => $workspace->id,
            'host' => '127.0.0.1',
            'port' => 1025,
            'username' => '',
            'password_encrypted' => encrypt(''),
            'encryption' => null,
            'from_name' => 'Demo',
            'from_email' => 'demo@example.com',
            'reply_to' => null,
            'meta' => [],
            'is_active' => true,
        ]);

        $contact = Contact::create([
            'workspace_id' => $workspace->id,
            'email' => 'contact@example.com',
            'first_name' => 'Demo',
        ]);

        $template = Template::create([
            'workspace_id' => $workspace->id,
            'name' => 'Welcome',
            'subject' => 'Welcome {{ $contact->first_name }}',
            'html' => '<p>Hello {{ $contact->first_name }}</p>',
            'text' => 'Hello {{ $contact->first_name }}',
        ]);

        $automation = Automation::create([
            'workspace_id' => $workspace->id,
            'name' => 'Welcome Flow',
            'trigger_event' => 'signup',
            'is_active' => true,
            'timezone' => 'UTC',
        ]);

        AutomationStep::create([
            'automation_id' => $automation->id,
            'uid' => (string) Str::uuid(),
            'kind' => AutomationStepKind::SendEmail->value,
            'config' => ['template_id' => $template->id],
        ]);

        $this->info('Demo data seeded.');
        $this->line("API token: {$token}");
        $this->line('');
        $this->line('Example curl to trigger event:');
        $this->line('curl -X POST http://localhost/api/v1/events \\');
        $this->line("  -H \"Authorization: Bearer {$token}\" \\");
        $this->line('  -H "X-Workspace: demo" \\');
        $this->line('  -H "Content-Type: application/json" \\');
        $this->line("  -d '{\"name\":\"signup\",\"contact_email\":\"{$contact->email}\"}'");

        return self::SUCCESS;
    }
}
