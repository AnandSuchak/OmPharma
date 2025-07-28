<?php

namespace App\Repositories;

use App\Interfaces\SupplierRepositoryInterface;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SupplierRepository implements SupplierRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getAllPaginated(int $perPage = 15, ?string $searchTerm = null): LengthAwarePaginator
    {
        $query = Supplier::latest();

        if ($searchTerm) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('phone_number', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('address', 'like', "%{$searchTerm}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?Supplier
    {
        return Supplier::find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): ?Supplier
    {
        $supplier = $this->findById($id);
        if ($supplier) {
            $supplier->update($data);
            return $supplier;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $supplier = $this->findById($id);
        if ($supplier) {
            return $supplier->delete();
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function searchByName(string $query): Collection
    {
        return Supplier::where('name', 'LIKE', "%{$query}%")
            ->limit(15)
            ->get(['id', 'name']);
    }
}
