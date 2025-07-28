<?php

// 1. The Interface: app/Interfaces/MedicineRepositoryInterface.php
// Defines the contract for all data access related to medicines.

namespace App\Interfaces;

use App\Models\Medicine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface MedicineRepositoryInterface
{
    public function getAllPaginated(array $filters): LengthAwarePaginator;
    public function findById(int $id): ?Medicine;
    public function create(array $data): Medicine;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
    public function hasRelatedTransactions(int $medicineId): bool;
    public function findBatchesForSale(int $medicineId): Collection;
    public function findBatchesFromPastSale(int $medicineId, int $saleId): Collection;
    public function searchWithStock(string $query): Collection;
    public function searchByNameOrCompany(string $query): Collection;
    public function searchByName(string $query): Collection;
    public function findPacksByName(string $name, ?string $companyName): Collection;
}
