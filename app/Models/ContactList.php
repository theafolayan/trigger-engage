<?php

namespace App\Models;

use App\Models\Concerns\ScopedToWorkspace;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ContactList extends Model
{
    /** @use HasFactory<\Database\Factories\ContactListFactory> */
    use HasFactory, ScopedToWorkspace;

    protected $table = 'lists';

    protected $guarded = [];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'list_contact', 'list_id', 'contact_id')
            ->withPivot(['subscribed_at', 'unsubscribed_at', 'meta']);
    }
}
