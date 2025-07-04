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
            Schema::create('sale_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained()->onDelete('cascade'); // Foreign key to the sales table
    $table->foreignId('medicine_id')->constrained()->onDelete('restrict'); // Foreign key to the medicines table
    $table->string('batch_number');
    $table->date('expiry_date');
    $table->integer('quantity');
    $table->decimal('sale_price', 8, 2);
    $table->decimal('ptr', 8, 2)->nullable(); // You might want to track PTR at the time of sale
    $table->decimal('gst_rate', 5, 2)->nullable();
    $table->decimal('discount_percentage', 5, 2)->default(0);
    $table->timestamps();
    $table->unique(['sale_id', 'medicine_id', 'batch_number', 'expiry_date']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
