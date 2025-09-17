<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workspace extends Model
{
    /** @use HasFactory<\Database\Factories\WorkspaceFactory> */
    use HasFactory;

    protected $guarded = [];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class);
    }

    public function smtpSettings(): HasMany
    {
        return $this->hasMany(SmtpSetting::class);
    }

    public function pushSettings(): HasMany
    {
        return $this->hasMany(PushSetting::class);
    }

    public function lists(): HasMany
    {
        return $this->hasMany(ContactList::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function suppressions(): HasMany
    {
        return $this->hasMany(Suppression::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function ugcTasks(): HasMany
    {
        return $this->hasMany(UgcTask::class);
    }

    public function ugcApplications(): HasMany
    {
        return $this->hasMany(UgcApplication::class);
    }

    public function ugcSubmissions(): HasMany
    {
        return $this->hasMany(UgcSubmission::class);
    }
}
