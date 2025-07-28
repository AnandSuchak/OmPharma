<?php

// File: app/Interfaces/SaleRepositoryInterface.php

namespace App\Interfaces;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Interface for the Sale Repository.
 * Defines all the data access methods for sales and their items.
 */
interface SaleRepositoryInterface
{
    /**
     * Get all sales with pagination.
     */
    public function getAllPaginated(): LengthAwarePaginator;

    /**
     * Find a single sale by its ID, with its relations.
     */
    public function findById(int $id): ?Sale;

    /**
     * Get the ID of the most recently created sale (including soft-deleted ones).
     */
    public function getLatestSaleId(): ?int;

    /**
     * Check if a bill number already exists.
     */
    public function billNumberExists(string $billNumber): bool;

    /**
     * Create a new sale record.
     */
    public function createSale(array $data): Sale;

    /**
     * Create a new item for a sale.
     */
    public function createItem(int $saleId, array $itemData): SaleItem;
    
    /**
     * Update the main details of a sale.
     */
    public function updateSale(int $saleId, array $data): bool;

    /**
     * Get all items for a given sale.
     */
    public function getItemsForSale(int $saleId): Collection;

    /**
     * Find a specific sale item by its ID.
     */
    public function findItemById(int $itemId): ?SaleItem;

    /**
     * Delete a sale item by its ID.
     */
    public function deleteItem(int $itemId): bool;

    /**
     * Update an existing sale item.
     */
    public function updateItem(int $itemId, array $itemData): bool;

    /**
     * Delete a sale (soft delete).
     */
    public function deleteSale(int $saleId): bool;
}
