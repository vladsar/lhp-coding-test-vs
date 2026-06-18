<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    prevUrl: string | null;
    nextUrl: string | null;
    page: number;
}>();
</script>

<template>
    <nav class="flex items-center justify-between border-t pt-4">
        <p class="text-sm text-muted-foreground">Page {{ page }}</p>
        <div class="flex items-center gap-2">
            <Button v-if="prevUrl" as-child variant="outline" size="sm">
                <Link :href="prevUrl" preserve-state>
                    <ChevronLeft class="mr-1 size-4" />
                    Previous
                </Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>
                <ChevronLeft class="mr-1 size-4" />
                Previous
            </Button>

            <Button v-if="nextUrl" as-child variant="outline" size="sm">
                <Link :href="nextUrl" preserve-state>
                    Next
                    <ChevronRight class="ml-1 size-4" />
                </Link>
            </Button>
            <Button v-else variant="outline" size="sm" disabled>
                Next
                <ChevronRight class="ml-1 size-4" />
            </Button>
        </div>
    </nav>
</template>
