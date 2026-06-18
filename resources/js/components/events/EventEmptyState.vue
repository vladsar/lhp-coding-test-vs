<script setup lang="ts">
import { CalendarX2 } from '@lucide/vue';
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-2 rounded-xl border border-dashed py-16 text-center"
    >
        <CalendarX2 class="size-8 text-muted-foreground" />
        <p class="font-medium">No events match these filters</p>
        <p class="text-sm text-muted-foreground">
            Try widening the date range or clearing the location.
        </p>
    </div>
</template>
