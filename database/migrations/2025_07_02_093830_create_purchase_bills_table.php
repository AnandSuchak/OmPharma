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
            Schema::create('purchase_bills', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_id')->constrained()->onDelete('cascade'); // Foreign key to suppliers table
                $table->date('bill_date');
                $table->string('bill_number');
                $table->string('status')->default('Pending'); // You can define different statuses
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->decimal('total_gst_amount', 10, 2)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['supplier_id', 'bill_number']); // Ensuring unique bill number for each supplier
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_bills');
    }
};
