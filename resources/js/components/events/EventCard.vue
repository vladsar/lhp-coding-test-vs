<script setup lang="ts">
import { CalendarDays, MapPin, Users } from '@lucide/vue';
import RegisterDialog from '@/components/events/RegisterDialog.vue';
import { Badge } from '@/components/ui/badge';
import { formatEventDateTime } from '@/lib/datetime';
import { humanize, statusVariant } from '@/lib/events';
import type { EventCard } from '@/types/events';

defineProps<{ event: EventCard }>();
</script>

<template>
    <article
        class="flex flex-col overflow-hidden rounded-xl border bg-card text-card-foreground shadow-xs transition-shadow hover:shadow-md"
    >
        <div class="relative aspect-[16/10] overflow-hidden bg-muted">
            <img
                :src="event.images[0]"
                :alt="event.title"
                loading="lazy"
                class="h-full w-full object-cover"
            />
            <Badge
                variant="secondary"
                class="absolute top-3 left-3 capitalize backdrop-blur"
                >{{ humanize(event.type) }}</Badge
            >
            <Badge
                :variant="statusVariant(event.status)"
                class="absolute top-3 right-3"
                >{{ humanize(event.status) }}</Badge
            >
        </div>

        <div class="flex flex-1 flex-col gap-3 p-4">
            <h3 class="line-clamp-2 leading-snug font-semibold">
                {{ event.title }}
            </h3>

            <div class="space-y-1.5 text-sm text-muted-foreground">
                <p class="flex items-center gap-2">
                    <CalendarDays class="size-4 shrink-0" />
                    <span>{{ formatEventDateTime(event.starts_at) }}</span>
                </p>
                <p class="flex items-center gap-2">
                    <MapPin class="size-4 shrink-0" />
                    <span class="truncate">
                        <template v-if="event.venue"
                            >{{ event.venue }} · </template
                        >{{ event.address ?? 'Location TBA' }}
                    </span>
                </p>
            </div>

            <p
                v-if="event.description"
                class="line-clamp-2 text-sm text-muted-foreground"
            >
                {{ event.description }}
            </p>

            <div class="mt-auto flex items-center justify-between pt-2">
                <div class="flex items-center gap-3">
                    <span class="font-semibold">{{ event.price ?? '—' }}</span>
                    <span
                        class="flex items-center gap-1 text-xs text-muted-foreground"
                    >
                        <Users class="size-3.5" />
                        {{ event.attendees_count }}
                    </span>
                </div>
                <RegisterDialog :event="event" button-size="sm" />
            </div>
        </div>
    </article>
</template>
