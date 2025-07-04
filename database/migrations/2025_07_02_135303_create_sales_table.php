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
       Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->string('customer_name');
    $table->date('sale_date');
    $table->string('bill_number')->unique(); // You might want sales bill numbers to be unique
    $table->enum('status', ['Pending', 'Completed', 'Cancelled'])->default('Completed'); // Example statuses
    $table->decimal('total_amount', 8, 2)->nullable();
    $table->decimal('total_gst_amount', 8, 2)->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
