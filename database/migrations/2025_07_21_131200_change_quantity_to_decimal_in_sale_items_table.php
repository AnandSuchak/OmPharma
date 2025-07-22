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
        Schema::table('sale_items', function (Blueprint $table) {
            // Change 'quantity' column to decimal(10, 2)
            $table->decimal('quantity', 10, 2)->change();
            
            // Change 'free_quantity' column to decimal(10, 2)
            $table->decimal('free_quantity', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            // Revert 'quantity' column back to integer
            $table->integer('quantity')->change();

            // Revert 'free_quantity' column back to integer
            $table->integer('free_quantity')->change();
        });
    }
};