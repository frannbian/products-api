<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Version used for optimistic locking: every update increments it and only
 * succeeds if the row still has the version that was read.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (['products', 'currencies'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->unsignedInteger('version')->default(1);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['products', 'currencies'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('version');
            });
        }
    }
};
