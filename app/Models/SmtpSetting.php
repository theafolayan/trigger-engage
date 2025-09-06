<?php

namespace App\Models;

use App\Models\Concerns\ScopedToWorkspace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmtpSetting extends Model
{
    /** @use HasFactory<\Database\Factories\SmtpSettingFactory> */
    use HasFactory, ScopedToWorkspace;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'is_active' => 'boolean',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
