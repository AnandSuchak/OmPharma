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
        Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('phone_number');
                $table->string('email')->nullable();
                $table->string('gst');
                $table->text('address')->nullable();
                $table->string('dln');
                $table->timestamps();

                $table->unique(['name', 'dln']); // Example: Ensuring unique supplier records based on name and DLN
                $table->unique('gst'); // Ensuring unique GST number
                $table->unique('phone_number'); // Ensuring unique phone number
                $table->unique('dln'); // Ensuring unique DLN
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
