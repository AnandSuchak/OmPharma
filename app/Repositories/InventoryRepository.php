<?php

// File: app/Repositories/InventoryRepository.php

namespace App\Repositories;

use App\Interfaces\InventoryRepositoryInterface;
use App\Models\Inventory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
}
