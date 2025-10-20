<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwitterFollower extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'seen_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(TwitterAccount::class, 'twitter_account_id');
    }
}
