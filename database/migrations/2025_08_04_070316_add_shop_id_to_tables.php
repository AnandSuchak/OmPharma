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
        // List of tables that belong to a shop
        $tables = [
            'users',
            'customers',
            'suppliers',
            'medicines',
            'purchase_bills',
            'purchase_bill_items',
            'sales',
            'sale_items',
            'inventories',
            'inventory_logs',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // Add the shop_id column. It's nullable for the 'users' table
                // to allow for platform-level users like yourself.
                $table->foreignId('shop_id')->nullable()->constrained('shops')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'users',
            'customers',
            'suppliers',
            'medicines',
            'purchase_bills',
            'purchase_bill_items',
            'sales',
            'sale_items',
            'inventories',
            'inventory_logs',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['shop_id']);
                $table->dropColumn('shop_id');
            });
        }
    }
};
