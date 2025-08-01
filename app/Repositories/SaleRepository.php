<?php

// File: app/Repositories/SaleRepository.php

namespace App\Repositories;

use App\Interfaces\SaleRepositoryInterface;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * The Eloquent implementation of the SaleRepositoryInterface.
 * This class contains all the database logic for the Sale model.
 */
class SaleRepository implements SaleRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAllPaginated(): LengthAwarePaginator
    {
        return Sale::with('customer')
            ->latest()
            ->paginate(15);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?Sale
    {
        return Sale::with(['customer', 'saleItems.medicine'])->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestSaleId(): ?int
    {
        return Sale::withTrashed()->max('id');
    }

    /**
     * {@inheritdoc}
     */
    public function billNumberExists(string $billNumber): bool
    {
        return Sale::withTrashed()->where('bill_number', $billNumber)->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function createSale(array $data): Sale
    {
        dd($data);
        return Sale::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function createItem(int $saleId, array $itemData): SaleItem
    {
        $sale = $this->findById($saleId);
        return $sale->saleItems()->create($itemData);
    }
    
    /**
     * {@inheritdoc}
     */
    public function updateSale(int $saleId, array $data): bool
    {
        $sale = $this->findById($saleId);
        return $sale ? $sale->update($data) : false;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsForSale(int $saleId): Collection
    {
        $sale = $this->findById($saleId);
        return $sale ? $sale->saleItems : new Collection();
    }

    /**
     * {@inheritdoc}
     */
    public function findItemById(int $itemId): ?SaleItem
    {
        return SaleItem::find($itemId);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteItem(int $itemId): bool
    {
        $item = $this->findItemById($itemId);
        return $item ? $item->delete() : false;
    }

    /**
     * {@inheritdoc}
     */
    public function updateItem(int $itemId, array $itemData): bool
    {
        $item = $this->findItemById($itemId);
        return $item ? $item->update($itemData) : false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteSale(int $saleId): bool
    {
        $sale = $this->findById($saleId);
        return $sale ? $sale->delete() : false;
    }
}
