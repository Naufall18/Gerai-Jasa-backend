<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('booking_id')->unique();
            $table->uuid('customer_id');
            $table->uuid('vendor_id');
            $table->tinyInteger('rating')->unsigned();
            $table->text('comment')->nullable();
            $table->text('vendor_reply')->nullable();
            $table->timestampTz('replied_at')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestampsTz();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};