<?php

namespace App\Repositories;

use App\Interfaces\InventoryRepositoryInterface;
use App\Models\Inventory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The Eloquent implementation of the InventoryRepositoryInterface.
 * This class contains all the database logic for the Inventory model.
 */
class InventoryRepository implements InventoryRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getGroupedInventory(?string $searchTerm): LengthAwarePaginator
    {
        $query = Inventory::query()
            ->join('medicines', 'inventories.medicine_id', '=', 'medicines.id')
            ->select(
                'inventories.medicine_id',
                'medicines.name',
                'medicines.pack',
                DB::raw('SUM(inventories.quantity) as total_quantity')
            )
            ->groupBy('inventories.medicine_id', 'medicines.name', 'medicines.pack')
            ->orderBy('medicines.name', 'asc');

        if ($searchTerm) {
            $query->where('medicines.name', 'like', '%' . $searchTerm . '%');
        }

        return $query->paginate(10);
    }

    /**
     * {@inheritdoc}
     */
    public function getDetailsForMedicine(int $medicineId): Collection
    {
        return Inventory::where('medicine_id', $medicineId)
            ->with('medicine')
            ->orderBy('expiry_date')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findByMedicineAndBatch(int $medicineId, ?string $batchNumber): ?Inventory
    {
        return Inventory::where('medicine_id', $medicineId)
            ->where('batch_number', $batchNumber)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findOldestAvailableBatch(int $medicineId, float $requiredQuantity): ?Inventory
    {
        return Inventory::where('medicine_id', $medicineId)
            ->where('quantity', '>=', $requiredQuantity)
            ->orderBy('id') // Assuming older inventory has a lower ID
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function adjustStock(int $medicineId, ?string $batchNumber, float $quantityChange, array $itemData = []): Inventory
    {
        $inventory = Inventory::firstOrNew([
            'medicine_id' => $medicineId,
            'batch_number' => $batchNumber,
        ]);

        // If it's a new inventory item from a purchase, populate its details
        if ($inventory->wasRecentlyCreated && $quantityChange > 0) {
            $inventory->fill([
                'purchase_bill_item_id' => $itemData['id'] ?? null,
                'mrp' => $itemData['mrp'] ?? 0,
                'ptr' => $itemData['ptr'] ?? 0,
                'purchase_price' => $itemData['purchase_price'] ?? 0,
                'sale_price' => $itemData['sale_price'] ?? 0,
                'gst' => $itemData['gst_percentage'] ?? 0,
                'expiry_date' => $itemData['expiry_date'] ?? null,
            ]);
        }

        $inventory->quantity = (float)($inventory->quantity ?? 0.0) + $quantityChange;

        if ($inventory->quantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => "Insufficient stock for batch '{$batchNumber}'."
            ]);
        }

        $inventory->save();
        return $inventory;
    }
}
