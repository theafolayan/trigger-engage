<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Models\Contact;
use App\Models\Suppression;
use App\Models\Workspace;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Mail\Mailer as IlluminateMailer;
use Illuminate\Mail\Transport\NullTransport;

class MailerResolver
{
    public function __construct(
        private MailFactory $factory,
        private ViewFactory $views,
        private QueueFactory $queue,
    ) {}

    public function for(Workspace $workspace, ?Contact $contact = null): MailerContract
    {
        if ($contact !== null) {
            $suppressed = Suppression::where('workspace_id', $workspace->id)
                ->where('email', $contact->email)
                ->exists();

            if ($suppressed) {
                return new IlluminateMailer('null', $this->views, new NullTransport(), $this->queue);
            }
        }

        $setting = $workspace->smtpSettings()->where('is_active', true)->first();

        if ($setting === null) {
            return $this->factory->mailer();
        }

        $name = 'workspace-' . $workspace->id;

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
