<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends Controller
{
    public function index(): Response
    {
        $contacts = Contact::all()->map(fn (Contact $contact) => $this->transform($contact));

        return response()->json(['data' => $contacts]);
    }

    public function store(Request $request): Response
    {
        return $this->upsert($request);
    }

    public function show(Contact $contact): Response
    {
        return response()->json(['data' => $this->transform($contact)]);
    }

    public function update(Request $request, Contact $contact): Response
    {
        $data = $request->validate([
            'email' => ['sometimes', 'email'],
            'first_name' => ['nullable', 'string'],
            'last_name' => ['nullable', 'string'],
            'attributes' => ['nullable', 'array'],
        ]);

        $contact->fill($data);
        $contact->save();

        return response()->json(['data' => $this->transform($contact)]);
    }

    public function destroy(Contact $contact): Response
    {
        $contact->delete();

        return response()->noContent();
    }

    public function upsert(Request $request): Response
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'first_name' => ['nullable', 'string'],
            'last_name' => ['nullable', 'string'],
            'attributes' => ['nullable', 'array'],
        ]);

        $contact = Contact::updateOrCreate(
            [
                'workspace_id' => currentWorkspace()->id,
                'email' => $data['email'],
            ],
            [
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'attributes' => $data['attributes'] ?? [],
            ],
        );

        return response()->json(
            ['data' => $this->transform($contact)],
            $contact->wasRecentlyCreated ? 201 : 200,
        );
    }

    public function bulkImport(Request $request): Response
    {
        $records = str_contains($request->header('Content-Type', ''), 'json')
            ? $request->json()->all()
            : $this->parseCsv($request->getContent());

        $created = 0;
        $updated = 0;
        $errors = [];

        foreach ($records as $index => $row) {
            $email = $row['email'] ?? null;

            if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['row' => $index, 'message' => 'Invalid email'];

                continue;
            }

            $contact = Contact::updateOrCreate(
                [
                    'workspace_id' => currentWorkspace()->id,
                    'email' => $email,
                ],
                [
                    'first_name' => $row['first_name'] ?? null,
                    'last_name' => $row['last_name'] ?? null,
                    'attributes' => array_diff_key(
                        $row,
                        array_flip(['email', 'first_name', 'last_name']),
                    ),
                ],
            );

            if ($contact->wasRecentlyCreated) {
                $created++;

                continue;
            }

            $updated++;
        }

        return response()->json([
            'data' => [
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
            ],
        ]);
    }

    private function parseCsv(string $csv): array
    {
        $rows = array_filter(array_map('trim', explode("\n", $csv)));

        if ($rows === []) {
            return [];
        }

        $header = str_getcsv(array_shift($rows));
        $records = [];

        foreach ($rows as $row) {
            if ($row === '') {
                continue;
            }

            $columns = str_getcsv($row);

            if (count($columns) !== count($header)) {
                continue;
            }

            $records[] = array_combine($header, $columns);
        }

        return $records;
    }

    private function transform(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'email' => $contact->email,
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'attributes' => $contact->attributes,
        ];
    }
}
