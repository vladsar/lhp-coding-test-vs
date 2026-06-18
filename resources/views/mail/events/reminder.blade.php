<x-mail::message>
# {{ $event->title }} is in {{ $window }}

Hi {{ $attendee->name }},

This is a friendly reminder that **{{ $event->title }}** is coming up in {{ $window }}.

- **When:** {{ $event->starts_at_long }}
- **Where:** {{ $event->venue_name ?? 'Venue TBA' }}@if($event->location), {{ $event->location['label'] }}@endif

Looking forward to seeing you,<br>
{{ config('app.name') }}
</x-mail::message>
