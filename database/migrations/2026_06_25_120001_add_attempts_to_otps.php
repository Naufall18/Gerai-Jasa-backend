<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            // Tracks failed verification attempts so an OTP can be locked out
            // before the full 6-digit space could be brute-forced.
            $table->unsignedTinyInteger('attempts')->default(0)->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropColumn('attempts');
        });
    }
};
