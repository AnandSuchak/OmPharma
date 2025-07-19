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
        Schema::table('sale_items', function (Blueprint $table) {
            $table->boolean('is_extra_discount_applied')->default(false)->after('discount_percentage');
            $table->decimal('applied_extra_discount_percentage', 5, 2)->default(0.00)->after('is_extra_discount_applied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('applied_extra_discount_percentage');
            $table->dropColumn('is_extra_discount_applied');
        });
    }
};