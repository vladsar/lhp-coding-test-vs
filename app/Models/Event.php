<?php

namespace App\Models;

use App\Support\CityLocator;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int $user_id
 * @property string $type
 * @property string $status
 * @property int|null $created_time
 * @property float|null $latitude
 * @property float|null $longitude
 * @property array<string, mixed> $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $title
 * @property-read string|null $description
 * @property-read string|null $venue_name
 * @property-read string|null $price_label
 * @property-read string|null $starts_at
 * @property-read string $starts_at_long
 * @property-read array{city: string, country: string, label: string}|null $location
 * @property-read list<string> $image_urls
 * @property-read User $user
 * @property-read Collection<int, Attendee> $attendees
 * @property-read int|null $attendees_count
 */
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUuids;

    /** Number of local placeholder images shown per event. */
    private const IMAGES_PER_EVENT = 3;

    /** Size of the placeholder image pool in public/images/events. */
    private const IMAGE_POOL_SIZE = 12;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'created_time' => 'integer',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Attendee, $this>
     */
    public function attendees(): HasMany
    {
        return $this->hasMany(Attendee::class);
    }

    /**
     * Event start time as an ISO-8601 (UTC) string. `created_time` is a Unix
     * timestamp that, in this dataset, holds the event start time (it mirrors
     * payload.schedule.starts_at), not the record creation time. The frontend
     * renders it in the viewer's local timezone.
     */
    public function getStartsAtAttribute(): ?string
    {
        return $this->created_time !== null ? gmdate('c', $this->created_time) : null;
    }

    /**
     * Long, human-readable start time for emails. Rendered in UTC on purpose:
     * unlike the web UI, an email has no viewer timezone to localize to.
     */
    public function getStartsAtLongAttribute(): string
    {
        return $this->starts_at !== null
            ? Carbon::parse($this->starts_at)->format('l, j F Y \a\t H:i T')
            : 'a date to be announced';
    }

    public function getTitleAttribute(): string
    {
        $name = $this->displayValue('lean_name', ['name']);

        return is_string($name) ? $name : 'Untitled event';
    }

    public function getDescriptionAttribute(): ?string
    {
        $description = $this->displayValue('lean_description', ['description']);

        return is_string($description) ? $description : null;
    }

    public function getVenueNameAttribute(): ?string
    {
        $name = $this->displayValue('lean_venue', ['venue', 'name']);

        return is_string($name) ? $name : null;
    }

    public function getPriceLabelAttribute(): ?string
    {
        $price = $this->displayValue('lean_price', ['pricing', 'min_price']);

        if (! is_numeric($price)) {
            return null;
        }

        return (float) $price <= 0 ? 'Free' : '$'.number_format((float) $price, 2);
    }

    /**
     * Read a display value from an eagerly-selected "lean" alias when present
     * — the listing selects just these few fields so it never has to load the
     * heavy payload blob — otherwise fall back to the decoded payload (used by
     * the detail page, which has the whole payload loaded).
     *
     * @param  list<string>  $payloadPath
     */
    private function displayValue(string $leanAlias, array $payloadPath): mixed
    {
        if (array_key_exists($leanAlias, $this->attributes)) {
            return $this->attributes[$leanAlias];
        }

        $value = $this->payload;
        foreach ($payloadPath as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Human-readable location derived from the coordinates.
     *
     * @return array{city: string, country: string, label: string}|null
     */
    public function getLocationAttribute(): ?array
    {
        return CityLocator::nearest($this->latitude, $this->longitude);
    }

    /**
     * Two-or-more local placeholder image URLs, chosen deterministically from
     * the pool so an event always shows the same images.
     *
     * @return list<string>
     */
    public function getImageUrlsAttribute(): array
    {
        $start = ((int) sprintf('%u', crc32((string) $this->id))) % self::IMAGE_POOL_SIZE;

        $urls = [];
        for ($i = 0; $i < self::IMAGES_PER_EVENT; $i++) {
            // Step by 4 so the picks are distinct within a 12-image pool.
            $n = (($start + $i * 4) % self::IMAGE_POOL_SIZE) + 1;
            $urls[] = asset("images/events/event-{$n}.svg");
        }

        return $urls;
    }
}
