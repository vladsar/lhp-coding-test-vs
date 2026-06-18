<?php

use App\Mail\EventRegistrationConfirmation;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function makeEvent(array $attributes = []): Event
{
    return Event::factory()->for(User::factory())->create($attributes);
}

it('registers an attendee and queues a confirmation email', function () {
    Mail::fake();
    $event = makeEvent();

    $this->post(route('events.attendees.store', $event), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ])->assertRedirect();

    $attendee = Attendee::sole();
    expect($attendee->event_id)->toBe($event->id)
        ->and($attendee->name)->toBe('Ada Lovelace')
        ->and($attendee->confirmation_sent_at)->not->toBeNull();

    Mail::assertQueued(
        EventRegistrationConfirmation::class,
        fn (EventRegistrationConfirmation $mail) => $mail->hasTo('ada@example.com'),
    );
});

it('prevents the same email registering twice for one event', function () {
    Mail::fake();
    $event = makeEvent();

    $this->post(route('events.attendees.store', $event), [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
    ]);

    $this->post(route('events.attendees.store', $event), [
        'name' => 'Ada Again',
        'email' => 'ada@example.com',
    ])->assertSessionHasErrors('email');

    expect(Attendee::where('event_id', $event->id)->count())->toBe(1);
    Mail::assertQueued(EventRegistrationConfirmation::class, 1);
});

it('treats the same email in different case as one registration', function () {
    Mail::fake();
    $event = makeEvent();

    $this->post(route('events.attendees.store', $event), [
        'name' => 'Ada Lovelace',
        'email' => 'Ada@Example.com',
    ])->assertRedirect();

    // Stored normalized to lowercase.
    expect(Attendee::sole()->email)->toBe('ada@example.com');

    // A different-case variant is rejected as a duplicate of the same event.
    $this->post(route('events.attendees.store', $event), [
        'name' => 'Ada Again',
        'email' => 'ADA@example.COM',
    ])->assertSessionHasErrors('email');

    expect(Attendee::where('event_id', $event->id)->count())->toBe(1);
    Mail::assertQueued(EventRegistrationConfirmation::class, 1);
});

it('allows the same email to register for different events', function () {
    Mail::fake();
    $first = makeEvent();
    $second = makeEvent();

    $this->post(route('events.attendees.store', $first), ['name' => 'Ada', 'email' => 'ada@example.com'])->assertRedirect();
    $this->post(route('events.attendees.store', $second), ['name' => 'Ada', 'email' => 'ada@example.com'])->assertRedirect();

    expect(Attendee::count())->toBe(2);
    Mail::assertQueued(EventRegistrationConfirmation::class, 2);
});

it('validates name and email', function () {
    $event = makeEvent();

    $this->post(route('events.attendees.store', $event), ['name' => '', 'email' => 'not-an-email'])
        ->assertSessionHasErrors(['name', 'email']);

    expect(Attendee::count())->toBe(0);
});
