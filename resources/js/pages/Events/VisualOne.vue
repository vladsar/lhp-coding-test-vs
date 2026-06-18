<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import EventCard from '@/components/events/EventCard.vue';
import EventEmptyState from '@/components/events/EventEmptyState.vue';
import EventFilters from '@/components/events/EventFilters.vue';
import EventPagination from '@/components/events/EventPagination.vue';
import type {
    EventCard as EventCardType,
    EventFiltersState,
    FilterOptions,
    SimplePaginator,
} from '@/types/events';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Events Visual 1', href: '/events-visual-1' }],
    },
});

defineProps<{
    events: SimplePaginator<EventCardType>;
    filters: EventFiltersState;
    filterOptions: FilterOptions;
}>();
</script>

<template>
    <Head title="Events Visual 1" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-6">
        <header class="space-y-1">
            <h1 class="text-2xl font-semibold tracking-tight">
                Discover events
            </h1>
            <p class="text-sm text-muted-foreground">
                Browse upcoming events as a card grid. Filter by date and
                location, and register in a click.
            </p>
        </header>

        <EventFilters :filters="filters" :options="filterOptions" />

        <div
            v-if="events.data.length"
            class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <EventCard
                v-for="event in events.data"
                :key="event.id"
                :event="event"
            />
        </div>

        <EventEmptyState v-else />

        <EventPagination
            :prev-url="events.prev_page_url"
            :next-url="events.next_page_url"
            :page="events.current_page"
        />
    </div>
</template>
