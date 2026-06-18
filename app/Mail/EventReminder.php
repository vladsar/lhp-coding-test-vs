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

class EventReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $window  Human label for the reminder window, e.g. "3 days" or "24 hours".
     */
    public function __construct(
        public Event $event,
        public Attendee $attendee,
        public string $window,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reminder: {$this->event->title} is in {$this->window}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.events.reminder',
        );
    }
}
