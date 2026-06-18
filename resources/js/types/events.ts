export interface EventCard {
    id: string;
    title: string;
    description: string | null;
    type: string;
    status: string;
    starts_at: string | null;
    venue: string | null;
    address: string | null;
    city: string | null;
    price: string | null;
    images: string[];
    attendees_count: number;
    lat: number | null;
    lng: number | null;
}

export interface EventFiltersState {
    status: string | null;
    type: string | null;
    city: string | null;
    from: string | null;
    to: string | null;
}

export interface FilterOptions {
    statuses: string[];
    types: string[];
    cities: string[];
}

/** The subset of Laravel's simplePaginate payload the pages use. */
export interface SimplePaginator<T> {
    data: T[];
    current_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}
