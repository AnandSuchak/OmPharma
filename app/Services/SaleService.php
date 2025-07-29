<?php

namespace App\Services;

use App\Interfaces\SaleRepositoryInterface;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\SaleItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * Handles the business logic for the Sale module.
 */
class SaleService
{
    protected SaleRepositoryInterface $saleRepository;

    public function __construct(SaleRepositoryInterface $saleRepository)
    {
        $this->saleRepository = $saleRepository;
    }

    /**
     * Get all sales with pagination.
     */
    public function getAllSales()
    {
        return $this->saleRepository->getAllPaginated();
    }

    /**
     * Find a sale by ID.
     */
    public function getSaleById(int $id)
    {
        return $this->saleRepository->findById($id);
    }

    /**
     * Create a new sale, including items and inventory adjustments.
     *
     * @throws ValidationException
     */
    public function createSale(array $data): \App\Models\Sale
    {
        DB::beginTransaction();
        try {
            // Validate inventory for all items before creating sale
            foreach ($data['new_sale_items'] as $itemData) {
                $totalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0);

                $inventory = Inventory::firstOrNew([
                    'medicine_id' => $itemData['medicine_id'],
                    'batch_number' => $itemData['batch_number'],
                ]);

                if ((float)($inventory->quantity ?? 0.0) < $totalQty) {
                    throw ValidationException::withMessages([
                        'quantity' => "Insufficient stock for batch '{$itemData['batch_number']}'."
                    ]);
                }
            }

            // Generate bill number and totals
            $billNumber = $this->generateBillNumber();
            $totals = $this->calculateTotals($data['new_sale_items']);

            // Create sale after validation passes
            $sale = $this->saleRepository->createSale([
                'customer_id' => $data['customer_id'],
                'customer_name' => optional(Customer::find($data['customer_id']))->name,
                'sale_date' => $data['sale_date'],
                'bill_number' => $billNumber,
                'notes' => $data['notes'],
                'total_amount' => $totals['total'],
                'total_gst_amount' => $totals['gst'],
            ]);

            // Adjust inventory and create sale items
            foreach ($data['new_sale_items'] as $itemData) {
                $totalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0);
                $this->adjustInventory($itemData, -$totalQty);
                $this->saleRepository->createItem($sale->id, $itemData);
            }

            DB::commit();
            return $sale;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update an existing sale, handling items and inventory adjustments.
     */
    public function updateSale(int $saleId, array $data): bool
    {
        DB::beginTransaction();
        try {
            $sale = $this->saleRepository->findById($saleId);
            if (!$sale) {
                throw new Exception("Sale not found.");
            }

            // Update main sale data
            $this->saleRepository->updateSale($saleId, Arr::only($data, ['customer_id', 'sale_date', 'notes']) + [
                'customer_name' => Customer::find($data['customer_id'])?->name ?? 'Unknown',
            ]);

            $originalItems = $sale->saleItems->keyBy('id');

            // Handle deleted items
            $deletedItemIds = array_filter(explode(',', $data['deleted_items'] ?? ''));
            foreach ($deletedItemIds as $itemId) {
                $item = $originalItems->get($itemId);
                if ($item) {
                    $this->adjustInventory($item, (float)$item->quantity + (float)$item->free_quantity);
                    $this->saleRepository->deleteItem($item->id);
                }
            }

            // Handle existing items
            foreach ($data['existing_sale_items'] ?? [] as $itemData) {
                $item = $originalItems->get($itemData['id']);
                if ($item) {
                    $originalTotalQty = (float)$item->quantity + (float)$item->free_quantity;
                    $newTotalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0);
                    $quantityDiff = $newTotalQty - $originalTotalQty;

                    if ($quantityDiff !== 0.0) {
                        $this->adjustInventory($item, -$quantityDiff);
                    }
                    $this->saleRepository->updateItem($item->id, $itemData);
                }
            }

            // Handle new items
            foreach ($data['new_sale_items'] ?? [] as $itemData) {
                $totalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0);
                $this->adjustInventory($itemData, -$totalQty);
                $this->saleRepository->createItem($saleId, $itemData);
            }

            $this->updateSaleTotals($saleId);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Delete a sale and restore inventory.
     */
    public function deleteSale(int $saleId): bool
    {
        DB::beginTransaction();
        try {
            $sale = $this->saleRepository->findById($saleId);
            if (!$sale) {
                throw new Exception("Sale not found.");
            }

            foreach ($sale->saleItems as $item) {
                $this->adjustInventory($item, (float)$item->quantity + (float)$item->free_quantity);
            }

            $this->saleRepository->deleteSale($saleId);
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate a unique bill number.
     */
    private function generateBillNumber(): string
    {
        do {
            $latestId = $this->saleRepository->getLatestSaleId() ?? 0;
            $billNumber = 'CASH-' . str_pad($latestId + 1, 5, '0', STR_PAD_LEFT);
        } while ($this->saleRepository->billNumberExists($billNumber));

        return $billNumber;
    }

    /**
     * Adjust inventory for a given item.
     */
    private function adjustInventory(array|SaleItem $item, float $adjustQty): void
    {
        if ($adjustQty === 0.0) return;

        $medicineId = is_array($item) ? $item['medicine_id'] : $item->medicine_id;
        $batchNumber = is_array($item) ? $item['batch_number'] : $item->batch_number;

        $inventory = Inventory::firstOrNew([
            'medicine_id' => $medicineId,
            'batch_number' => $batchNumber,
        ]);

        $inventory->quantity = (float)($inventory->quantity ?? 0.0) + $adjustQty;

        if ($inventory->quantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => "Insufficient stock for batch '{$batchNumber}'."
            ]);
        }

        $inventory->save();
    }

    /**
     * Recalculate and update totals for a sale.
     */
    private function updateSaleTotals(int $saleId): void
    {
        $sale = $this->saleRepository->findById($saleId);
        $totals = $this->calculateTotals($sale->saleItems);
        $this->saleRepository->updateSale($saleId, [
            'total_amount' => $totals['total'],
            'total_gst_amount' => $totals['gst'],
        ]);
    }

    /**
     * Calculate totals for a set of items.
     */
    private function calculateTotals(iterable $items): array
    {
        $subtotal = 0.0;
        $gst = 0.0;

        foreach ($items as $item) {
            $quantity = (float)(is_array($item) ? $item['quantity'] : $item->quantity);
            $salePrice = (float)(is_array($item) ? $item['sale_price'] : $item->sale_price);
            $discount = (float)(is_array($item) ? $item['discount_percentage'] : $item->discount_percentage);
            $gstRate = (float)(is_array($item) ? $item['gst_rate'] : $item->gst_rate);

            $lineTotal = $quantity * $salePrice;
            $afterDiscount = $lineTotal * (1 - ($discount / 100));
            $gstAmount = $afterDiscount * ($gstRate / 100);

            $subtotal += $afterDiscount;
            $gst += $gstAmount;
        }

        return [
            'total' => round($subtotal + $gst, 2),
            'gst' => round($gst, 2),
        ];
    }
}
