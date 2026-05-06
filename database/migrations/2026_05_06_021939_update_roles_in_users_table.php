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
        Schema::table('users', function (Blueprint $table) {
            // Using DB::statement for compatibility if needed, or change() if doctrine/dbal is present
            // However, in Laravel 10+, native change() is better if supported.
            // For enum, we often need to redefine it.
            $table->enum('role', ['super_admin', 'local_admin', 'staff'])->default('local_admin')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'local_admin'])->default('local_admin')->change();
        });
    }
};
