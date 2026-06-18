<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The listing always orders by the event start time, so an index on
     * `created_time` lets every filtered query (including the city bounding
     * box) scan in date order and stop at the page limit, instead of a full
     * scan across the ~1.25M seeded rows.
     *
     * We deliberately do NOT index (latitude, longitude): because the listing
     * is always ordered by date, a lat/lng index only tempts the planner into
     * gathering the whole city cluster and sorting it (slow). Filtering the
     * box inline during the date-ordered scan is consistently faster here.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index('created_time');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['created_time']);
        });
    }
};
