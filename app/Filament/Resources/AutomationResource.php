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
use Filament\Schemas\Components\Group as SchemaGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                            Forms\Components\Select::make('attribute')
                                ->label('Attribute')
                                ->options(self::conditionAttributeOptions())
                                ->searchable()
                                ->placeholder('Select attribute')
                                ->required()
                                ->reactive(),
                            Forms\Components\TextInput::make('custom_path')
                                ->label('Custom JSON path')
                                ->placeholder('e.g. event.payload.plan')
                                ->maxLength(255)
                                ->visible(fn (Get $get): bool => $get('attribute') === '__custom__')
                                ->required(fn (Get $get): bool => $get('attribute') === '__custom__')
                                ->helperText('Use dot notation to access nested data.'),
                            Forms\Components\Select::make('op')
                                ->label('Operator')
                                ->options(self::conditionOperators())
                                ->required()
                                ->reactive(),
                            Forms\Components\Select::make('type')
                                ->label('Value type')
                                ->options(self::conditionValueTypes())
                                ->default('string')
                                ->reactive()
                                ->visible(fn (Get $get): bool => self::operatorRequiresValue((string) $get('op')))
                                ->required(fn (Get $get): bool => self::operatorRequiresValue((string) $get('op'))),
                            Forms\Components\TextInput::make('value_text')
                                ->label('Value')
                                ->default('')
                                ->visible(fn (Get $get): bool => self::operatorRequiresValue((string) $get('op')) && $get('type') === 'string')
                                ->required(fn (Get $get): bool => self::operatorRequiresValue((string) $get('op')) && $get('type') === 'string'),
                            Forms\Components\TextInput::make('value_number')
                                ->label('Number')
                                ->numeric()
                                ->visible(fn (Get $get): bool => self::operatorRequiresValue((string) $get('op')) && $get('type') === 'number')
                                ->required(fn (Get $get): bool => self::operatorRequiresValue((string) $get('op')) && $get('type') === 'number'),
                            Forms\Components\Toggle::make('value_boolean')
                                ->label('Value')
                                ->inline(false)
                                ->default(false)
                                ->visible(fn (Get $get): bool => self::operatorRequiresValue((string) $get('op')) && $get('type') === 'boolean')
                                ->required(fn (Get $get): bool => self::operatorRequiresValue((string) $get('op')) && $get('type') === 'boolean'),
                            Forms\Components\TagsInput::make('value_array')
                                ->label('Values')
                                ->placeholder('Add value and press enter')
                                ->default([])
                                ->visible(fn (Get $get): bool => self::operatorRequiresValue((string) $get('op')) && $get('type') === 'array')
                                ->required(fn (Get $get): bool => self::operatorRequiresValue((string) $get('op')) && $get('type') === 'array')
                                ->helperText('Each entry becomes a list item for comparisons like "in" or "contains".'),
                        ])
                        ->grid(1)
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
                            Forms\Components\TextInput::make('label')
                                ->label('Step name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, Get $get, Set $set): void {
                                    if ($get('allow_uid_edit')) {
                                        return;
                                    }

                                    $slug = Str::slug((string) $state);

                                    if ($slug === '') {
                                        return;
                                    }

                                    $set('uid', $slug);
                                })
                                ->columnSpan(2),
                            Forms\Components\Toggle::make('allow_uid_edit')
                                ->label('Edit UID manually')
                                ->inline(false)
                                ->default(false)
                                ->live()
                                ->dehydrated(false)
                                ->afterStateUpdated(function (bool $state, Get $get, Set $set): void {
                                    if ($state) {
                                        return;
                                    }

                                    $label = $get('label');
                                    $slug = Str::slug((string) $label);

                                    if ($slug === '') {
                                        return;
                                    }

                                    $set('uid', $slug);
                                }),
                            Forms\Components\TextInput::make('uid')
                                ->label('Step UID')
                                ->required()
                                ->default(fn(): string => Str::slug(Str::uuid()->toString()))
                                ->disabled(fn (Get $get): bool => ! $get('allow_uid_edit'))
                                ->maxLength(255),
                            Forms\Components\Select::make('kind')
                                ->label('Kind')
                                ->options(self::stepKindOptions())
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function (Set $set): void {
                                    $set('config', []);
                                }),
                            SchemaGroup::make()
                                ->schema([
                                    Forms\Components\Select::make('config.template_id')
                                        ->label('Template')
                                        ->relationship('workspace.templates', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required(fn (Get $get): bool => $get('kind') === AutomationStepKind::SendEmail->value)
                                        ->helperText('Choose the template to send for this step.'),
                                ])
                                ->visible(fn (Get $get): bool => $get('kind') === AutomationStepKind::SendEmail->value)
                                ->columnSpan(2),
                            SchemaGroup::make()
                                ->schema([
                                    Forms\Components\TextInput::make('config.minutes')
                                        ->label('Delay (minutes)')
                                        ->integer()
                                        ->minValue(0)
                                        ->required(fn (Get $get): bool => $get('kind') === AutomationStepKind::Delay->value)
                                        ->helperText('Number of minutes to wait before continuing.'),
                                ])
                                ->visible(fn (Get $get): bool => $get('kind') === AutomationStepKind::Delay->value)
                                ->columnSpan(2),
                            SchemaGroup::make()
                                ->schema([
                                    Forms\Components\TextInput::make('config.title')
                                        ->label('Title')
                                        ->maxLength(255)
                                        ->required(fn (Get $get): bool => $get('kind') === AutomationStepKind::SendPushNotification->value),
                                    Forms\Components\Textarea::make('config.body')
                                        ->label('Body')
                                        ->rows(3)
                                        ->required(fn (Get $get): bool => $get('kind') === AutomationStepKind::SendPushNotification->value),
                                    Forms\Components\KeyValue::make('config.data')
                                        ->label('Payload Data')
                                        ->helperText('Optional key-value payload delivered with the notification.')
                                        ->nullable()
                                        ->keyLabel('Key')
                                        ->valueLabel('Value')
                                        ->columnSpan(2),
                                ])
                                ->visible(fn (Get $get): bool => $get('kind') === AutomationStepKind::SendPushNotification->value)
                                ->columnSpan(2),
                            Forms\Components\Select::make('next_step_uid')
                                ->label('Next Step')
                                ->placeholder('None')
                                ->searchable()
                                ->reactive()
                                ->options(function (Get $get): array {
                                    $steps = $get('../../steps');

                                    if (! is_array($steps)) {
                                        $steps = [];
                                    }

                                    $currentUid = $get('uid');

                                    return self::buildStepLinkOptions($steps, is_string($currentUid) ? $currentUid : null);
                                }),
                            Forms\Components\Select::make('alt_next_step_uid')
                                ->label('Alternate Next Step')
                                ->placeholder('None')
                                ->searchable()
                                ->reactive()
                                ->options(function (Get $get): array {
                                    $steps = $get('../../steps');

                                    if (! is_array($steps)) {
                                        $steps = [];
                                    }

                                    $currentUid = $get('uid');

                                    return self::buildStepLinkOptions($steps, is_string($currentUid) ? $currentUid : null);
                                }),
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
        $knownAttributes = array_keys(self::flattenConditionAttributeOptions());

        return array_map(static function (array $condition) use ($knownAttributes): array {
            $path = isset($condition['path']) ? trim((string) $condition['path']) : '';
            $op = isset($condition['op']) ? (string) $condition['op'] : '==';
            $value = $condition['value'] ?? null;

            $attribute = in_array($path, $knownAttributes, true) ? $path : '__custom__';
            $customPath = $attribute === '__custom__' ? $path : null;

            $type = match (true) {
                is_bool($value) => 'boolean',
                is_array($value) => 'array',
                $value === null => 'null',
                is_int($value), is_float($value) => 'number',
                default => 'string',
            };

            $valueText = '';
            $valueNumber = null;
            $valueBoolean = false;
            $valueArray = [];

            if ($type === 'string' && $value !== null) {
                $valueText = (string) $value;
            } elseif ($type === 'number' && $value !== null) {
                $valueNumber = (string) $value;
            } elseif ($type === 'boolean') {
                $valueBoolean = (bool) $value;
            } elseif ($type === 'array' && is_array($value)) {
                $valueArray = array_map(
                    static function ($item): string {
                        if ($item === null) {
                            return 'null';
                        }

                        if (is_bool($item)) {
                            return $item ? 'true' : 'false';
                        }

                        if (is_scalar($item)) {
                            return (string) $item;
                        }

                        return json_encode($item, JSON_UNESCAPED_SLASHES) ?: '';
                    },
                    $value
                );
            }

            return [
                'attribute' => $attribute,
                'custom_path' => $customPath,
                'op' => $op,
                'type' => $type,
                'value_text' => $valueText,
                'value_number' => $valueNumber,
                'value_boolean' => $valueBoolean,
                'value_array' => $valueArray,
            ];
        }, $conditions);
    }

    public static function prepareStepsForForm(Automation $automation): array
    {
        return $automation->steps()
            ->orderBy('id')
            ->get()
            ->map(static function (AutomationStep $step): array {
                $config = $step->config;

                if (! is_array($config)) {
                    $config = [];
                }

                return [
                    'id' => $step->id,
                    'label' => self::defaultStepLabel($step->uid),
                    'uid' => $step->uid,
                    'kind' => $step->kind->value,
                    'config' => self::prepareStepConfigForForm($step->kind, $config),
                    'next_step_uid' => $step->next_step_uid,
                    'alt_next_step_uid' => $step->alt_next_step_uid,
                ];
            })
            ->toArray();
    }

    private static function defaultStepLabel(string $uid): string
    {
        $label = trim(Str::headline(str_replace(['_', '-'], ' ', $uid)));

        return $label === '' ? 'Step' : $label;
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private static function prepareStepConfigForForm(AutomationStepKind $kind, array $config): array
    {
        return match ($kind) {
            AutomationStepKind::SendEmail => [
                'template_id' => $config['template_id'] ?? null,
            ],
            AutomationStepKind::Delay => [
                'minutes' => $config['minutes'] ?? null,
            ],
            AutomationStepKind::SendPushNotification => [
                'title' => $config['title'] ?? null,
                'body' => $config['body'] ?? null,
                'data' => is_array($config['data'] ?? null) ? $config['data'] : [],
            ],
            default => [],
        };
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

    /**
     * @param array<int, array<string, mixed>> $steps
     */
    private static function buildStepLinkOptions(array $steps, ?string $currentUid): array
    {
        $options = [];

        foreach ($steps as $step) {
            if (! is_array($step)) {
                continue;
            }

            $uid = trim((string) ($step['uid'] ?? ''));

            if ($uid === '' || ($currentUid !== null && $uid === $currentUid)) {
                continue;
            }

            $label = isset($step['label']) ? trim((string) $step['label']) : '';

            $options[$uid] = $label !== '' ? sprintf('%s (%s)', $label, $uid) : $uid;
        }

        return $options;
    }

    private static function prepareConditionsForStorage(array $conditions): array
    {
        $operators = array_keys(self::conditionOperators());

        $processed = [];

        foreach ($conditions as $condition) {
            $attribute = is_string($condition['attribute'] ?? null) ? trim((string) $condition['attribute']) : '';
            $customPath = is_string($condition['custom_path'] ?? null) ? trim((string) $condition['custom_path']) : '';
            $op = trim((string) ($condition['op'] ?? ''));

            $path = $attribute === '__custom__'
                ? $customPath
                : ($attribute !== '' ? $attribute : $customPath);

            $requiresValue = $op !== '' && self::operatorRequiresValue($op);

            $hasValuePayload = $requiresValue && (
                array_key_exists('value_text', $condition)
                || array_key_exists('value_number', $condition)
                || array_key_exists('value_boolean', $condition)
                || array_key_exists('value_array', $condition)
                || array_key_exists('type', $condition)
            );

            if ($path === '' && $op === '' && ! $hasValuePayload) {
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

            $value = null;

            if ($requiresValue) {
                $type = is_string($condition['type'] ?? null) ? $condition['type'] : 'string';

                if (! array_key_exists($type, self::conditionValueTypes())) {
                    throw ValidationException::withMessages([
                        'conditions' => 'Invalid value type selected for a condition.',
                    ]);
                }

                $value = match ($type) {
                    'number' => self::normalizeNumberValue($condition['value_number'] ?? null),
                    'boolean' => (bool) ($condition['value_boolean'] ?? false),
                    'array' => self::normalizeArrayValue($condition['value_array'] ?? []),
                    'null' => null,
                    default => self::normalizeStringValue($condition['value_text'] ?? null),
                };
            }

            $payload = [
                'path' => $path,
                'op' => $op,
            ];

            if ($requiresValue) {
                $payload['value'] = $value;
            }

            $processed[] = $payload;
        }

        return $processed;
    }

    private static function conditionAttributeOptions(): array
    {
        return [
            'Event' => [
                'event.name' => 'Event · Name',
                'event.occurred_at' => 'Event · Occurred at',
                'event.id' => 'Event · ID',
            ],
            'Contact' => [
                'contact.email' => 'Contact · Email',
                'contact.first_name' => 'Contact · First name',
                'contact.last_name' => 'Contact · Last name',
                'contact.status' => 'Contact · Status',
                'contact.tags' => 'Contact · Tags',
                'contact.lists' => 'Contact · Lists',
            ],
            '__custom__' => 'Advanced: Custom JSON path',
        ];
    }

    private static function flattenConditionAttributeOptions(): array
    {
        $options = self::conditionAttributeOptions();
        $flat = [];

        foreach ($options as $key => $value) {
            if ($key === '__custom__') {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $nestedKey => $nestedValue) {
                    if (is_array($nestedValue)) {
                        foreach ($nestedValue as $deepKey => $deepValue) {
                            $flat[$deepKey] = $deepValue;
                        }
                    } else {
                        $flat[$nestedKey] = $nestedValue;
                    }
                }
            } else {
                $flat[$key] = $value;
            }
        }

        return $flat;
    }

    private static function conditionValueTypes(): array
    {
        return [
            'string' => 'Text',
            'number' => 'Number',
            'boolean' => 'Boolean',
            'array' => 'List of values',
            'null' => 'Empty (null)',
        ];
    }

    private static function operatorRequiresValue(string $operator): bool
    {
        return $operator !== 'exists';
    }

    private static function normalizeStringValue(mixed $value): string
    {
        if ($value === null) {
            throw ValidationException::withMessages([
                'conditions' => 'A text value is required for this condition.',
            ]);
        }

        $string = (string) $value;

        if ($string === '') {
            throw ValidationException::withMessages([
                'conditions' => 'A text value is required for this condition.',
            ]);
        }

        return $string;
    }

    private static function normalizeNumberValue(mixed $value): int|float
    {
        if ($value === null || $value === '') {
            throw ValidationException::withMessages([
                'conditions' => 'A number is required for this condition.',
            ]);
        }

        if (! is_numeric($value)) {
            throw ValidationException::withMessages([
                'conditions' => 'Provide a valid numeric value for this condition.',
            ]);
        }

        $numericString = is_string($value) ? $value : (string) $value;

        $isIntLike = filter_var($numericString, FILTER_VALIDATE_INT) !== false;

        $usesExponent = str_contains($numericString, 'e') || str_contains($numericString, 'E');

        if ($isIntLike && ! $usesExponent) {
            return (int) $numericString;
        }

        return (float) $numericString;
    }

    private static function normalizeArrayValue(mixed $value): array
    {
        if (! is_array($value)) {
            throw ValidationException::withMessages([
                'conditions' => 'Add at least one entry to the list for this condition.',
            ]);
        }

        $items = [];

        foreach ($value as $item) {
            if (is_string($item)) {
                $trimmed = trim($item);

                if ($trimmed === '') {
                    continue;
                }

                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $items[] = $decoded;
                } else {
                    $items[] = $trimmed;
                }

                continue;
            }

            if (is_scalar($item) || $item === null) {
                $items[] = $item;
            }
        }

        if ($items === []) {
            throw ValidationException::withMessages([
                'conditions' => 'Add at least one entry to the list for this condition.',
            ]);
        }

        return $items;
    }

    private static function prepareStepsForStorage(array $steps): array
    {
        $kinds = array_map(static fn(AutomationStepKind $kind): string => $kind->value, AutomationStepKind::cases());

        $processed = [];

        $workspaceTemplateIds = [];
        if (function_exists('currentWorkspace') && null !== currentWorkspace()) {
            $workspaceTemplateIds = array_map(
                static fn($id): int => (int) $id,
                currentWorkspace()->templates()->pluck('id')->all()
            );
        }

        foreach ($steps as $step) {
            $kind = $step['kind'] ?? null;
            $label = isset($step['label']) ? trim((string) $step['label']) : '';
            $uid = isset($step['uid']) ? trim((string) $step['uid']) : '';

            if ($uid === '' && $label === '' && ($kind === null || $kind === '')) {
                continue;
            }

            if ($uid === '') {
                $uid = $label !== '' ? Str::slug($label) : '';
            }

            if ($uid === '') {
                $uid = Str::slug(Str::uuid()->toString());
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

            $config = $step['config'] ?? [];

            if (is_string($config)) {
                $trimmedConfig = trim($config);

                if ($trimmedConfig === '') {
                    $config = [];
                } else {
                    $decodedConfig = json_decode($trimmedConfig, true);

                    if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decodedConfig)) {
                        throw ValidationException::withMessages([
                            'steps' => 'Step config must be an array.',
                        ]);
                    }

                    $config = $decodedConfig;
                }
            } elseif ($config === null) {
                $config = [];
            } elseif (! is_array($config)) {
                throw ValidationException::withMessages([
                    'steps' => 'Step config must be an array.',
                ]);
            }

            $kindEnum = AutomationStepKind::from($kind);
            $config = self::prepareStepConfigForStorage($kindEnum, $config, $workspaceTemplateIds);

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

    /**
     * @param array<string, mixed> $config
     * @param array<int, int> $workspaceTemplateIds
     */
    private static function prepareStepConfigForStorage(AutomationStepKind $kind, array $config, array $workspaceTemplateIds): ?array
    {
        return match ($kind) {
            AutomationStepKind::SendEmail => self::prepareSendEmailConfigForStorage($config, $workspaceTemplateIds),
            AutomationStepKind::Delay => self::prepareDelayConfigForStorage($config),
            AutomationStepKind::SendPushNotification => self::preparePushNotificationConfigForStorage($config),
            default => $config === [] ? null : $config,
        };
    }

    /**
     * @param array<string, mixed> $config
     * @param array<int, int> $workspaceTemplateIds
     * @return array{template_id: int}
     */
    private static function prepareSendEmailConfigForStorage(array $config, array $workspaceTemplateIds): array
    {
        $templateId = $config['template_id'] ?? null;

        if ($templateId === null || $templateId === '') {
            throw ValidationException::withMessages([
                'steps' => 'Select a template for email steps.',
            ]);
        }

        if (! is_int($templateId)) {
            if (is_numeric($templateId)) {
                $templateId = (int) $templateId;
            } else {
                throw ValidationException::withMessages([
                    'steps' => 'Select a valid template for email steps.',
                ]);
            }
        }

        if ($templateId <= 0) {
            throw ValidationException::withMessages([
                'steps' => 'Select a valid template for email steps.',
            ]);
        }

        if ($workspaceTemplateIds !== [] && ! in_array($templateId, $workspaceTemplateIds, true)) {
            throw ValidationException::withMessages([
                'steps' => 'Select a template available in this workspace.',
            ]);
        }

        return ['template_id' => $templateId];
    }

    /**
     * @param array<string, mixed> $config
     * @return array{minutes: int}
     */
    private static function prepareDelayConfigForStorage(array $config): array
    {
        $minutes = $config['minutes'] ?? null;

        if ($minutes === null || $minutes === '') {
            throw ValidationException::withMessages([
                'steps' => 'Delay steps require a duration in minutes.',
            ]);
        }

        if (! is_numeric($minutes)) {
            throw ValidationException::withMessages([
                'steps' => 'Delay durations must be numeric.',
            ]);
        }

        $minutes = (int) $minutes;

        if ($minutes < 0) {
            throw ValidationException::withMessages([
                'steps' => 'Delay durations cannot be negative.',
            ]);
        }

        return ['minutes' => $minutes];
    }

    /**
     * @param array<string, mixed> $config
     * @return array{title: string, body: string, data?: array<string, string>}
     */
    private static function preparePushNotificationConfigForStorage(array $config): array
    {
        $title = isset($config['title']) ? trim((string) $config['title']) : '';
        $body = isset($config['body']) ? trim((string) $config['body']) : '';

        if ($title === '' || $body === '') {
            throw ValidationException::withMessages([
                'steps' => 'Push notification steps require a title and body.',
            ]);
        }

        $data = $config['data'] ?? [];
        if ($data === null) {
            $data = [];
        } elseif (! is_array($data)) {
            throw ValidationException::withMessages([
                'steps' => 'Push notification payload must be key-value pairs.',
            ]);
        }

        $normalizedData = [];
        foreach ($data as $key => $value) {
            $normalizedKey = trim((string) $key);

            if ($normalizedKey === '') {
                continue;
            }

            if ($value === null) {
                $normalizedData[$normalizedKey] = null;
                continue;
            }

            if (! is_scalar($value)) {
                throw ValidationException::withMessages([
                    'steps' => 'Push notification payload values must be strings.',
                ]);
            }

            $normalizedData[$normalizedKey] = (string) $value;
        }

        $result = [
            'title' => $title,
            'body' => $body,
        ];

        if ($normalizedData !== []) {
            $result['data'] = $normalizedData;
        }

        return $result;
    }
}
