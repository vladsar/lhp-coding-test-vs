# Implementation notes

Short notes on the decisions and assumptions behind the Event Visuals build.

## Two visual pages

Same data, same filters, same attendance action — two clearly different layouts:

- **Visual 1** (`Events/VisualOne.vue`) — a responsive card grid / discovery page.
  Each card has a hero image, type/status badges, title, local date/time, venue +
  city, description, price and attendee count.
- **Visual 2** (`Events/VisualTwo.vue`) — a single-column chronological timeline
  grouped by day, with a connector rail, small square thumbnails and the start time
  brought to the front. Narrower layout, different rhythm from the grid.

No interactive map was used on purpose: it adds external libraries, API keys and
extra JS for little benefit here. Both pages are server-rendered via Inertia with
simple (prev/next) pagination, which keeps them stable and easy to review.

Shared, reusable frontend pieces:

- `components/events/EventFilters.vue` — date + location + type/status controls that
  navigate with the query string (used by both pages).
- `components/events/RegisterDialog.vue` — the attendance dialog (Inertia `<Form>`
  posting to the attendee endpoint), used by the cards and timeline rows.
- `components/events/EventPagination.vue` — prev/next pagination.
- `lib/datetime.ts` — date/time formatting (renders in the viewer's local timezone).
- `lib/events.ts` — status badge variants and label humanizing.

No new UI dependencies were added — everything uses the components already shipped
with the starter kit (shadcn-vue / reka-ui, lucide, vue-sonner) and Tailwind.

## Key assumption: `created_time` is the event start time

The `events.created_time` column is a Unix timestamp. Despite the name, in this
dataset it holds the **event start time**, not the record creation time:

- its values span roughly one year either side of "now" (2025-06 → 2027-06);
- it matches `payload.schedule.starts_at` exactly (checked across sampled rows);
- the real record-creation time is the separate `created_at` column.

So `created_time` is used for display, ordering, date filtering and the reminder
windows. (`Event::starts_at` exposes it as an ISO-8601 UTC string.)

## Addresses from lat/lng — `App\Support\CityLocator`

Events only carry latitude/longitude. Rather than call an external geocoding API
(slow, needs keys, and impractical to run per row over 1.25M events), the seeder's
own approach is mirrored: it scatters every event within ~0.5° of a fixed set of
city anchors. `CityLocator` holds that same anchor list with labels and returns the
**nearest** one as a readable `City, Country`. It also powers the city filter.

This is an approximation (nearest anchor, not true reverse geocoding) but it is
local, deterministic, dependency-free and accurate for this dataset. The location
filter uses a small lat/lng **bounding box** around the chosen city's anchor.

## Images — local, 2+ per event

Events had no images. A pool of 12 local placeholder SVGs lives in
`public/images/events/`. `Event::image_urls` returns three of them, chosen
deterministically from the event id, so an event always shows the same images and
the grid still looks varied. Files are served locally (no external/hotlinked URLs),
and the same placeholders are reused as the brief allows. A real upload flow could
be layered on later without changing the read path.

## Date & time / timezones

`created_time` is treated as UTC. The API returns `starts_at` as an ISO-8601 UTC
string and the frontend formats it in the **viewer's local timezone**. Events are
global, so storing/serving UTC and localising at display time is the simplest
sensible behaviour. Date filters (`from`/`to`) are interpreted as UTC days.

## Attendees & emails

- `POST /events/{event}/attendees` registers interest. **Duplicate attendance** is
  prevented at two levels: a `unique(event_id, email)` DB constraint, and a
  validation rule that returns a friendly error (`firstOrCreate` is the race
  backstop). Emails are normalized to lowercase before validation/storage, so
  duplicate registration is case-insensitive.
- A **confirmation email** is queued on registration (`confirmation_sent_at` is
  stamped so it is sent once).
- **Reminders** (`events:send-reminders`, scheduled hourly) email each attendee
  3 days and 24 hours before the event. They are based on the actual event start
  time (`created_time`). **Duplicate reminders** are prevented by `reminded_3d_at` /
  `reminded_24h_at` marker columns (only null markers are sent, then stamped), and
  the two windows do not overlap — `(now+24h, now+72h]` for the 3-day reminder and
  `(now, now+24h]` for the 24-hour one — so an attendee never gets both at once and
  events that have already started are skipped.

Mail uses the `log` driver in local dev (visible in `storage/logs`) and `array` in
tests. Queue is the `database` driver; run `php artisan queue:work` to process the
queued mail locally.

## Performance on the 1.25M-row dataset

The listing is built for the real dataset, not toy data:

- **Lean projection.** The listing selects only the columns and the few small JSON
  fields the cards need (`json_extract` of name/description/venue/price), never the
  heavy `payload` blob. Dragging `payload` through a sort was the single biggest
  cost (a city filter went from ~1.4s to ~35ms). The detail page still loads the
  full payload (single row). The model accessors prefer the lean alias and fall
  back to `payload`, so there is one shaping path (`EventResource`) for both.
- **No `withCount` on the listing.** A correlated count subquery would run for every
  matched row before the limit. Attendee counts are instead loaded in one follow-up
  query over just the current page's 24 ids.
- **Index + statistics.** A single `created_time` index lets every filtered query
  scan in date order and stop at the page limit. A `(latitude, longitude)` index was
  deliberately *removed* — because the listing is always ordered by date, it only
  tempted the planner into gathering and sorting a whole city cluster. `ANALYZE`
  (run at the end of seeding) gives the planner the statistics it needs to avoid the
  non-selective `status` index for the same reason.
- **`simplePaginate`** avoids a `COUNT(*)` over the whole table; the listing defaults
  to upcoming events.

With these in place, common filter combinations return in single-digit to ~100ms;
the rare worst case (a specific city + an uncommon type) is ~400ms.

## Testing

`php artisan test` — feature tests cover attendee registration (including the
duplicate guard and per-event uniqueness), the reminder windows (3-day vs 24-hour,
idempotency, no-overlap, past-event exclusion), and the listing filters (status,
type, date range, city bounding box, default-upcoming, shaped output). The existing
event/auth tests still pass.

## Tooling / scope

- All files added or changed pass `pint` and `phpstan` (level 7).
- PHPStan still reports a handful of pre-existing issues in `EventSeeder.php` /
  `EventFactory.php` (test-provided files); those were left as-is, out of scope.
- New-style `Attribute` accessors were avoided in the model in favour of classic
  `get…Attribute` accessors: the modern API trips a known phpstan false positive on
  its invariant `TGet` template, and the classic form is equally idiomatic.
