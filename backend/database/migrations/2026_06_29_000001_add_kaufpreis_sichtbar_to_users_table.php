<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('kaufpreis_sichtbar')->default(false);
        });

        // Existing admin users get price visibility by default
        DB::statement("UPDATE users SET kaufpreis_sichtbar = 1 WHERE role = 'admin'");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('kaufpreis_sichtbar');
        });
    }
};
