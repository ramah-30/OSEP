<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A single-purpose HTML email for guest invitations and reminders. The body is
 * pre-rendered by the dispatcher, so this stays view-free. With MAIL_MAILER=log
 * (dev default) the message is written to the Laravel log rather than sent.
 */
class GuestMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $htmlBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->htmlBody);
    }
}
