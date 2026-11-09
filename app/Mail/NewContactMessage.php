<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewContactMessage extends Mailable
{
    public function __construct(public ContactMessage $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->contact->email, $this->contact->name)],
            subject: 'New message from '.$this->contact->name.($this->contact->project_type ? ' — '.$this->contact->project_type : ''),
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mail.contact-message-text', with: ['contact' => $this->contact, 'url' => url('/admin/contact-messages/'.$this->contact->id)]);
    }
}
