<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_slots', function (Blueprint $table) {
            // Prevent duplicate slots for the same vendor/service/date/time.
            // (Backs the firstOrCreate in SlotGenerationService at the DB level.)
            $table->unique(['vendor_id', 'service_id', 'slot_date', 'slot_time'], 'time_slots_unique_slot');
        });
    }

    public function down(): void
    {
        Schema::table('time_slots', function (Blueprint $table) {
            $table->dropUnique('time_slots_unique_slot');
        });
    }
};
