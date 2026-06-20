<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->string('ip_address')->nullable()->after('device_name');
            $table->string('city')->nullable()->after('ip_address');
            $table->string('region')->nullable()->after('city');
            $table->string('country')->nullable()->after('region');
            $table->string('isp')->nullable()->after('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'city', 'region', 'country', 'isp']);
        });
    }
};
