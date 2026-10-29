<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The audit trail is history: it must keep who made a change even after the
 * user is deleted, and the asynchronous consumer must not fail when the user
 * was deleted between publishing the event and recording it. So the actor
 * is kept as a plain, indexed id instead of a foreign key.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropForeign(['actor_id']);
            $table->index('actor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_log', function (Blueprint $table) {
            $table->dropIndex(['actor_id']);
            $table->foreign('actor_id')->references('id')->on('users')->nullOnDelete();
        });
    }
};
