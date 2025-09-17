<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UgcSubmissionStatus;
use App\Models\Concerns\ScopedToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UgcSubmission extends Model
{
    use HasFactory;
    use ScopedToWorkspace;

    protected $fillable = [
        'workspace_id',
        'ugc_task_id',
        'ugc_application_id',
        'content_url',
        'notes',
        'status',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'status' => UgcSubmissionStatus::class,
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(UgcTask::class, 'ugc_task_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(UgcApplication::class, 'ugc_application_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
