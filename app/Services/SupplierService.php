<?php

namespace App\Services;

use App\Interfaces\SupplierRepositoryInterface;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class SupplierService
{
    protected SupplierRepositoryInterface $supplierRepository;

    public function __construct(SupplierRepositoryInterface $supplierRepository)
    {
        $this->supplierRepository = $supplierRepository;
    }

    /**
     * Get all suppliers with pagination and optional search filters.
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAllSuppliers(array $filters): LengthAwarePaginator
    {
        return $this->supplierRepository->getAllPaginated(15, $filters['search'] ?? null);
    }

    public function createSupplier(array $data): Supplier
    {
        Log::info("Creating a new supplier with name: {$data['name']}");
        return $this->supplierRepository->create($data);
    }

    public function getSupplierById(int $id): ?Supplier
    {
        return $this->supplierRepository->findById($id);
    }

    public function updateSupplier(int $id, array $data): ?Supplier
    {
        Log::info("Updating supplier with ID: {$id}");
        return $this->supplierRepository->update($id, $data);
    }

    public function deleteSupplier(int $id): bool
    {
        Log::warning("Deleting supplier with ID: {$id}");
        return $this->supplierRepository->delete($id);
    }

    public function searchSuppliersByName(?string $query): Collection
    {
        if (!$query) {
            return new Collection();
        }
        return $this->supplierRepository->searchByName($query);
    }
}
