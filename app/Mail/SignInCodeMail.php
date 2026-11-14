<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use SensitiveParameter;

/** The dashboard sign-in code, in the person's dashboard language (plain text, nothing to render or track). */
class SignInCodeMail extends Mailable
{
    public function __construct(public User $staff, #[SensitiveParameter] public string $code, public int $minutes) {}

    private function mailLocale(): string
    {
        return in_array($this->staff->locale, ['ar', 'en'], true) ? $this->staff->locale : 'ar';
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('Your dashboard sign-in code', locale: $this->mailLocale()));
    }

    public function content(): Content
    {
        return new Content(text: 'mail.sign-in-code-text', with: [
            'locale' => $this->mailLocale(), 'name' => $this->staff->name, 'code' => $this->code, 'minutes' => $this->minutes,
        ]);
    }
}
