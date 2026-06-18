<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');

            // Delivery markers. A non-null value means that message has been
            // sent, which is what keeps confirmations and reminders idempotent.
            $table->timestamp('confirmation_sent_at')->nullable();
            $table->timestamp('reminded_3d_at')->nullable();
            $table->timestamp('reminded_24h_at')->nullable();

            $table->timestamps();

            // One registration per email per event.
            $table->unique(['event_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendees');
    }
};
