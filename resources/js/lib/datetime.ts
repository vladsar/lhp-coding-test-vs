// Event times come from the API as ISO-8601 UTC strings. We format them in the
// viewer's own locale and timezone — `toLocale*` with no explicit timezone uses
// the browser's — so a "global" event reads naturally wherever it's viewed.

function toDate(iso: string | null | undefined): Date | null {
    if (!iso) {
        return null;
    }

    const date = new Date(iso);

    return Number.isNaN(date.getTime()) ? null : date;
}

export function formatEventDate(iso: string | null): string {
    const date = toDate(iso);

    return date
        ? date.toLocaleDateString(undefined, {
              weekday: 'short',
              day: 'numeric',
              month: 'short',
              year: 'numeric',
          })
        : 'Date TBA';
}

export function formatEventTime(iso: string | null): string {
    const date = toDate(iso);

    return date
        ? date.toLocaleTimeString(undefined, {
              hour: 'numeric',
              minute: '2-digit',
          })
        : '';
}

export function formatEventDateTime(iso: string | null): string {
    const date = toDate(iso);

    return date
        ? `${formatEventDate(iso)} · ${formatEventTime(iso)}`
        : 'Date TBA';
}

/** Stable per-day key (in local time) used to group the timeline. */
export function dayKey(iso: string | null): string {
    const date = toDate(iso);

    if (!date) {
        return 'unknown';
    }

    return `${date.getFullYear()}-${`${date.getMonth() + 1}`.padStart(2, '0')}-${`${date.getDate()}`.padStart(2, '0')}`;
}

export function formatDayHeading(iso: string | null): string {
    const date = toDate(iso);

    return date
        ? date.toLocaleDateString(undefined, {
              weekday: 'long',
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          })
        : 'Date to be announced';
}
