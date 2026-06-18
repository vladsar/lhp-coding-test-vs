<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendeeRequest;
use App\Mail\EventRegistrationConfirmation;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class AttendeeController extends Controller
{
    /**
     * Register interest/attendance for an event and email a confirmation.
     */
    public function store(StoreAttendeeRequest $request, Event $event): RedirectResponse
    {
        // firstOrCreate guards against the rare race where two requests pass
        // the unique-email validation at the same time; the DB unique index is
        // the final backstop.
        $attendee = $event->attendees()->firstOrCreate(
            ['email' => $request->validated('email')],
            ['name' => $request->validated('name')],
        );

        if ($attendee->wasRecentlyCreated) {
            $attendee->forceFill(['confirmation_sent_at' => now()])->save();

            Mail::to($attendee->email)->queue(
                new EventRegistrationConfirmation($event, $attendee),
            );
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => "You're on the list for {$event->title}.",
        ]);
    }
}
