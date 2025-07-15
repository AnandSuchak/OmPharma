<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_bill_items', function (Blueprint $table) {
            // Add our new supplier discount field
            $table->decimal('our_discount_percentage', 5, 2)->default(0)->after('discount_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_bill_items', function (Blueprint $table) {
            $table->dropColumn('our_discount_percentage');
        });
    }
};