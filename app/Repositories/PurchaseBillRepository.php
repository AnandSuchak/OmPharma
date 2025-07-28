<?php

// File: app/Repositories/PurchaseBillRepository.php

namespace App\Repositories;

use App\Interfaces\PurchaseBillRepositoryInterface;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * The Eloquent implementation of the PurchaseBillRepositoryInterface.
 * This class contains all the database logic for the PurchaseBill model.
 */
class PurchaseBillRepository implements PurchaseBillRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAllPaginated(array $filters): LengthAwarePaginator
    {
        $query = PurchaseBill::with('supplier')
            ->withoutTrashed()
            ->orderByDesc('id');

        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('bill_number', 'like', "%{$searchTerm}%")
                  ->orWhereHas('supplier', function($sq) use ($searchTerm) {
                      $sq->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        return $query->paginate(15);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?PurchaseBill
    {
        return PurchaseBill::with('supplier', 'purchaseBillItems.medicine')->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function createBill(array $data): PurchaseBill
    {
        return PurchaseBill::create($data);
    }
    
    /**
     * {@inheritdoc}
     */
    public function createItem(int $billId, array $itemData): PurchaseBillItem
    {
        return PurchaseBill::find($billId)->purchaseBillItems()->create($itemData);
    }

    /**
     * {@inheritdoc}
     */
    public function getOriginalItems(int $billId): Collection
    {
        return PurchaseBill::find($billId)->purchaseBillItems()->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findItemById(int $itemId): ?PurchaseBillItem
    {
        return PurchaseBillItem::find($itemId);
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
    public function deleteItem(int $itemId): bool
    {
        $item = $this->findItemById($itemId);
        return $item ? $item->delete() : false;
    }

    /**
     * {@inheritdoc}
     */
    public function updateBill(int $billId, array $data): bool
    {
        $bill = $this->findById($billId);
        return $bill ? $bill->update($data) : false;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteBill(int $billId): bool
    {
        $bill = $this->findById($billId);
        return $bill ? $bill->delete() : false;
    }
}
