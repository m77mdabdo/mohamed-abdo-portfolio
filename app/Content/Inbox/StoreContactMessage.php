<?php

namespace App\Content\Inbox;

use App\Jobs\NotifyOwnerOfMessage;
use App\Models\ContactMessage;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Saves a contact message first, then queues the e-mail notice. The notice can fail (no mail server,
 * wrong password) without losing the message. The same form submission (submission_key) is stored once,
 * so double clicks and retries never create duplicates.
 */
final class StoreContactMessage
{
    /** @return array{message: ContactMessage, created: bool} */
    public function handle(array $data, string $locale, ?string $ip, ?string $userAgent): array
    {
        $existing = ContactMessage::query()->where('submission_key', $data['submission_key'])->first();
        if ($existing) {
            return ['message' => $existing, 'created' => false];
        }

        try {
            $message = ContactMessage::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'project_type' => $data['project_type'] ?? null,
                'message' => $data['message'] ?? null,
                'locale' => $locale,
                'submission_key' => $data['submission_key'],
                'ip_hash' => $ip ? hash_hmac('sha256', $ip, (string) config('app.key')) : null,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 255) : null,
            ]);
        } catch (UniqueConstraintViolationException) {
            return ['message' => ContactMessage::query()->where('submission_key', $data['submission_key'])->firstOrFail(), 'created' => false];
        }

        // Runs right after the visitor's response is sent: no background worker is needed on simple hosting.
        NotifyOwnerOfMessage::dispatchAfterResponse($message->id);

        return ['message' => $message, 'created' => true];
    }
}
