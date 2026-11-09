<?php

namespace App\Jobs;

use App\Content\Site;
use App\Mail\NewContactMessage;
use App\Models\ContactMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * E-mails the owner about a new message. The status stored on the message is honest:
 * "skipped" when notices are off or no real mailer is configured, "sent" only after the mailer accepted it,
 * "failed" (with the reason) after the last retry. The message itself is never deleted or changed.
 */
class NotifyOwnerOfMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public function __construct(public int $messageId) {}

    public function handle(Site $site): void
    {
        $message = ContactMessage::query()->find($this->messageId);
        if (! $message) {
            return;
        }

        $settings = $site->settings('contact');
        $recipient = $settings['recipient'] ?: ($site->settings('identity')['email'] ?? '');
        $mailer = (string) config('mail.default');

        if (! ($settings['notify'] ?? false) || $recipient === '' || in_array($mailer, ['log', 'array'], true)) {
            $reason = ! ($settings['notify'] ?? false) ? 'notifications are switched off'
                : ($recipient === '' ? 'no recipient address' : "mailer \"{$mailer}\" does not deliver e-mail (development)");
            $message->forceFill(['notification_status' => 'skipped', 'notification_error' => $reason])->save();

            return;
        }

        Mail::to($recipient)->send(new NewContactMessage($message));
        $message->forceFill(['notification_status' => 'sent', 'notification_error' => null, 'notified_at' => now()])->save();
    }

    public function failed(Throwable $e): void
    {
        ContactMessage::query()->whereKey($this->messageId)->update([
            'notification_status' => 'failed',
            'notification_error' => mb_substr($e->getMessage(), 0, 1000),
        ]);
    }
}
