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
        Schema::create('purchase_bill_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_bill_id')->constrained()->onDelete('cascade'); // Foreign key to purchase_bills table
            $table->foreignId('medicine_id')->constrained(); // Foreign key to medicines table
            $table->string('batch_number');
            $table->date('expiry_date');
            $table->integer('quantity')->default(0);
            $table->decimal('purchase_price', 8, 2)->default(0);
            $table->decimal('ptr', 8, 2)->nullable();
            $table->decimal('gst_rate', 8, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['purchase_bill_id', 'medicine_id', 'batch_number', 'expiry_date'], 'pb_item_unique'); // Ensuring unique items in a bill
            $table->index(['medicine_id', 'expiry_date']); // For efficient querying of medicines by expiry
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_bill_items');
    }
};
