<?php

namespace App\Http\Resources;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Lean event shape for the listing/visual pages. Deliberately omits the heavy
 * `payload` blob — the detail page adds it back on top of this.
 *
 * @mixin Event
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $location = $this->location;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'starts_at' => $this->starts_at,
            'venue' => $this->venue_name,
            'address' => $location['label'] ?? null,
            'city' => $location['city'] ?? null,
            'price' => $this->price_label,
            'images' => $this->image_urls,
            'attendees_count' => $this->whenCounted('attendees'),
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }
}
