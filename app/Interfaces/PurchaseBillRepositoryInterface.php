<?php

// File: app/Interfaces/PurchaseBillRepositoryInterface.php

namespace App\Interfaces;

use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface for the PurchaseBill Repository.
 * Defines all the data access methods for purchase bills and their items.
 */
interface PurchaseBillRepositoryInterface
{
    /**
     * Get all purchase bills with pagination and filters.
     */
    public function getAllPaginated(array $filters): LengthAwarePaginator;

    /**
     * Find a single purchase bill by its ID, with its relations.
     */
    public function findById(int $id): ?PurchaseBill;

    /**
     * Create a new purchase bill record.
     */
    public function createBill(array $data): PurchaseBill;
    
    /**
     * Create a new item for a purchase bill.
     */
    public function createItem(int $billId, array $itemData): PurchaseBillItem;

    /**
     * Get all original items for a given bill.
     */
    public function getOriginalItems(int $billId): Collection;

    /**
     * Find a specific purchase bill item by its ID.
     */
    public function findItemById(int $itemId): ?PurchaseBillItem;

    /**
     * Update an existing purchase bill item.
     */
    public function updateItem(int $itemId, array $itemData): bool;

    /**
     * Delete a purchase bill item by its ID.
     */
    public function deleteItem(int $itemId): bool;

    /**
     * Update the main details of a purchase bill.
     */
    public function updateBill(int $billId, array $data): bool;

    /**
     * Delete a purchase bill.
     */
    public function deleteBill(int $billId): bool;
}
