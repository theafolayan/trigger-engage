<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Models\Workspace;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailer as MailerContract;

class MailerResolver
{
    public function __construct(private MailFactory $factory) {}

    public function for(Workspace $workspace): MailerContract
    {
        $setting = $workspace->smtpSettings()->where('is_active', true)->first();

        if ($setting === null) {
            return $this->factory->mailer();
        }

        $name = 'workspace-'.$workspace->id;

        if (! config()->has("mail.mailers.$name")) {
            config([
                "mail.mailers.$name" => [
                    'transport' => 'smtp',
                    'host' => $setting->host,
                    'port' => $setting->port,
                    'username' => $setting->username,
                    'password' => decrypt($setting->password_encrypted),
                    'scheme' => $setting->encryption,
                ],
            ]);
        }

        $mailer = $this->factory->mailer($name);

        if ($setting->from_email !== null) {
            $mailer->alwaysFrom($setting->from_email, $setting->from_name);
        }

        if ($setting->reply_to !== null) {
            $mailer->alwaysReplyTo($setting->reply_to);
        }

        return $mailer;
    }
}
