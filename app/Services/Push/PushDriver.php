<?php

declare(strict_types=1);

namespace App\Services\Push;

use App\Models\Contact;

interface PushDriver
{
    public function send(Contact $contact, array $message): void;
}

