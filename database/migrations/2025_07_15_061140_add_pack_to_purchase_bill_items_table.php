<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_bill_items', function (Blueprint $table) {
            // Assuming 'pack' can be a string like '10 Strips' or 'Bottle of 100ml'
            $table->string('pack')->nullable()->after('medicine_id'); // Adjust 'after' as needed
            // If 'pack' is just a number (e.g., 10, 25), use $table->integer('pack')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('purchase_bill_items', function (Blueprint $table) {
            $table->dropColumn('pack');
        });
    }
};