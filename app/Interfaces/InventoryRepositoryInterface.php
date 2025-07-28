<?php

// File: app/Interfaces/InventoryRepositoryInterface.php

namespace App\Interfaces;

use App\Models\Medicine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface for the Inventory Repository.
 * Defines all the data access methods for inventory.
 */
interface InventoryRepositoryInterface
{
    /**
     * Get a paginated list of inventory items, grouped by medicine.
     *
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function getGroupedInventory(?string $searchTerm): LengthAwarePaginator;

    /**
     * Get the detailed inventory records for a specific medicine.
     *
     * @param int $medicineId
     * @return Collection
     */
    public function getDetailsForMedicine(int $medicineId): Collection;
}
