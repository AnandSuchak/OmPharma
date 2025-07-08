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
        // Change the expiry_date column to allow null values
        Schema::table('sale_items', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the column to not allow null values
        // Note: This will fail if there are rows with null expiry dates.
        Schema::table('sale_items', function (Blueprint $table) {
            $table->date('expiry_date')->nullable(false)->change();
        });
    }
};
