<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Clock, MapPin, Users } from '@lucide/vue';
import { computed } from 'vue';
import EventEmptyState from '@/components/events/EventEmptyState.vue';
import EventFilters from '@/components/events/EventFilters.vue';
import EventPagination from '@/components/events/EventPagination.vue';
import RegisterDialog from '@/components/events/RegisterDialog.vue';
import { Badge } from '@/components/ui/badge';
import { dayKey, formatDayHeading, formatEventTime } from '@/lib/datetime';
import { humanize, statusVariant } from '@/lib/events';
import type {
    EventCard as EventCardType,
    EventFiltersState,
    FilterOptions,
    SimplePaginator,
} from '@/types/events';

const props = defineProps<{
    events: SimplePaginator<EventCardType>;
    filters: EventFiltersState;
    filterOptions: FilterOptions;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Events Visual 2', href: '/events-visual-2' }],
    },
});

// Events arrive ordered by start time, so we can fold them into day groups in
// one pass while preserving order.
const groups = computed(() => {
    const byDay = new Map<
        string,
        { key: string; heading: string; events: EventCardType[] }
    >();

    for (const event of props.events.data) {
        const key = dayKey(event.starts_at);

        if (!byDay.has(key)) {
            byDay.set(key, {
                key,
                heading: formatDayHeading(event.starts_at),
                events: [],
            });
        }

        byDay.get(key)!.events.push(event);
    }

    return [...byDay.values()];
});
</script>

<template>
    <Head title="Events Visual 2" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
        <header class="space-y-1">
            <h1 class="text-2xl font-semibold tracking-tight">
                Events timeline
            </h1>
            <p class="text-sm text-muted-foreground">
                The same events, laid out chronologically and grouped by day.
            </p>
        </header>

        <EventFilters :filters="filters" :options="filterOptions" />

        <div v-if="events.data.length" class="flex flex-col gap-8">
            <section v-for="group in groups" :key="group.key" class="space-y-3">
                <h2 class="text-sm font-semibold text-muted-foreground">
                    {{ group.heading }}
                </h2>

                <ol class="relative ml-1 space-y-3 border-l pl-6">
                    <li
                        v-for="event in group.events"
                        :key="event.id"
                        class="relative"
                    >
                        <span
                            class="absolute top-5 -left-[27px] size-2.5 rounded-full border-2 border-background bg-primary"
                        />

                        <div
                            class="flex gap-4 rounded-lg border bg-card p-3 text-card-foreground transition-colors hover:bg-accent/40"
                        >
                            <img
                                :src="event.images[0]"
                                :alt="event.title"
                                loading="lazy"
                                class="size-16 shrink-0 rounded-md object-cover sm:size-20"
                            />

                            <div class="flex min-w-0 flex-1 flex-col gap-1">
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground"
                                        >
                                            <Clock class="size-3.5" />
                                            {{
                                                formatEventTime(
                                                    event.starts_at,
                                                ) || 'Time TBA'
                                            }}
                                        </p>
                                        <h3 class="truncate font-semibold">
                                            {{ event.title }}
                                        </h3>
                                    </div>
                                    <div class="flex shrink-0 gap-1.5">
                                        <Badge
                                            variant="secondary"
                                            class="hidden sm:inline-flex"
                                            >{{ humanize(event.type) }}</Badge
                                        >
                                        <Badge
                                            :variant="
                                                statusVariant(event.status)
                                            "
                                            >{{ humanize(event.status) }}</Badge
                                        >
                                    </div>
                                </div>

                                <p
                                    class="flex items-center gap-1.5 truncate text-sm text-muted-foreground"
                                >
                                    <MapPin class="size-3.5 shrink-0" />
                                    <span class="truncate">
                                        <template v-if="event.venue"
                                            >{{ event.venue }} · </template
                                        >{{ event.address ?? 'Location TBA' }}
                                    </span>
                                </p>

                                <p
                                    v-if="event.description"
                                    class="line-clamp-1 text-sm text-muted-foreground"
                                >
                                    {{ event.description }}
                                </p>

                                <div
                                    class="mt-1 flex items-center justify-between gap-3"
                                >
                                    <div
                                        class="flex items-center gap-3 text-sm"
                                    >
                                        <span class="font-semibold">{{
                                            event.price ?? '—'
                                        }}</span>
                                        <span
                                            class="flex items-center gap-1 text-xs text-muted-foreground"
                                        >
                                            <Users class="size-3.5" />
                                            {{ event.attendees_count }}
                                        </span>
                                    </div>
                                    <RegisterDialog
                                        :event="event"
                                        button-size="sm"
                                        button-variant="outline"
                                    />
                                </div>
                            </div>
                        </div>
                    </li>
                </ol>
            </section>
        </div>

        <EventEmptyState v-else />

        <EventPagination
            :prev-url="events.prev_page_url"
            :next-url="events.next_page_url"
            :page="events.current_page"
        />
    </div>
</template>
