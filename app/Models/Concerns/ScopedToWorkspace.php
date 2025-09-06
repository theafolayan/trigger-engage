<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait ScopedToWorkspace
{
    protected static function bootScopedToWorkspace(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder): void {
            if (function_exists('currentWorkspace') && null !== currentWorkspace()) {
                $builder->where($builder->getModel()->getTable().'.workspace_id', currentWorkspace()->id);
            }
        });

        static::creating(function (Model $model): void {
            if (function_exists('currentWorkspace') && null !== currentWorkspace()) {
                $model->workspace_id ??= currentWorkspace()->id;
            }
        });
    }
}
