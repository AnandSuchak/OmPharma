<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The name of the unique index.
     * We define it here to use in both up() and down() methods.
     */
    private $indexName = 'medicines_name_company_name_unit_pack_unique';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            // Step 1: Drop the index that includes the 'unit' column.
            // The index name is taken from the SHOW INDEX command you ran.
            $table->dropIndex($this->indexName);

            // Step 2: Now that the index is gone, drop the 'unit' column.
            $table->dropColumn('unit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            // Step 1: Add the 'unit' column back if we roll back.
            $table->string('unit')->nullable()->after('description');

            // Step 2: Re-create the unique index exactly as it was before.
            $table->unique(['name', 'company_name', 'unit', 'pack'], $this->indexName);
        });
    }
};