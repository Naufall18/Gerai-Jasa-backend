<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Admin "all bookings" status filter.
            $table->index('status', 'bookings_status_index');
            // Vendor/customer lists order by created_at within their scope.
            $table->index(['vendor_id', 'created_at'], 'bookings_vendor_created_index');
            $table->index(['customer_id', 'created_at'], 'bookings_customer_created_index');
        });

        Schema::table('otps', function (Blueprint $table) {
            // OTP lookups filter by phone (+ validity) on every auth attempt.
            $table->index(['phone', 'expires_at'], 'otps_phone_expires_index');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->index('status', 'vendors_status_index');
            $table->index('city', 'vendors_city_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_status_index');
            $table->dropIndex('bookings_vendor_created_index');
            $table->dropIndex('bookings_customer_created_index');
        });

        Schema::table('otps', function (Blueprint $table) {
            $table->dropIndex('otps_phone_expires_index');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex('vendors_status_index');
            $table->dropIndex('vendors_city_index');
        });
    }
};
