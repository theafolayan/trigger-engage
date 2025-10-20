<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\ScopedToWorkspace;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TwitterAccount extends Model
{
    use HasFactory, ScopedToWorkspace;

    protected $guarded = [];

    protected $casts = [
        'scopes' => 'array',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_polled_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token_encrypted',
        'refresh_token_encrypted',
    ];

    protected $appends = [
        'status',
        'is_connected',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function followers(): HasMany
    {
        return $this->hasMany(TwitterFollower::class);
    }

    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value, array $attributes): ?string => isset($attributes['access_token_encrypted'])
                ? decrypt($attributes['access_token_encrypted'])
                : null,
            set: fn(?string $value): array => [
                'access_token_encrypted' => $value === null ? null : encrypt($value),
            ],
        );
    }

    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value, array $attributes): ?string => isset($attributes['refresh_token_encrypted'])
                ? decrypt($attributes['refresh_token_encrypted'])
                : null,
            set: fn(?string $value): array => [
                'refresh_token_encrypted' => $value === null ? null : encrypt($value),
            ],
        );
    }

    public function getIsConnectedAttribute(): bool
    {
        return $this->disconnected_at === null;
    }

    public function getStatusAttribute(): string
    {
        return $this->is_connected ? 'connected' : 'disconnected';
    }
}
