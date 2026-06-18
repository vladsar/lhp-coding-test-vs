import type { BadgeVariants } from '@/components/ui/badge';

/** "sold_out" -> "Sold Out", "concert" -> "Concert". */
export function humanize(value: string): string {
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

export function statusVariant(status: string): BadgeVariants['variant'] {
    switch (status) {
        case 'published':
            return 'default';
        case 'sold_out':
            return 'secondary';
        case 'cancelled':
            return 'destructive';
        default:
            return 'outline';
    }
}
