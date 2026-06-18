<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Attendee;
use App\Models\Event;
use App\Support\CityLocator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    private const TYPES = ['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition'];

    private const STATUSES = ['draft', 'published', 'cancelled', 'sold_out'];

    private const PER_PAGE = 24;

    public function index(Request $request): Response
    {
        return Inertia::render('Events/Index', [
            'filters' => [
                'status' => $request->status,
                'from' => $request->input('from', '2023-01-01'),
            ],
            'statuses' => self::STATUSES,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        [$events, $stats] = $this->loadListing($request);

        return response()->json([
            'data' => $events->items(),
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'total' => $events->total(),
            'stats' => $stats,
        ]);
    }

    /**
     * Visual 1 — card grid / discovery layout.
     */
    public function visualOne(Request $request): Response
    {
        return Inertia::render('Events/VisualOne', $this->listingProps($request));
    }

    /**
     * Visual 2 — chronological timeline layout. Same data and filters as
     * Visual 1, different presentation.
     */
    public function visualTwo(Request $request): Response
    {
        return Inertia::render('Events/VisualTwo', $this->listingProps($request));
    }

    public function show(Event $event): Response
    {
        $event->loadCount('attendees');

        return Inertia::render('Events/Show', [
            'event' => (new EventResource($event))->resolve(request()) + [
                'payload' => $event->payload,
            ],
            'attendees' => $event->attendees()
                ->latest()
                ->limit(50)
                ->get(['id', 'name', 'created_at']),
        ]);
    }

    /**
     * Shared Inertia props for both visual listing pages.
     *
     * @return array<string, mixed>
     */
    private function listingProps(Request $request): array
    {
        $events = $this->filteredEvents($request);
        $this->attachAttendeeCounts($events->items());

        return [
            'events' => $events->through(fn (Event $event) => (new EventResource($event))->resolve($request)),
            'filters' => [
                'status' => $request->input('status'),
                'type' => $request->input('type'),
                'city' => $request->input('city'),
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ],
            'filterOptions' => [
                'statuses' => self::STATUSES,
                'types' => self::TYPES,
                'cities' => CityLocator::cities(),
            ],
        ];
    }

    /**
     * Build the filtered, paginated event listing shared by both visual pages.
     *
     * Uses simplePaginate to avoid a COUNT(*) over the ~1.25M-row table, and
     * defaults to upcoming events when no explicit date range is given.
     *
     * @return Paginator<int, Event>
     */
    private function filteredEvents(Request $request): Paginator
    {
        $box = $request->filled('city') ? CityLocator::boundingBox((string) $request->input('city')) : null;

        return Event::query()
            // Lean projection: select only the columns and the few small JSON
            // fields the cards need, never the heavy `payload` blob. This keeps
            // filtered/sorted listings fast over the ~1.25M-row table (the
            // payload would otherwise be dragged through the sort buffer).
            ->select(['id', 'user_id', 'type', 'status', 'created_time', 'latitude', 'longitude'])
            ->selectRaw("json_extract(payload, '$.name') as lean_name")
            ->selectRaw("json_extract(payload, '$.description') as lean_description")
            ->selectRaw("json_extract(payload, '$.venue.name') as lean_venue")
            ->selectRaw("json_extract(payload, '$.pricing.min_price') as lean_price")
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->input('status')))
            ->when($request->filled('type'), fn (Builder $q) => $q->where('type', $request->input('type')))
            ->when($request->filled('from'), fn (Builder $q) => $q->where('created_time', '>=', $this->dayStart((string) $request->input('from'))))
            ->when($request->filled('to'), fn (Builder $q) => $q->where('created_time', '<=', $this->dayEnd((string) $request->input('to'))))
            ->when(
                ! $request->filled('from') && ! $request->filled('to'),
                fn (Builder $q) => $q->where('created_time', '>=', Carbon::now()->timestamp),
            )
            ->when($box, fn (Builder $q) => $q
                ->whereBetween('latitude', $box['lat'])
                ->whereBetween('longitude', $box['lng']))
            ->orderBy('created_time')
            ->simplePaginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * Load attendee counts for just the events on the current page in a single
     * query. Doing this here (rather than withCount on the listing) keeps the
     * count off the hot filtered/sorted path, where it would otherwise run a
     * correlated subquery for every matched row before the limit applies.
     *
     * @param  array<int, Event>  $events
     */
    private function attachAttendeeCounts(array $events): void
    {
        if ($events === []) {
            return;
        }

        $counts = Attendee::query()
            ->whereIn('event_id', array_map(fn (Event $event) => $event->id, $events))
            ->groupBy('event_id')
            ->selectRaw('event_id, count(*) as aggregate')
            ->pluck('aggregate', 'event_id');

        foreach ($events as $event) {
            $event->setAttribute('attendees_count', (int) ($counts[$event->id] ?? 0));
        }
    }

    private function dayStart(string $date): int
    {
        return Carbon::parse($date, 'UTC')->startOfDay()->getTimestamp();
    }

    private function dayEnd(string $date): int
    {
        return Carbon::parse($date, 'UTC')->endOfDay()->getTimestamp();
    }

    /**
     * @return array{0: LengthAwarePaginator<int, Event>, 1: array{ms: int, bytes: int}}
     */
    private function loadListing(Request $request): array
    {
        $start = microtime(true);

        $events = Event::with('user')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_time')
            ->paginate(50)
            ->withQueryString();

        $stats = [
            'ms' => (int) round((microtime(true) - $start) * 1000),
            'bytes' => strlen((string) json_encode($events->items())),
        ];

        return [$events, $stats];
    }
}
