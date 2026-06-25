<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Protect financial / historical records from accidental hard-deletes.
 *
 * The app soft-deletes users/vendors/bookings, but the DB-level CASCADE meant a
 * hard-delete of a user or vendor would wipe their bookings (and via payments,
 * the financial trail). We switch ownership FKs to RESTRICT so such entities
 * must be soft-deleted instead, and SET NULL for the optional service link so a
 * (soft-)deleted service never endangers booking history.
 *
 * payments.booking_id and reviews.booking_id keep CASCADE: a payment/review is
 * part of its booking's lifecycle (the booking is the aggregate root).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['service_id']);
            $table->dropForeign(['time_slot_id']);
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('restrict');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');
            $table->foreign('time_slot_id')->references('id')->on('time_slots')->onDelete('restrict');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['vendor_id']);
        });
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['vendor_id']);
            $table->dropForeign(['service_id']);
            $table->dropForeign(['time_slot_id']);
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('time_slot_id')->references('id')->on('time_slots')->onDelete('cascade');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['vendor_id']);
        });
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
        });
    }
};
