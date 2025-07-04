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
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('hsn_code')->nullable();
            $table->text('description')->nullable();
            $table->string('unit');
            $table->decimal('gst_rate', 8, 2)->nullable();
            $table->string('pack')->nullable();
            $table->string('company_name')->nullable();
            $table->timestamps();

            $table->unique(['name', 'company_name', 'unit', 'pack']); // Example: Ensuring unique medicine records
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
