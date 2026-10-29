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
        Schema::create('product_imports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20);
            // Claim check: the rows live here, the queue message only has the id.
            $table->jsonb('rows');
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('succeeded')->default(0);
            $table->jsonb('errors');
            $table->timestampTz('created_at', 6);
            $table->timestampTz('finished_at', 6)->nullable();

            $table->index('owner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_imports');
    }
};
