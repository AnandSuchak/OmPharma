<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PurchaseBillItem; // Import PurchaseBillItem

class UpdateBatchNumbersersforpurchesbill extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-purchase-bill-items-batches'; // Changed for clarity

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates old purchase bill items with empty or placeholder batch numbers to the new SYS-medicine_id format.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting the batch number update process for PurchaseBillItems...");

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

        // Find purchase bill items for those medicines where the batch number is NULL, empty, or 'NA'.
        $purchaseBillItemsToUpdate = PurchaseBillItem::whereIn('medicine_id', $medicineIdsToUpdate)
            ->where(function ($query) {
                $query->whereNull('batch_number')
                      ->orWhere('batch_number', '')
                      ->orWhere('batch_number', 'NA');
            })
            ->get();
            
        if ($purchaseBillItemsToUpdate->isEmpty()) {
            $this->warn("No PurchaseBillItems were found that need updating.");
            return 0;
        }

        $this->info("Found " . $purchaseBillItemsToUpdate->count() . " PurchaseBillItems to update.");

        $progressBar = $this->output->createProgressBar($purchaseBillItemsToUpdate->count());
        $progressBar->start();

        foreach ($purchaseBillItemsToUpdate as $purchaseBillItem) {
            // Generate the new batch number as 'SYS-' followed by medicine_id
            // This must match the format used for inventories for the join to work.
            $newBatchNumber = 'SYS-' . $purchaseBillItem->medicine_id;

            // Save the update
            $purchaseBillItem->batch_number = $newBatchNumber;
            $purchaseBillItem->save();

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Success! All " . $purchaseBillItemsToUpdate->count() . " PurchaseBillItems have been updated to the new SYS-medicine_id format.");

        return 0;
    }
}