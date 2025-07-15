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
        Schema::table('purchase_bill_items', function (Blueprint $table) {
            // Make the batch_number column nullable
            $table->string('batch_number')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_bill_items', function (Blueprint $table) {
            // Revert the change if needed, though this may fail if nulls exist
            $table->string('batch_number')->nullable(false)->change();
        });
    }
};