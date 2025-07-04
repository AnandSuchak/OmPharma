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
    Schema::table('purchase_bill_items', function (Blueprint $table) {
        $table->renameColumn('sale_price', 'sale_price');
    });
}

public function down()
{
    Schema::table('purchase_bill_items', function (Blueprint $table) {
        $table->renameColumn('sale_price', 'sale_price');
    });
}

};
