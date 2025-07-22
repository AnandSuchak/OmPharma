<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Inventory;
use App\Models\Medicine; // Although not directly used for update, it's often imported

class UpdateOldBatchNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-old-batch-numbers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates old inventory items with empty or placeholder batch numbers, OR old SYS-ID-ID format, to the new SYS-medicine_id format.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting the batch number update process for Inventories...");

        // Your list of medicine IDs to process
        $medicineIdsToUpdate = [
            2, 44, 45, 46, 47, 50, 53, 55, 57, 58, 59, 64, 66, 77, 78, 79,
            80, 81, 82, 83, 116, 120, 121, 183, 184, 185, 189, 191, 192, 194,
            195, 197, 198, 233, 234, 235, 236, 237, 238, 251, 252, 253, 254,
            255, 256, 257, 258, 260, 261, 262, 263, 264, 265, 266, 267, 268,
            298, 299, 300, 301, 302, 303, 304, 305, 306, 307, 308, 309, 310,
            311, 312, 313, 314, 315, 316, 317, 318, 319, 320, 321, 322, 323,
            324, 325, 326, 327, 328, 329, 330, 331, 332, 333, 334, 335, 336,
            337, 338, 339, 340, 341, 342, 343, 344, 345, 346, 347, 348, 349
        ];

        // Find inventory items for those medicines where the batch number is:
        // 1. NULL, empty, or 'NA'
        // OR
        // 2. Starts with 'SYS-' AND contains an additional hyphen (indicating the old SYS-ID-ID format)
        $inventoriesToUpdate = Inventory::whereIn('medicine_id', $medicineIdsToUpdate)
            ->where(function ($query) {
                $query->whereNull('batch_number')
                      ->orWhere('batch_number', '')
                      ->orWhere('batch_number', 'NA')
                      ->orWhere('batch_number', 'LIKE', 'SYS-%-%'); // ADDED condition
            })
            ->get();
            
        if ($inventoriesToUpdate->isEmpty()) {
            $this->warn("No inventory items were found that need updating.");
            return 0;
        }

        $this->info("Found " . $inventoriesToUpdate->count() . " inventory items to update.");

        $progressBar = $this->output->createProgressBar($inventoriesToUpdate->count());
        $progressBar->start();

        foreach ($inventoriesToUpdate as $inventory) {
            // Generate the new batch number as 'SYS-' followed by medicine_id
            $newBatchNumber = 'SYS-' . $inventory->medicine_id;

            // Save the update
            $inventory->batch_number = $newBatchNumber;
            $inventory->save();

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Success! All " . $inventoriesToUpdate->count() . " inventory items have been updated to the new SYS-medicine_id format.");

        return 0;
    }
}