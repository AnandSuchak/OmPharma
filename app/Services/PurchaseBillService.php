<?php

// File: app/Services/PurchaseBillService.php

namespace App\Services;

use App\Interfaces\PurchaseBillRepositoryInterface;
use App\Models\Inventory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * Handles the business logic for the PurchaseBill module.
 */
class PurchaseBillService
{
    protected PurchaseBillRepositoryInterface $purchaseBillRepository;

    public function __construct(PurchaseBillRepositoryInterface $purchaseBillRepository)
    {
        $this->purchaseBillRepository = $purchaseBillRepository;
    }

    public function getAllPurchaseBills(array $filters)
    {
        return $this->purchaseBillRepository->getAllPaginated($filters);
    }

    public function getPurchaseBillById(int $id)
    {
        return $this->purchaseBillRepository->findById($id);
    }

    /**
     * Create a new purchase bill and update inventory.
     */
    public function createPurchaseBill(array $data): \App\Models\PurchaseBill
    {
        $items = $data['purchase_items'];

        DB::beginTransaction();
        try {
            $billData = $this->calculateAndPrepareBillData($data, $items);
            $purchaseBill = $this->purchaseBillRepository->createBill($billData);

            foreach ($items as $itemData) {
                $this->purchaseBillRepository->createItem($purchaseBill->id, $itemData);
                $this->adjustInventory(
                    $itemData['medicine_id'],
                    $itemData['batch_number'],
                    $itemData['expiry_date'],
                    (float)($itemData['quantity'] ?? 0.0),
                    (float)($itemData['free_quantity'] ?? 0.0)
                );
            }

            DB::commit();
            return $purchaseBill;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e; // Re-throw the exception to be caught by the controller
        }
    }

    /**
     * Update an existing purchase bill and adjust inventory accordingly.
     */
    public function updatePurchaseBill(int $billId, array $data): bool
    {
        $existingItems = $data['existing_items'] ?? [];
        $newItems = $data['new_purchase_items'] ?? [];

        DB::beginTransaction();
        try {
            // 1. Rollback inventory for all original items
            $originalItems = $this->purchaseBillRepository->getOriginalItems($billId);
            foreach ($originalItems as $item) {
                $this->adjustInventory($item->medicine_id, $item->batch_number, $item->expiry_date, -(float)$item->quantity, -(float)$item->free_quantity);
            }

            // 2. Delete items that were removed from the form
            $existingItemIds = Arr::pluck($existingItems, 'id');
            foreach ($originalItems as $item) {
                if (!in_array($item->id, $existingItemIds)) {
                    $this->purchaseBillRepository->deleteItem($item->id);
                }
            }

            // 3. Update existing items and re-apply their inventory
            foreach ($existingItems as $itemData) {
                $this->purchaseBillRepository->updateItem($itemData['id'], Arr::except($itemData, 'id'));
                $this->adjustInventory($itemData['medicine_id'], $itemData['batch_number'], $itemData['expiry_date'], (float)$itemData['quantity'], (float)$itemData['free_quantity']);
            }

            // 4. Create new items and apply their inventory
            foreach ($newItems as $itemData) {
                $newItem = $this->purchaseBillRepository->createItem($billId, $itemData);
                $this->adjustInventory($newItem->medicine_id, $newItem->batch_number, $newItem->expiry_date, (float)$newItem->quantity, (float)$newItem->free_quantity);
            }

            // 5. Recalculate totals and update the main bill
            $allItemsData = array_merge(array_values($existingItems), $newItems);
            $billData = $this->calculateAndPrepareBillData($data, $allItemsData);
            $this->purchaseBillRepository->updateBill($billId, $billData);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a purchase bill and reverse its inventory adjustments.
     */
    public function deletePurchaseBill(int $billId): bool
    {
        DB::beginTransaction();
        try {
            $purchaseBill = $this->purchaseBillRepository->findById($billId);
            if (!$purchaseBill) {
                throw new Exception("Purchase Bill not found.");
            }

            foreach ($purchaseBill->purchaseBillItems as $item) {
                $this->adjustInventory($item->medicine_id, $item->batch_number, $item->expiry_date, -(float)$item->quantity, -(float)$item->free_quantity);
            }

            $this->purchaseBillRepository->deleteBill($billId);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calculates totals and prepares the main bill data for storage.
     */
    private function calculateAndPrepareBillData(array $requestData, array $items): array
    {
        $subtotal = 0.0;
        $totalGst = 0.0;

        foreach ($items as $itemData) {
            $quantity = (float)($itemData['quantity'] ?? 0.0);
            $price = (float)($itemData['purchase_price'] ?? 0.0);
            $discount = (float)($itemData['our_discount_percentage'] ?? 0.0);
            $gstRate = (float)($itemData['gst_rate'] ?? 0.0);

            $itemBase = $quantity * $price;
            $itemAfterDiscount = $itemBase * (1 - ($discount / 100));
            $itemGst = $itemAfterDiscount * ($gstRate / 100);

            $subtotal += $itemAfterDiscount;
            $totalGst += $itemGst;
        }

        $extraDiscount = (float)($requestData['extra_discount_amount'] ?? 0.0);
        $subtotalAfterExtraDiscount = max($subtotal - $extraDiscount, 0.0);

        $calculatedGrandTotal = $subtotalAfterExtraDiscount + $totalGst;
        $roundedGrandTotal = round($calculatedGrandTotal);
        $roundingOffAmount = $roundedGrandTotal - $calculatedGrandTotal;

        $billData = Arr::except($requestData, ['purchase_items', 'existing_items', 'new_purchase_items', '_token', '_method']);
        $billData['extra_discount_amount'] = $extraDiscount;
        $billData['total_gst_amount'] = round($totalGst, 2);
        $billData['total_amount'] = $roundedGrandTotal;
        $billData['rounding_off_amount'] = round($roundingOffAmount, 2);

        return $billData;
    }

    /**
     * Adjusts the inventory for a given medicine and batch.
     */
    private function adjustInventory(?int $medicineId, ?string $batchNumber, ?string $expiryDate, float $paidQuantity, float $freeQuantity = 0.0): void
    {
        if (!$medicineId) return;

        $totalQuantityChange = $paidQuantity + $freeQuantity;
        if ($totalQuantityChange == 0.0) return;

        $inventory = Inventory::firstOrNew([
            'medicine_id'  => $medicineId,
            'batch_number' => $batchNumber,
        ]);

        if ($inventory->exists && $expiryDate !== null) {
            $existingExpiry = $inventory->expiry_date ? $inventory->expiry_date->format('Y-m-d') : null;
            $newExpiry = \Carbon\Carbon::parse($expiryDate)->format('Y-m-d');
            if ($existingExpiry && $existingExpiry !== $newExpiry) {
                throw ValidationException::withMessages([
                    'batch_number' => "Batch '{$batchNumber}' already exists with a different expiry date."
                ]);
            }
        }
        
        if ($expiryDate !== null) {
            $inventory->expiry_date = $expiryDate;
        }

        $inventory->quantity = (float)($inventory->quantity ?? 0.0) + $totalQuantityChange;
        $inventory->save();
    }
}
