<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'features' => 'array',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'subscription_plan_id');
    }

    public function hasFeature(string $feature): bool
    {
        return (bool)($this->features[$feature] ?? false);
    }
}
