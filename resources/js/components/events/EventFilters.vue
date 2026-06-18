<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { RotateCcw, SlidersHorizontal } from '@lucide/vue';
import { computed, reactive, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { humanize } from '@/lib/events';
import type { EventFiltersState, FilterOptions } from '@/types/events';

const props = defineProps<{
    filters: EventFiltersState;
    options: FilterOptions;
}>();

const page = usePage();
const basePath = computed(() => page.url.split('?')[0]);

const form = reactive({
    status: props.filters.status ?? '',
    type: props.filters.type ?? '',
    city: props.filters.city ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

// Keep the controls in sync if the applied filters change (e.g. browser back).
watch(
    () => props.filters,
    (filters) => {
        form.status = filters.status ?? '';
        form.type = filters.type ?? '';
        form.city = filters.city ?? '';
        form.from = filters.from ?? '';
        form.to = filters.to ?? '';
    },
);

const hasActiveFilters = computed(() =>
    Object.values(form).some((value) => value !== ''),
);

const selectClass =
    'h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

function apply() {
    // Drop empty values and reset to page 1 by navigating to the base path.
    const query = Object.fromEntries(
        Object.entries(form).filter(([, value]) => value !== ''),
    );
    router.get(basePath.value, query, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function reset() {
    router.get(
        basePath.value,
        {},
        { preserveScroll: true, preserveState: true, replace: true },
    );
}
</script>

<template>
    <form
        class="rounded-xl border bg-card p-4 shadow-xs"
        @submit.prevent="apply"
    >
        <div class="mb-3 flex items-center gap-2 text-sm font-medium">
            <SlidersHorizontal class="size-4" />
            Filters
        </div>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
            <div class="flex flex-col gap-1.5">
                <Label for="filter-from" class="text-xs text-muted-foreground"
                    >From</Label
                >
                <input
                    id="filter-from"
                    v-model="form.from"
                    type="date"
                    :class="selectClass"
                />
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="filter-to" class="text-xs text-muted-foreground"
                    >To</Label
                >
                <input
                    id="filter-to"
                    v-model="form.to"
                    type="date"
                    :class="selectClass"
                />
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="filter-city" class="text-xs text-muted-foreground"
                    >Location</Label
                >
                <select
                    id="filter-city"
                    v-model="form.city"
                    :class="selectClass"
                >
                    <option value="">All locations</option>
                    <option
                        v-for="city in options.cities"
                        :key="city"
                        :value="city"
                    >
                        {{ city }}
                    </option>
                </select>
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="filter-type" class="text-xs text-muted-foreground"
                    >Type</Label
                >
                <select
                    id="filter-type"
                    v-model="form.type"
                    :class="selectClass"
                >
                    <option value="">All types</option>
                    <option
                        v-for="type in options.types"
                        :key="type"
                        :value="type"
                    >
                        {{ humanize(type) }}
                    </option>
                </select>
            </div>

            <div class="flex flex-col gap-1.5">
                <Label for="filter-status" class="text-xs text-muted-foreground"
                    >Status</Label
                >
                <select
                    id="filter-status"
                    v-model="form.status"
                    :class="selectClass"
                >
                    <option value="">Any status</option>
                    <option
                        v-for="status in options.statuses"
                        :key="status"
                        :value="status"
                    >
                        {{ humanize(status) }}
                    </option>
                </select>
            </div>

            <div
                class="col-span-2 flex items-end gap-2 md:col-span-3 lg:col-span-1"
            >
                <Button type="submit" class="flex-1">Apply</Button>
                <Button
                    v-if="hasActiveFilters"
                    type="button"
                    variant="ghost"
                    size="icon"
                    title="Clear filters"
                    @click="reset"
                >
                    <RotateCcw class="size-4" />
                </Button>
            </div>
        </div>
    </form>
</template>
