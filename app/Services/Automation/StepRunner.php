<?php

declare(strict_types=1);

namespace App\Services\Automation;

use App\Enums\AutomationStepKind;
use App\Models\AutomationStep;
use App\Models\Contact;
use App\Models\PushSetting;
use App\Services\Push\PushManager;
use Illuminate\Support\Facades\Blade;

class StepRunner
{
    public function __construct(private PushManager $pushManager) {}

    public function run(AutomationStep $step, Contact $contact): void
    {
        if ($step->kind !== AutomationStepKind::SendPushNotification) {
            return;
        }

        $config = $step->config ?? [];

        $context = ['contact' => $contact];

        $title = Blade::render($config['template_title'] ?? '', $context);
        $body = Blade::render($config['template_body'] ?? '', $context);
        $data = $config['data'] ?? [];

        $workspaceId = $contact->workspace_id;

        $setting = PushSetting::where('workspace_id', $workspaceId)->first();

        if ($setting === null) {
            return;
        }

        $driver = $this->pushManager->driver($setting->driver, $setting);

        $driver->send($contact, [
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }
}

