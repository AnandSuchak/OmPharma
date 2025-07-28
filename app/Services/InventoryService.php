<?php

// File: app/Services/InventoryService.php

namespace App\Services;

use App\Interfaces\InventoryRepositoryInterface;
use App\Interfaces\MedicineRepositoryInterface;
use App\Models\Medicine;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Handles the business logic for the Inventory module.
 */
class InventoryService
{
    protected InventoryRepositoryInterface $inventoryRepository;
    protected MedicineRepositoryInterface $medicineRepository;

    public function __construct(InventoryRepositoryInterface $inventoryRepository,MedicineRepositoryInterface $medicineRepository )
    {
        $this->inventoryRepository = $inventoryRepository;
        $this->medicineRepository = $medicineRepository;
    }

    /**
     * Get the grouped inventory list for the index page.
     *
     * @param string|null $searchTerm
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getGroupedInventory(?string $searchTerm)
    {
        return $this->inventoryRepository->getGroupedInventory($searchTerm);
    }

    /**
     * Get the detailed inventory and medicine details for the show page.
     *
     * @param int $medicineId
     * @return array
     */
    public function getInventoryDetailsForMedicine(int $medicineId): array
    {
        $inventoryDetails = $this->inventoryRepository->getDetailsForMedicine($medicineId);

        // Get the medicine details. If the inventory is empty, we still need to
        // find the medicine to display its name on the page.
         $medicine = $inventoryDetails->first()->medicine ?? $this->medicineRepository->findById($medicineId);

        if (!$medicine) {
            throw new ModelNotFoundException("Medicine with ID {$medicineId} not found.");
        }

        return [
            'inventoryDetails' => $inventoryDetails,
            'medicine' => $medicine,
        ];
    }
}
