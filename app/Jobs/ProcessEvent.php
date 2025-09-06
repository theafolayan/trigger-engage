<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Event;
use App\Services\Automation\StepRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Event $event) {}

    public function handle(StepRunner $runner): void
    {
        $contact = $this->event->contact;
        if ($contact === null) {
            return;
        }

        $automations = $this->event->workspace->automations()->with('steps')->get();
        foreach ($automations as $automation) {
            foreach ($automation->steps as $step) {
                $runner->run($step, $contact);
            }
        }
    }
}
