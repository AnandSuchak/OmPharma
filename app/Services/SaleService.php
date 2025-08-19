<?php

namespace App\Services;

use App\Interfaces\InventoryRepositoryInterface;
use App\Interfaces\SaleRepositoryInterface;
use App\Models\Customer;
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
    protected InventoryRepositoryInterface $inventoryRepository;

    public function __construct(SaleRepositoryInterface $saleRepository, InventoryRepositoryInterface $inventoryRepository)
    {
        $this->saleRepository = $saleRepository;
        $this->inventoryRepository = $inventoryRepository;
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
            // This loop validates stock and prepares item data before creating the sale
            foreach ($data['new_sale_items'] as &$itemData) {
                $totalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0);
                $batchNumber = $itemData['batch_number'] ?? null;

                if (!$batchNumber || $batchNumber === 'N/A') {
                    $oldestInventory = $this->inventoryRepository->findOldestAvailableBatch($itemData['medicine_id'], $totalQty);
                    if (!$oldestInventory) {
                        throw ValidationException::withMessages(['quantity' => "Insufficient stock for medicine ID {$itemData['medicine_id']}."]);
                    }
                    $batchNumber = $oldestInventory->batch_number;
                    $itemData['batch_number'] = $batchNumber;
                    if (empty($itemData['sale_price'])) $itemData['sale_price'] = $oldestInventory->sale_price;
                    if (empty($itemData['ptr'])) $itemData['ptr'] = $oldestInventory->ptr;
                    if (empty($itemData['gst_rate'])) $itemData['gst_rate'] = $oldestInventory->gst;
                }

                $inventory = $this->inventoryRepository->findByMedicineAndBatch($itemData['medicine_id'], $batchNumber);
                if (!$inventory || (float)($inventory->quantity) < $totalQty) {
                    throw ValidationException::withMessages(['quantity' => "Insufficient stock for batch '{$batchNumber}'."]);
                }
                 // Ensure extra discount fields are present
                $itemData['is_extra_discount_applied'] = !empty($itemData['is_extra_discount_applied']);
                $itemData['applied_extra_discount_percentage'] = $itemData['applied_extra_discount_percentage'] ?? 0;
            }
            unset($itemData); // Unset reference

            $billNumber = $this->generateBillNumber();
            $totals = $this->calculateTotals($data['new_sale_items']);

            $sale = $this->saleRepository->createSale([
                'customer_id' => $data['customer_id'],
                'customer_name' => optional(Customer::find($data['customer_id']))->name,
                'sale_date' => $data['sale_date'],
                'bill_number' => $billNumber,
                'notes' => $data['notes'] ?? null,
                'total_amount' => $totals['total'],
                'total_gst_amount' => $totals['gst'],
                'discount_percentage' => $data['discount_percentage'] ?? 0,
            ]);

            // This loop creates sale items and adjusts inventory
            foreach ($data['new_sale_items'] as $itemData) {
                $totalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0);
                $this->inventoryRepository->adjustStock($itemData['medicine_id'], $itemData['batch_number'], -$totalQty);
                $this->saleRepository->createItem($sale->id, $itemData);
            }

            DB::commit();
            return $sale;
        } catch (\Exception $e) {
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

            $this->saleRepository->updateSale($saleId, Arr::only($data, ['customer_id', 'sale_date', 'notes']) + [
                'customer_name' => Customer::find($data['customer_id'])?->name ?? 'Unknown',
            ]);

            $originalItems = $sale->saleItems->keyBy('id');
            $deletedItemIds = array_filter(explode(',', $data['deleted_items'] ?? ''));

            foreach ($deletedItemIds as $itemId) {
                $item = $originalItems->get($itemId);
                if ($item) {
                    $totalQty = (float)$item->quantity + (float)$item->free_quantity;
                    $this->inventoryRepository->adjustStock($item->medicine_id, $item->batch_number, $totalQty);
                    $this->saleRepository->deleteItem($item->id);
                }
            }

            foreach ($data['existing_sale_items'] ?? [] as $itemData) {
                $item = $originalItems->get($itemData['id']);
                if ($item) {
                    $originalTotalQty = (float)$item->quantity + (float)$item->free_quantity;
                    $newTotalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0);
                    $quantityDiff = $newTotalQty - $originalTotalQty;

                    if ($quantityDiff !== 0.0) {
                        $this->inventoryRepository->adjustStock($item->medicine_id, $item->batch_number, -$quantityDiff);
                    }
                    $this->saleRepository->updateItem($item->id, $itemData);
                }
            }

            foreach ($data['new_sale_items'] ?? [] as $itemData) {
                $totalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0);
                $this->inventoryRepository->adjustStock($itemData['medicine_id'], $itemData['batch_number'], -$totalQty);
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
                $totalQty = (float)$item->quantity + (float)$item->free_quantity;
                $this->inventoryRepository->adjustStock($item->medicine_id, $item->batch_number, $totalQty);
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
            $discount = (float)(is_array($item) ? ($item['discount_percentage'] ?? 0) : $item->discount_percentage);
            $gstRate = (float)(is_array($item) ? $item['gst_rate'] : $item->gst_rate);
            // New extra discount logic
            $isExtraApplied = is_array($item) ? ($item['is_extra_discount_applied'] ?? false) : $item->is_extra_discount_applied ?? false;
            $extraDiscount = is_array($item) ? ($item['applied_extra_discount_percentage'] ?? 0) : $item->applied_extra_discount_percentage ?? 0;
            if ($isExtraApplied && $extraDiscount > 0) {
                $discount += $extraDiscount;
            }

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
