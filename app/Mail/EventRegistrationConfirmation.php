<?php

namespace App\Mail;

use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventRegistrationConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public Attendee $attendee,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're registered for {$this->event->title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.events.confirmation',
        );
    }
}
