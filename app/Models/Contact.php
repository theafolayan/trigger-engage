<?php

namespace App\Models;

use App\Models\Concerns\ScopedToWorkspace;

use App\Enums\ContactStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    /** @use HasFactory<\Database\Factories\ContactFactory> */
    use HasFactory, ScopedToWorkspace;

    protected $guarded = [];

    protected $casts = [
        'attributes' => 'array',
        'status' => ContactStatus::class,
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(ContactList::class, 'list_contact', 'contact_id', 'list_id')
            ->withPivot(['subscribed_at', 'unsubscribed_at', 'meta']);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }
}
