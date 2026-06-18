<?php

namespace App\Support;

/**
 * Turns a latitude/longitude into a human-readable "City, Country" without an
 * external geocoding service.
 *
 * The seeder scatters every event within ~0.5° of one of a fixed set of city
 * anchors (see Database\Seeders\EventSeeder). We mirror that same anchor list
 * here with labels, so the nearest anchor to a given point is, in practice,
 * the city the event was generated around. This is an approximation — good
 * enough for a readable label and for grouping events by city — and it keeps
 * the whole feature local, deterministic and dependency-free.
 */
class CityLocator
{
    /**
     * [latitude, longitude, city, country]. Coordinates match the anchors in
     * EventSeeder so the nearest lookup resolves back to the seeded city.
     *
     * @var list<array{0: float, 1: float, 2: string, 3: string}>
     */
    private const ANCHORS = [
        // United States
        [40.7128, -74.0060, 'New York', 'USA'],
        [34.0522, -118.2437, 'Los Angeles', 'USA'],
        [41.8781, -87.6298, 'Chicago', 'USA'],
        [29.7604, -95.3698, 'Houston', 'USA'],
        [33.4484, -112.0740, 'Phoenix', 'USA'],
        [39.9526, -75.1652, 'Philadelphia', 'USA'],
        [29.4241, -98.4936, 'San Antonio', 'USA'],
        [32.7157, -117.1611, 'San Diego', 'USA'],
        [32.7767, -96.7970, 'Dallas', 'USA'],
        [37.3382, -121.8863, 'San Jose', 'USA'],
        [30.2672, -97.7431, 'Austin', 'USA'],
        [37.7749, -122.4194, 'San Francisco', 'USA'],
        [47.6062, -122.3321, 'Seattle', 'USA'],
        [39.7392, -104.9903, 'Denver', 'USA'],
        [42.3601, -71.0589, 'Boston', 'USA'],
        [36.1699, -115.1398, 'Las Vegas', 'USA'],
        [25.7617, -80.1918, 'Miami', 'USA'],
        [33.7490, -84.3880, 'Atlanta', 'USA'],
        [38.9072, -77.0369, 'Washington', 'USA'],
        [36.1627, -86.7816, 'Nashville', 'USA'],
        [45.5152, -122.6784, 'Portland', 'USA'],
        [29.9511, -90.0715, 'New Orleans', 'USA'],
        // Canada
        [43.6532, -79.3832, 'Toronto', 'Canada'],
        [45.5019, -73.5674, 'Montreal', 'Canada'],
        [49.2827, -123.1207, 'Vancouver', 'Canada'],
        [51.0447, -114.0719, 'Calgary', 'Canada'],
        [45.4215, -75.6972, 'Ottawa', 'Canada'],
        [53.5461, -113.4938, 'Edmonton', 'Canada'],
        [46.8139, -71.2080, 'Quebec City', 'Canada'],
        [49.8951, -97.1384, 'Winnipeg', 'Canada'],
        // Mexico
        [19.4326, -99.1332, 'Mexico City', 'Mexico'],
        [20.6597, -103.3496, 'Guadalajara', 'Mexico'],
        [25.6866, -100.3161, 'Monterrey', 'Mexico'],
        [19.0414, -98.2063, 'Puebla', 'Mexico'],
        [32.5149, -117.0382, 'Tijuana', 'Mexico'],
        [21.1619, -86.8515, 'Cancún', 'Mexico'],
        [20.9674, -89.5926, 'Mérida', 'Mexico'],
        // Europe
        [51.5074, -0.1278, 'London', 'UK'],
        [48.8566, 2.3522, 'Paris', 'France'],
        [52.5200, 13.4050, 'Berlin', 'Germany'],
        [40.4168, -3.7038, 'Madrid', 'Spain'],
        [41.9028, 12.4964, 'Rome', 'Italy'],
        [52.3676, 4.9041, 'Amsterdam', 'Netherlands'],
        [41.3851, 2.1734, 'Barcelona', 'Spain'],
        [48.1351, 11.5820, 'Munich', 'Germany'],
        [45.4642, 9.1900, 'Milan', 'Italy'],
        [48.2082, 16.3738, 'Vienna', 'Austria'],
        [50.0755, 14.4378, 'Prague', 'Czechia'],
        [38.7223, -9.1393, 'Lisbon', 'Portugal'],
        [53.3498, -6.2603, 'Dublin', 'Ireland'],
        [55.6761, 12.5683, 'Copenhagen', 'Denmark'],
        [59.3293, 18.0686, 'Stockholm', 'Sweden'],
        [59.9139, 10.7522, 'Oslo', 'Norway'],
        [60.1699, 24.9384, 'Helsinki', 'Finland'],
        [50.8503, 4.3517, 'Brussels', 'Belgium'],
        [47.3769, 8.5417, 'Zurich', 'Switzerland'],
        [52.2297, 21.0122, 'Warsaw', 'Poland'],
        [47.4979, 19.0402, 'Budapest', 'Hungary'],
        [37.9838, 23.7275, 'Athens', 'Greece'],
        [45.7640, 4.8357, 'Lyon', 'France'],
        [53.5511, 9.9937, 'Hamburg', 'Germany'],
        [53.4808, -2.2426, 'Manchester', 'UK'],
        [55.9533, -3.1883, 'Edinburgh', 'UK'],
        [50.1109, 8.6821, 'Frankfurt', 'Germany'],
        [50.0647, 19.9450, 'Kraków', 'Poland'],
        [41.1579, -8.6291, 'Porto', 'Portugal'],
        [40.8518, 14.2681, 'Naples', 'Italy'],
        // Global hubs
        [35.6762, 139.6503, 'Tokyo', 'Japan'],
        [37.5665, 126.9780, 'Seoul', 'South Korea'],
        [1.3521, 103.8198, 'Singapore', 'Singapore'],
        [-33.8688, 151.2093, 'Sydney', 'Australia'],
        [-37.8136, 144.9631, 'Melbourne', 'Australia'],
        [25.2048, 55.2708, 'Dubai', 'UAE'],
        [-23.5505, -46.6333, 'São Paulo', 'Brazil'],
        [-34.6037, -58.3816, 'Buenos Aires', 'Argentina'],
    ];

    /**
     * Half-width, in degrees, of the bounding box used when filtering events
     * by city. Slightly wider than the seeder's ~0.5° jitter so the whole
     * cluster around an anchor is captured.
     */
    private const BOX_HALF_DEGREES = 0.6;

    /**
     * Nearest city to a point.
     *
     * @return array{city: string, country: string, label: string}|null
     */
    public static function nearest(?float $lat, ?float $lng): ?array
    {
        if ($lat === null || $lng === null) {
            return null;
        }

        $best = null;
        $bestDistance = INF;

        foreach (self::ANCHORS as [$aLat, $aLng, $city, $country]) {
            // Squared euclidean distance is enough to rank well-separated
            // anchors; no need for a true great-circle distance here.
            $distance = (($lat - $aLat) ** 2) + (($lng - $aLng) ** 2);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = ['city' => $city, 'country' => $country, 'label' => "{$city}, {$country}"];
            }
        }

        return $best;
    }

    /**
     * Sorted list of selectable city labels for the location filter.
     *
     * @return list<string>
     */
    public static function cities(): array
    {
        $labels = array_map(fn ($a) => "{$a[2]}, {$a[3]}", self::ANCHORS);
        sort($labels);

        return $labels;
    }

    /**
     * Bounding box for a city label, for use in a whereBetween() filter.
     *
     * @return array{lat: array{0: float, 1: float}, lng: array{0: float, 1: float}}|null
     */
    public static function boundingBox(string $label): ?array
    {
        foreach (self::ANCHORS as [$lat, $lng, $city, $country]) {
            if ("{$city}, {$country}" === $label) {
                return [
                    'lat' => [$lat - self::BOX_HALF_DEGREES, $lat + self::BOX_HALF_DEGREES],
                    'lng' => [$lng - self::BOX_HALF_DEGREES, $lng + self::BOX_HALF_DEGREES],
                ];
            }
        }

        return null;
    }
}
