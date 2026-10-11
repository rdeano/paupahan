<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Listings an admin posts for owners without an account carry their own contact details.
            $table->string('contact_name')->nullable()->after('owner_id');
            $table->string('contact_messenger', 100)->nullable()->after('contact_name');
            $table->string('contact_phone', 30)->nullable()->after('contact_messenger');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'contact_messenger', 'contact_phone']);
        });
    }
};
