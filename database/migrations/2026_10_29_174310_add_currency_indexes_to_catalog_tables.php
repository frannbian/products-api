<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PostgreSQL does not index foreign key columns on its own. These columns are
 * used to find out whether a currency is in use before deleting it.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('currency_id');
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->index('currency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['currency_id']);
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropIndex(['currency_id']);
        });
    }
};
