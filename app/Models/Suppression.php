<?php

namespace App\Models;

use App\Enums\SuppressionReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suppression extends Model
{
    /** @use HasFactory<\Database\Factories\SuppressionFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'reason' => SuppressionReason::class,
        'source' => 'array',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
