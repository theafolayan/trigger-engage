<?php

declare(strict_types=1);

use App\Models\Workspace;

function currentWorkspace(): ?Workspace
{
    return app()->bound('currentWorkspace') ? app()->get('currentWorkspace') : null;
}
