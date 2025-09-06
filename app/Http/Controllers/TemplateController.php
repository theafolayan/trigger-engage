<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\TemplateTestMail;
use App\Models\Contact;
use App\Models\Event;
use App\Models\Template;
use App\Services\Mail\MailerResolver;
use App\Services\RateLimiter\TokenBucketLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\HttpFoundation\Response;

class TemplateController extends Controller
{
    public function index(): Response
    {
        $templates = Template::all();

        return response()->json([
            'data' => $templates->map(fn (Template $template) => $this->transform($template)),
        ]);
    }

    public function store(Request $request): Response
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'subject' => ['required', 'string'],
            'html' => ['required', 'string'],
            'text' => ['nullable', 'string'],
            'editor_meta' => ['nullable', 'array'],
        ]);

        $template = Template::create([
            'workspace_id' => currentWorkspace()->id,
            'name' => $data['name'],
            'subject' => $data['subject'],
            'html' => $data['html'],
            'text' => $data['text'] ?? null,
            'editor_meta' => $data['editor_meta'] ?? [],
        ]);

        return response()->json(['data' => $this->transform($template)], 201);
    }

    public function show(Template $template): Response
    {
        return response()->json(['data' => $this->transform($template)]);
    }

    public function update(Request $request, Template $template): Response
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string'],
            'subject' => ['sometimes', 'string'],
            'html' => ['sometimes', 'string'],
            'text' => ['sometimes', 'string', 'nullable'],
            'editor_meta' => ['sometimes', 'array'],
        ]);

        $template->update($data);

        return response()->json(['data' => $this->transform($template)]);
    }

    public function destroy(Template $template): Response
    {
        $template->delete();

        return response()->noContent();
    }

    public function preview(Request $request, Template $template): Response
    {
        $data = $request->validate([
            'contact_id' => ['nullable', 'integer'],
            'event_id' => ['nullable', 'integer'],
        ]);

        $context = [
            'contact' => isset($data['contact_id']) ? Contact::where('workspace_id', currentWorkspace()->id)->findOrFail($data['contact_id']) : null,
            'event' => isset($data['event_id']) ? Event::where('workspace_id', currentWorkspace()->id)->findOrFail($data['event_id']) : null,
        ];

        $rendered = [
            'subject' => Blade::render($template->subject, $context),
            'html' => Blade::render($template->html, $context),
            'text' => Blade::render($template->text ?? '', $context),
        ];

        return response()->json(['data' => $rendered]);
    }

    public function test(Request $request, Template $template, MailerResolver $resolver, TokenBucketLimiter $limiter): Response
    {
        $data = $request->validate([
            'to' => ['required', 'email'],
            'contact_id' => ['nullable', 'integer'],
            'event_id' => ['nullable', 'integer'],
        ]);

        $context = [
            'contact' => isset($data['contact_id']) ? Contact::where('workspace_id', currentWorkspace()->id)->findOrFail($data['contact_id']) : null,
            'event' => isset($data['event_id']) ? Event::where('workspace_id', currentWorkspace()->id)->findOrFail($data['event_id']) : null,
        ];

        $rendered = [
            'subject' => Blade::render($template->subject, $context),
            'html' => Blade::render($template->html, $context),
            'text' => Blade::render($template->text ?? '', $context),
        ];

        if (! $limiter->consume(currentWorkspace()->id, (int) config('rate-limiter.per_minute'))) {
            return response()->json([
                'errors' => [
                    ['title' => 'Rate limit exceeded'],
                ],
            ], 429);
        }

        $mailer = $resolver->for(currentWorkspace());
        $mailer->to($data['to'])->queue(
            new TemplateTestMail($rendered['subject'], $rendered['html'], $rendered['text'])
        );

        return response()->json(['data' => ['sent' => true]]);
    }

    private function transform(Template $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'subject' => $template->subject,
            'html' => $template->html,
            'text' => $template->text,
            'editor_meta' => $template->editor_meta,
        ];
    }
}
