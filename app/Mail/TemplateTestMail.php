<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class TemplateTestMail extends Mailable implements ShouldQueue
{
    public function __construct(
        public string $subjectText,
        public string $htmlContent,
        public ?string $textContent = null,
    ) {}

    public function build(): static
    {
        $this->subject($this->subjectText);

        if ($this->textContent !== null) {
            $this->text('mail.raw-text', ['text' => $this->textContent]);
        }

        return $this->html($this->htmlContent);
    }
}
