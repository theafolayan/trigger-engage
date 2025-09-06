<?php

namespace App\Models;

use App\Models\Concerns\ScopedToWorkspace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Automation extends Model
{
    /** @use HasFactory<\Database\Factories\AutomationFactory> */
    use HasFactory, ScopedToWorkspace;

    protected $guarded = [];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(AutomationStep::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }
}
