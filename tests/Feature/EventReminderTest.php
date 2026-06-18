<?php

use App\Mail\EventReminder;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function eventStartingIn(int $seconds): Event
{
    return Event::factory()->for(User::factory())->create([
        'created_time' => Carbon::now()->timestamp + $seconds,
    ]);
}

function attendeeFor(Event $event): Attendee
{
    return Attendee::factory()->for($event)->create();
}

it('queues only a 3-day reminder for an event ~2 days out', function () {
    Mail::fake();
    $attendee = attendeeFor(eventStartingIn(2 * 24 * 3600));

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminder::class, fn (EventReminder $m) => $m->window === '3 days' && $m->hasTo($attendee->email));
    Mail::assertNotQueued(EventReminder::class, fn (EventReminder $m) => $m->window === '24 hours');

    $attendee->refresh();
    expect($attendee->reminded_3d_at)->not->toBeNull()
        ->and($attendee->reminded_24h_at)->toBeNull();
});

it('queues only a 24-hour reminder for an event ~12 hours out', function () {
    Mail::fake();
    $attendee = attendeeFor(eventStartingIn(12 * 3600));

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminder::class, fn (EventReminder $m) => $m->window === '24 hours' && $m->hasTo($attendee->email));
    Mail::assertNotQueued(EventReminder::class, fn (EventReminder $m) => $m->window === '3 days');

    $attendee->refresh();
    expect($attendee->reminded_24h_at)->not->toBeNull()
        ->and($attendee->reminded_3d_at)->toBeNull();
});

it('does not send reminders for events that have already started', function () {
    Mail::fake();
    attendeeFor(eventStartingIn(-3600));

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertNothingQueued();
});

it('does not send the same reminder twice when run repeatedly', function () {
    Mail::fake();
    attendeeFor(eventStartingIn(2 * 24 * 3600));

    $this->artisan('events:send-reminders')->assertSuccessful();
    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminder::class, 1);
});

it('sends both reminders over the lifetime of an event, but never at once', function () {
    Mail::fake();
    $attendee = attendeeFor(eventStartingIn(2 * 24 * 3600));

    // 2 days out -> 3-day reminder.
    $this->artisan('events:send-reminders');
    Mail::assertQueued(EventReminder::class, 1);

    // Jump to ~12 hours before the event -> 24-hour reminder, and the 3-day
    // one is not re-sent.
    Carbon::setTestNow(Carbon::now()->addHours(36));
    $this->artisan('events:send-reminders');

    Mail::assertQueued(EventReminder::class, 2);
    Mail::assertQueued(EventReminder::class, fn (EventReminder $m) => $m->window === '24 hours');

    $attendee->refresh();
    expect($attendee->reminded_3d_at)->not->toBeNull()
        ->and($attendee->reminded_24h_at)->not->toBeNull();

    Carbon::setTestNow();
});
