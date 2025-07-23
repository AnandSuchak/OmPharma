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
        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
            $table->foreignId('medicine_id')->constrained('medicines')->onDelete('cascade');
            $table->string('batch_number')->nullable();

            // OLD LINE (causing error):
            // $table->nullableMorphs('transaction_reference');

            // NEW, CORRECTED LINE: Manually specify a shorter index name for the polymorphic columns
            $table->unsignedBigInteger('transaction_reference_id')->nullable();
            $table->string('transaction_reference_type')->nullable();
            // Add a custom, shorter index name for these two columns
            $table->index(['transaction_reference_type', 'transaction_reference_id'], 'inv_log_trans_ref_idx'); // Max 64 chars. 'inv_log_trans_ref_idx' is 21 chars.

            $table->decimal('quantity_change', 10, 2);
            $table->decimal('new_quantity_on_hand', 10, 2);

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};