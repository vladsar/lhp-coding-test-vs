<?php

namespace App\Console\Commands;

use App\Mail\EventReminder;
use App\Models\Attendee;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Queue reminder emails for attendees of events 3 days and 24 hours out';

    private const HOUR = 3600;

    public function handle(): int
    {
        $now = Carbon::now()->getTimestamp();

        // Windows are based on the actual event start time (events.created_time)
        // and do not overlap, so an attendee never receives both reminders at
        // once: an event lands in the 24-hour window only once it is within a
        // day, and in the 3-day window only while it is 1–3 days out.
        $threeDay = $this->sendWindow(
            column: 'reminded_3d_at',
            window: '3 days',
            after: $now + 24 * self::HOUR,            // > 24h out
            throughInclusive: $now + 72 * self::HOUR, // <= 72h out
        );

        $oneDay = $this->sendWindow(
            column: 'reminded_24h_at',
            window: '24 hours',
            after: $now,                              // still upcoming
            throughInclusive: $now + 24 * self::HOUR, // <= 24h out
        );

        $this->info("Queued {$threeDay} three-day and {$oneDay} 24-hour reminders.");

        return self::SUCCESS;
    }

    /**
     * Queue a reminder for every not-yet-reminded attendee whose event starts
     * within (after, throughInclusive], then stamp the marker column so the
     * same reminder is never queued twice.
     */
    private function sendWindow(string $column, string $window, int $after, int $throughInclusive): int
    {
        $sent = 0;

        Attendee::query()
            ->whereNull($column)
            ->whereHas('event', fn (Builder $query) => $query
                ->where('created_time', '>', $after)
                ->where('created_time', '<=', $throughInclusive))
            ->with('event')
            ->chunkById(500, function ($attendees) use ($column, $window, &$sent) {
                foreach ($attendees as $attendee) {
                    Mail::to($attendee->email)->queue(
                        new EventReminder($attendee->event, $attendee, $window),
                    );

                    $attendee->forceFill([$column => Carbon::now()])->save();
                    $sent++;
                }
            });

        return $sent;
    }
}
