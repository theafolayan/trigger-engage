<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UgcApplicationStatus;
use App\Models\Concerns\ScopedToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UgcApplication extends Model
{
    use HasFactory;
    use ScopedToWorkspace;

    protected $fillable = [
        'workspace_id',
        'ugc_task_id',
        'creator_name',
        'creator_email',
        'pitch',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'status' => UgcApplicationStatus::class,
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(UgcTask::class, 'ugc_task_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function submission(): HasOne
    {
        return $this->hasOne(UgcSubmission::class, 'ugc_application_id');
    }
}
