<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConsolidateInventoryBatches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:consolidate-batches {--dry-run : Test the command without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consolidates duplicate inventory entries for the same medicine_id and batch_number into a single record, summing quantities and picking a consistent expiry date.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting inventory batch consolidation...");

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn("--- DRY RUN MODE --- No database changes will be made.");
        }

        DB::beginTransaction();
        try {
            // Find medicine_id and batch_number combinations that have duplicate entries
            $duplicateBatches = Inventory::select('medicine_id', 'batch_number')
                ->groupBy('medicine_id', 'batch_number')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            if ($duplicateBatches->isEmpty()) {
                $this->info("No duplicate inventory batches found. Exiting.");
                DB::rollBack(); // Nothing to commit or rollback here, just exit
                return;
            }

            $this->info("Found " . $duplicateBatches->count() . " unique medicine_id/batch_number combinations with duplicates.");

            foreach ($duplicateBatches as $duplicateBatch) {
                $medicineId = $duplicateBatch->medicine_id;
                $batchNumber = $duplicateBatch->batch_number;

                $this->line("Processing Batch: Medicine ID {$medicineId}, Batch Number '{$batchNumber}'");

                // Get all inventory entries for this specific medicine_id and batch_number
                $entries = Inventory::where('medicine_id', $medicineId)
                                    ->where('batch_number', $batchNumber)
                                    ->orderBy('expiry_date', 'asc') // Order by expiry to easily pick earliest
                                    ->get();

                // Calculate total quantity and determine the canonical expiry date
                $totalQuantity = 0;
                $canonicalExpiryDate = null; // Will store the chosen expiry date

                // You need a rule for selecting the expiry date. Common options:
                // 1. Earliest expiry (safest for perishable goods)
                // 2. Latest expiry
                // 3. User confirmation (more complex for a script)
                // For this script, we'll pick the earliest expiry found.

                foreach ($entries as $entry) {
                    $totalQuantity += $entry->quantity;

                    // If canonicalExpiryDate is null or this entry's expiry is earlier
                    if ($entry->expiry_date) {
                        if ($canonicalExpiryDate === null || $entry->expiry_date->lt($canonicalExpiryDate)) {
                            $canonicalExpiryDate = $entry->expiry_date;
                        }
                    }
                }

                // If no expiry date was ever set (all were null), keep it null
                if ($canonicalExpiryDate === null) {
                    $this->warn("  Warning: No valid expiry date found among duplicates for this batch. Keeping expiry as NULL.");
                } else {
                    $this->line("  Consolidated Quantity: {$totalQuantity}");
                    $this->line("  Chosen Expiry Date: " . ($canonicalExpiryDate ? $canonicalExpiryDate->format('Y-m-d') : 'NULL'));
                }

                // Get the "primary" record to update (e.g., the first one by ID or the one with the chosen expiry)
                // For simplicity, let's just pick the first one from the initial fetch to update
                $primaryEntry = $entries->first();

                if ($isDryRun) {
                    $this->info("  DRY RUN: Would update Inventory ID {$primaryEntry->id} (Medicine ID: {$medicineId}, Batch: '{$batchNumber}')");
                    $this->info("    - New Quantity: {$totalQuantity}");
                    $this->info("    - New Expiry Date: " . ($canonicalExpiryDate ? $canonicalExpiryDate->format('Y-m-d') : 'NULL'));
                    $this->info("  DRY RUN: Would delete " . ($entries->count() - 1) . " duplicate records.");
                } else {
                    // Update the primary record
                    $primaryEntry->quantity = $totalQuantity;
                    $primaryEntry->expiry_date = $canonicalExpiryDate; // Set the chosen expiry
                    $primaryEntry->save();
                    $this->info("  Updated Inventory ID {$primaryEntry->id}.");

                    // Delete the other duplicate records
                    $entriesToKeepIds = [$primaryEntry->id];
                    Inventory::where('medicine_id', $medicineId)
                             ->where('batch_number', $batchNumber)
                             ->whereNotIn('id', $entriesToKeepIds)
                             ->delete(); // Using delete() not forceDelete() if soft deletes are intended
                    $this->info("  Deleted " . ($entries->count() - 1) . " duplicate records for this batch.");
                }
            }

            if (!$isDryRun) {
                DB::commit();
                $this->info("Inventory batch consolidation completed successfully.");
            } else {
                DB::rollBack(); // Ensure no changes are committed in dry run
                $this->info("Dry run completed. No changes were committed to the database.");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("An error occurred during consolidation: " . $e->getMessage());
            $this->error($e->getTraceAsString());
        }
    }
}