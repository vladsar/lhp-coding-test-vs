<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function seedEvent(array $attributes): Event
{
    return Event::factory()->for(User::factory())->create($attributes);
}

it('renders the visual page with shaped events and filter options', function () {
    seedEvent([
        'type' => 'concert',
        'status' => 'published',
        'created_time' => Carbon::now()->addDays(5)->timestamp,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
        'payload' => ['name' => 'Synthwave Night', 'description' => 'A night out', 'venue' => ['name' => 'The Grand Hall']],
    ]);

    $this->get(route('events.visual1'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/VisualOne')
            ->has('events.data', 1)
            ->where('events.data.0.title', 'Synthwave Night')
            ->where('events.data.0.venue', 'The Grand Hall')
            ->where('events.data.0.address', 'New York, USA')
            ->where('events.data.0.attendees_count', 0)
            ->has('events.data.0.images', 3)
            ->has('filterOptions.cities')
            ->has('filterOptions.types', 8)
            ->has('filterOptions.statuses', 4)
        );
});

it('hides events that have already started by default', function () {
    seedEvent(['created_time' => Carbon::now()->addDays(3)->timestamp]);
    seedEvent(['created_time' => Carbon::now()->subDays(3)->timestamp]);

    $this->get(route('events.visual2'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Events/VisualTwo')->has('events.data', 1));
});

it('filters by status and type', function () {
    seedEvent(['status' => 'published', 'type' => 'concert', 'created_time' => Carbon::now()->addDays(2)->timestamp]);
    seedEvent(['status' => 'cancelled', 'type' => 'concert', 'created_time' => Carbon::now()->addDays(2)->timestamp]);
    seedEvent(['status' => 'published', 'type' => 'workshop', 'created_time' => Carbon::now()->addDays(2)->timestamp]);

    $this->get(route('events.visual1', ['status' => 'published', 'type' => 'concert']))
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.status', 'published')
            ->where('events.data.0.type', 'concert')
        );
});

it('filters by an explicit date range, including past events', function () {
    seedEvent(['created_time' => Carbon::parse('2024-03-15', 'UTC')->timestamp]);
    seedEvent(['created_time' => Carbon::parse('2024-06-15', 'UTC')->timestamp]);

    $this->get(route('events.visual1', ['from' => '2024-01-01', 'to' => '2024-04-01']))
        ->assertInertia(fn ($page) => $page->has('events.data', 1));
});

it('filters by city using a coordinate bounding box', function () {
    seedEvent(['latitude' => 40.7128, 'longitude' => -74.0060, 'created_time' => Carbon::now()->addDays(2)->timestamp]); // New York
    seedEvent(['latitude' => 51.5074, 'longitude' => -0.1278, 'created_time' => Carbon::now()->addDays(2)->timestamp]);  // London

    $this->get(route('events.visual1', ['city' => 'London, UK']))
        ->assertInertia(fn ($page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.city', 'London')
        );
});
