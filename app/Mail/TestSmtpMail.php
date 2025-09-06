<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;

class TestSmtpMail extends Mailable
{
    public function build(): static
    {
        return $this->subject('SMTP Test')->text('mail.test-smtp');
    }
}
