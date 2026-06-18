<x-mail::message>
# You're on the list 🎉

Hi {{ $attendee->name }},

Thanks for registering — your spot for **{{ $event->title }}** is confirmed.

- **When:** {{ $event->starts_at_long }}
- **Where:** {{ $event->venue_name ?? 'Venue TBA' }}@if($event->location), {{ $event->location['label'] }}@endif

We'll send you a reminder as the event approaches.

See you there,<br>
{{ config('app.name') }}
</x-mail::message>
