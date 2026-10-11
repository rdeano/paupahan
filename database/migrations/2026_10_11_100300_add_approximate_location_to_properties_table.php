<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Public map position: the private pin moved a short random distance, so the house can't be pinpointed.
            // Map search filters on these, never on latitude/longitude.
            $table->decimal('approx_latitude', 10, 7)->nullable()->after('longitude');
            $table->decimal('approx_longitude', 10, 7)->nullable()->after('approx_latitude');
            $table->index(['approx_latitude', 'approx_longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['approx_latitude', 'approx_longitude']);
            $table->dropColumn(['approx_latitude', 'approx_longitude']);
        });
    }
};
