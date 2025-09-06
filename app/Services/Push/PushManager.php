<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\Models\PushSetting;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class PushManager
{
    public function __construct(private Container $container) {}

    public function driver(string $name, PushSetting $setting): PushDriver
    {
        return match ($name) {
            'one_signal' => $this->container->makeWith(OneSignalDriver::class, ['setting' => $setting]),
            'expo' => $this->container->makeWith(ExpoDriver::class, ['setting' => $setting]),
            default => throw new InvalidArgumentException("Unsupported push driver [$name]."),
        };
    }
}

