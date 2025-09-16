<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Automation\AutomationValidator;
use App\Enums\AutomationStepKind;
use App\Filament\Resources\AutomationResource\Pages\CreateAutomation;
use App\Filament\Resources\AutomationResource\Pages\EditAutomation;
use App\Filament\Resources\AutomationResource\Pages\ListAutomations;
use App\Models\Automation;
use App\Models\AutomationStep;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AutomationResource extends Resource
{
    protected static ?string $model = Automation::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-m-cog-6-tooth';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('trigger_event')
                    ->label('Trigger Event')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('timezone')
                    ->options(self::timezoneOptions())
                    ->searchable()
                    ->required()
                    ->default('UTC'),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(false),
            ]),
            Section::make('Conditions')
                ->schema([
                    Forms\Components\Repeater::make('conditions')
                        ->schema([
                            Forms\Components\TextInput::make('path')
                                ->label('Path')
                                ->maxLength(255),
                            Forms\Components\Select::make('op')
                                ->label('Operator')
                                ->options(self::conditionOperators()),
                            Forms\Components\Textarea::make('value')
                                ->label('Value')
                                ->rows(2)
                                ->helperText('Use JSON for arrays or complex data.'),
                        ])
                        ->grid(3)
                        ->addActionLabel('Add Condition')
                        ->reorderable(true)
                        ->default([]),
                ])
                ->collapsible()
                ->collapsed(),
            Section::make('Steps')
                ->schema([
                    Forms\Components\Repeater::make('steps')
                        ->schema([
                            Forms\Components\Hidden::make('id'),
                            Forms\Components\TextInput::make('uid')
                                ->label('UID')
                                ->required()
                                ->default(fn(): string => Str::uuid()->toString())
                                ->maxLength(255),
                            Forms\Components\Select::make('kind')
                                ->label('Kind')
                                ->options(self::stepKindOptions())
                                ->required(),
                            Forms\Components\Textarea::make('config')
                                ->label('Config (JSON)')
                                ->rows(4)
                                ->helperText('Provide configuration specific to the step type.'),
                            Forms\Components\TextInput::make('next_step_uid')
                                ->label('Next Step UID')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('alt_next_step_uid')
                                ->label('Alternate Next Step UID')
                                ->maxLength(255),
                        ])
                        ->grid(2)
                        ->addActionLabel('Add Step')
                        ->reorderable(true)
                        ->default([])
                        ->minItems(1),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('trigger_event')->label('Trigger'),
            Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            Tables\Columns\TextColumn::make('timezone')->sortable(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAutomations::route('/automations'),
            'create' => CreateAutomation::route('/automations/create'),
            'edit' => EditAutomation::route('/automations/{record}/edit'),
        ];
    }

    public static function normalizeFormData(array $data): array
    {
        $data['conditions'] = self::prepareConditionsForStorage($data['conditions'] ?? []);
        $data['steps'] = self::prepareStepsForStorage($data['steps'] ?? []);

        return $data;
    }

    public static function prepareConditionsForForm(array $conditions): array
    {
        return array_map(static function (array $condition): array {
            if (array_key_exists('value', $condition)) {
                $value = $condition['value'];

                if (is_array($value)) {
                    $condition['value'] = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
                } elseif (is_bool($value)) {
                    $condition['value'] = $value ? 'true' : 'false';
                } elseif ($value === null) {
                    $condition['value'] = '';
                } else {
                    $condition['value'] = (string) $value;
                }
            }

            return $condition;
        }, $conditions);
    }

    public static function prepareStepsForForm(Automation $automation): array
    {
        return $automation->steps()
            ->orderBy('id')
            ->get()
            ->map(static function (AutomationStep $step): array {
                $config = $step->config;

                if (is_array($config)) {
                    $config = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
                }

                return [
                    'id' => $step->id,
                    'uid' => $step->uid,
                    'kind' => $step->kind->value,
                    'config' => $config,
                    'next_step_uid' => $step->next_step_uid,
                    'alt_next_step_uid' => $step->alt_next_step_uid,
                ];
            })
            ->toArray();
    }

    public static function validateSteps(array $steps): void
    {
        if ($steps === []) {
            throw ValidationException::withMessages([
                'steps' => 'Add at least one step.',
            ]);
        }

        $validatorInput = array_map(static fn(array $step): array => [
            'uid' => $step['uid'],
            'next_step_uid' => $step['next_step_uid'] ?? null,
            'alt_next_step_uid' => $step['alt_next_step_uid'] ?? null,
        ], $steps);

        try {
            app(AutomationValidator::class)->validate($validatorInput);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'steps' => $exception->getMessage(),
            ]);
        }

        $uids = array_column($validatorInput, 'uid');
        if (count($uids) !== count(array_unique($uids))) {
            throw ValidationException::withMessages([
                'steps' => 'Step UIDs must be unique.',
            ]);
        }
    }

    public static function syncSteps(Automation $automation, array $steps): void
    {
        $existingIds = $automation->steps()->pluck('id')->all();
        $keptIds = [];

        foreach ($steps as $step) {
            $payload = [
                'uid' => $step['uid'],
                'kind' => $step['kind'],
                'config' => $step['config'],
                'next_step_uid' => $step['next_step_uid'] ?? null,
                'alt_next_step_uid' => $step['alt_next_step_uid'] ?? null,
            ];

            if (isset($step['id'])) {
                $model = $automation->steps()->whereKey($step['id'])->first();
                if ($model !== null) {
                    $model->update($payload);
                    $keptIds[] = $model->id;
                    continue;
                }
            }

            $model = $automation->steps()->create($payload);
            $keptIds[] = $model->id;
        }

        $toDelete = array_diff($existingIds, $keptIds);
        if ($toDelete !== []) {
            $automation->steps()->whereIn('id', $toDelete)->delete();
        }
    }

    private static function timezoneOptions(): array
    {
        $zones = \DateTimeZone::listIdentifiers();

        return array_combine($zones, $zones);
    }

    private static function conditionOperators(): array
    {
        return [
            '==' => 'Equals',
            '!=' => 'Not equals',
            'in' => 'In list',
            '<=' => 'Less than or equal',
            '>=' => 'Greater than or equal',
            '<' => 'Less than',
            '>' => 'Greater than',
            'exists' => 'Exists',
            'contains' => 'Contains',
        ];
    }

    private static function stepKindOptions(): array
    {
        $options = [];

        foreach (AutomationStepKind::cases() as $case) {
            $options[$case->value] = Str::headline($case->name);
        }

        return $options;
    }

    private static function prepareConditionsForStorage(array $conditions): array
    {
        $operators = array_keys(self::conditionOperators());

        $processed = [];

        foreach ($conditions as $condition) {
            $path = trim((string) ($condition['path'] ?? ''));
            $op = trim((string) ($condition['op'] ?? ''));
            $value = $condition['value'] ?? null;

            if ($path === '' && $op === '' && ($value === null || $value === '')) {
                continue;
            }

            if ($path === '' || $op === '') {
                throw ValidationException::withMessages([
                    'conditions' => 'Conditions require both a path and an operator.',
                ]);
            }

            if (! in_array($op, $operators, true)) {
                throw ValidationException::withMessages([
                    'conditions' => 'Invalid condition operator selected.',
                ]);
            }

            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    $value = null;
                } else {
                    $decoded = json_decode($trimmed, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $value = $decoded;
                    } else {
                        $value = $trimmed;
                    }
                }
            }

            $processed[] = [
                'path' => $path,
                'op' => $op,
                'value' => $value,
            ];
        }

        return $processed;
    }

    private static function prepareStepsForStorage(array $steps): array
    {
        $kinds = array_map(static fn(AutomationStepKind $kind): string => $kind->value, AutomationStepKind::cases());

        $processed = [];

        foreach ($steps as $step) {
            $uid = trim((string) ($step['uid'] ?? ''));
            $kind = $step['kind'] ?? null;

            if ($uid === '' && ($kind === null || $kind === '')) {
                continue;
            }

            if ($uid === '' || $kind === null || $kind === '') {
                throw ValidationException::withMessages([
                    'steps' => 'Each step requires both a UID and kind.',
                ]);
            }

            if (! in_array($kind, $kinds, true)) {
                throw ValidationException::withMessages([
                    'steps' => 'Invalid step kind selected.',
                ]);
            }

            $config = $step['config'] ?? null;

            if (is_string($config)) {
                $trimmedConfig = trim($config);

                if ($trimmedConfig === '') {
                    $config = null;
                } else {
                    $decodedConfig = json_decode($trimmedConfig, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw ValidationException::withMessages([
                            'steps' => 'Step config must be valid JSON.',
                        ]);
                    }

                    $config = $decodedConfig;
                }
            } elseif ($config === [] || $config === '') {
                $config = null;
            } elseif ($config !== null && ! is_array($config)) {
                throw ValidationException::withMessages([
                    'steps' => 'Step config must be valid JSON.',
                ]);
            }

            $next = trim((string) ($step['next_step_uid'] ?? ''));
            $alt = trim((string) ($step['alt_next_step_uid'] ?? ''));

            $processed[] = [
                'id' => $step['id'] ?? null,
                'uid' => $uid,
                'kind' => $kind,
                'config' => $config,
                'next_step_uid' => $next === '' ? null : $next,
                'alt_next_step_uid' => $alt === '' ? null : $alt,
            ];
        }

        return $processed;
    }
}
