<?php

declare(strict_types=1);

namespace App\Services\Twitter\Exceptions;

class RateLimitException extends DirectMessageException
{
    public function __construct(string $message, public ?int $retryAfter = null)
    {
        parent::__construct($message);
    }
}
