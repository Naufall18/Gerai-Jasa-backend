<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the FK first so the column can be modified, then re-add it.
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            // Bookings may have no specific service (vendor prices manually).
            $table->uuid('service_id')->nullable()->change();
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            // Note: reverting to NOT NULL fails if any null-service bookings exist.
            $table->uuid('service_id')->nullable(false)->change();
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
        });
    }
};
