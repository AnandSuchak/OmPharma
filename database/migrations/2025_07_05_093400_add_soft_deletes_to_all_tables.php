<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('medicines', function (Blueprint $table) {
        $table->softDeletes();
    });

    Schema::table('suppliers', function (Blueprint $table) {
        $table->softDeletes();
    });

    Schema::table('customers', function (Blueprint $table) {
        $table->softDeletes();
    });

    Schema::table('purchase_bills', function (Blueprint $table) {
        $table->softDeletes();
    });

    Schema::table('purchase_bill_items', function (Blueprint $table) {
        $table->softDeletes();
    });

    Schema::table('sales', function (Blueprint $table) {
        $table->softDeletes();
    });

    Schema::table('sale_items', function (Blueprint $table) {
        $table->softDeletes();
    });
    
    Schema::table('inventories', function (Blueprint $table) {
        $table->softDeletes();
    });
}

public function down()
{
    Schema::table('medicines', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });

    Schema::table('suppliers', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });

    Schema::table('customers', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });

    Schema::table('purchase_bills', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });

    Schema::table('purchase_bill_items', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });

    Schema::table('sales', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });

    Schema::table('sale_items', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });

    Schema::table('inventories', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });
}


};
