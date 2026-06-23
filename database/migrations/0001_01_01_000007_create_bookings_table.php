<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('booking_code')->unique();
            $table->uuid('customer_id');
            $table->uuid('vendor_id');
            $table->uuid('service_id');
            $table->uuid('time_slot_id');
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'awaiting_payment'])->default('pending');
            $table->text('notes')->nullable();
            $table->text('special_requests')->nullable();
            $table->decimal('total_price', 12, 2);
            $table->decimal('commission_amount', 12, 2)->default(0.00);
            $table->enum('payment_method', ['cod', 'midtrans', 'xendit']);
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('time_slot_id')->references('id')->on('time_slots')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};