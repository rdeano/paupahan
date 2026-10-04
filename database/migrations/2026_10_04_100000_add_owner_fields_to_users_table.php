<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('owner')->after('password')->index();
            $table->string('status')->default('active')->after('role')->index();
            $table->string('phone', 30)->nullable()->after('status');
            $table->boolean('show_phone')->default(false)->after('phone');
            $table->string('messenger_username', 100)->nullable()->after('show_phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'status', 'phone', 'show_phone', 'messenger_username']);
        });
    }
};
