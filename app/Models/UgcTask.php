<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UgcTaskStatus;
use App\Models\Concerns\ScopedToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UgcTask extends Model
{
    use HasFactory;
    use ScopedToWorkspace;

    protected $fillable = [
        'workspace_id',
        'title',
        'slug',
        'brief',
        'requirements',
        'reward',
        'status',
        'published_at',
        'deadline_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'deadline_at' => 'datetime',
        'status' => UgcTaskStatus::class,
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(UgcApplication::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(UgcSubmission::class);
    }
}
