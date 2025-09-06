<?php

namespace App\Models;

use App\Enums\AutomationStepKind;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationStep extends Model
{
    /** @use HasFactory<\Database\Factories\AutomationStepFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'config' => 'array',
        'kind' => AutomationStepKind::class,
    ];

    public function automation(): BelongsTo
    {
        return $this->belongsTo(Automation::class);
    }
}
