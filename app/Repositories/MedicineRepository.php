<?php

// File: app/Repositories/MedicineRepository.php

namespace App\Repositories;

use App\Interfaces\MedicineRepositoryInterface;
use App\Models\Inventory;
use App\Models\Medicine;
use App\Models\PurchaseBillItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The Eloquent implementation of the MedicineRepositoryInterface.
 * This class contains all the database logic for the Medicine model.
 */
class MedicineRepository implements MedicineRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAllPaginated(array $filters): LengthAwarePaginator
    {
        $query = Medicine::withoutTrashed()->orderBy('name');

        if (isset($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('company_name', 'like', "%{$searchTerm}%")
                  ->orWhere('hsn_code', 'like', "%{$searchTerm}%");
            });
        }

        return $query->paginate(15);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?Medicine
    {
        return Medicine::find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Medicine
    {
        return Medicine::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): bool
    {
        $medicine = $this->findById($id);
        return $medicine ? $medicine->update($data) : false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $medicine = $this->findById($id);
        return $medicine ? $medicine->delete() : false;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRelatedTransactions(int $medicineId): bool
    {
        $medicine = $this->findById($medicineId);
        if (!$medicine) {
            return false;
        }
        
        return $medicine->purchaseBillItems()->exists() ||
               $medicine->inventories()->exists() ||
               $medicine->saleItems()->exists();
    }

    /**
     * {@inheritdoc}
     */
public function findBatchesForSale(int $medicineId): Collection
{
    return Inventory::query()
        ->join('purchase_bill_items', function ($join) {
            $join->on('inventories.medicine_id', '=', 'purchase_bill_items.medicine_id')
                 ->on('inventories.batch_number', '=', 'purchase_bill_items.batch_number');
        })
        ->join('medicines', 'inventories.medicine_id', '=', 'medicines.id')
        ->where('inventories.medicine_id', $medicineId)
        ->where('inventories.quantity', '>', 0)
        ->whereNull('purchase_bill_items.deleted_at')
        ->select(
            'medicines.id as medicine_id',
            'medicines.name as medicine_name',
            'medicines.pack',
            'inventories.batch_number',
            'inventories.expiry_date',
            'inventories.quantity',
            'purchase_bill_items.sale_price',
            'purchase_bill_items.gst_rate',
            'purchase_bill_items.ptr'
        )
        ->orderByRaw('CASE WHEN inventories.expiry_date IS NULL THEN 1 ELSE 0 END') // NULL last
        ->orderBy('inventories.expiry_date', 'asc') // Nearest expiry first
        ->get();
}

    /**
     * NEW: Fallback method to get the latest pricing info if no batches with stock are found.
     */
    public function findLatestPurchaseDetails(int $medicineId): ?PurchaseBillItem
    {
        return PurchaseBillItem::where('medicine_id', $medicineId)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findBatchesFromPastSale(int $medicineId, int $saleId): Collection
    {
        // Query for batches currently in stock
        $baseQuery = Inventory::query()
            ->select(
                'inventories.batch_number', 'inventories.expiry_date', 'inventories.quantity',
                'purchase_bill_items.sale_price', 'purchase_bill_items.gst_rate', 'purchase_bill_items.ptr'
            )
            ->join('purchase_bill_items', function ($join) {
                $join->on('inventories.medicine_id', '=', 'purchase_bill_items.medicine_id')
                    ->on('inventories.batch_number', '=', 'purchase_bill_items.batch_number');
            })
            ->where('inventories.medicine_id', $medicineId)
            ->whereNull('purchase_bill_items.deleted_at');

        // Query for batches that were part of the sale but might be out of stock now
        $pastSaleQuery = DB::table('sale_items')
            ->select(
                'sale_items.batch_number', 'purchase_bill_items.expiry_date', DB::raw('0 as quantity'),
                'purchase_bill_items.sale_price', 'purchase_bill_items.gst_rate', 'purchase_bill_items.ptr'
            )
            ->join('purchase_bill_items', function ($join) {
                $join->on('sale_items.medicine_id', '=', 'purchase_bill_items.medicine_id')
                    ->on('sale_items.batch_number', '=', 'purchase_bill_items.batch_number');
            })
            ->where('sale_items.sale_id', $saleId)
            ->where('sale_items.medicine_id', $medicineId)
            ->whereNull('purchase_bill_items.deleted_at')
            ->distinct();

        // Combine the two queries
        return $baseQuery->union($pastSaleQuery)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function searchWithStock(string $query): Collection
    {
        return Medicine::query()
            ->join('inventories as i', 'medicines.id', '=', 'i.medicine_id')
            ->where('i.quantity', '>', 0)
            ->whereNull('i.deleted_at')
            ->where(function($q) use ($query) {
                $q->where('medicines.name', 'like', "%{$query}%")
                  ->orWhere('medicines.company_name', 'like', "%{$query}%");
            })
            ->select('medicines.id', 'medicines.name', 'medicines.pack')
            ->distinct()
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function searchByNameOrCompany(string $query): Collection
    {
        return Medicine::where('name', 'like', "%{$query}%")
            ->orWhere('company_name', 'like', "%{$query}%")
            ->limit(20)
            ->get(['id', 'name', 'company_name', 'pack']);
    }

    /**
     * {@inheritdoc}
     */
    public function searchByName(string $query): Collection
    {
        return Medicine::where('name', 'LIKE', "%{$query}%")
            ->select('id', 'name', 'pack', 'company_name')
            ->limit(20)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findPacksByName(string $name, ?string $companyName): Collection
    {
        return Medicine::where('name', $name)
            ->when($companyName, function ($query) use ($companyName) {
                return $query->where('company_name', $companyName);
            })
            ->whereNull('deleted_at')
            ->get(['id', 'pack']);
    }
}
