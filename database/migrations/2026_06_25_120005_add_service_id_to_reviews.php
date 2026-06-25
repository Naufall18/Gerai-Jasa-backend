<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ReviewController persists `service_id`, the Review model declares it fillable,
 * and the frontend Review type expects it — but the column was never created,
 * so submitting a review failed with "unknown column service_id". Add it
 * (nullable, SET NULL on service delete to preserve the review).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->uuid('service_id')->nullable()->after('vendor_id');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });
    }
};
