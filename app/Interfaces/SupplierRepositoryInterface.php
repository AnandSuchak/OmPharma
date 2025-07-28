<?php

namespace App\Interfaces;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SupplierRepositoryInterface
{
    /**
     * Get all suppliers with pagination and optional search.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(int $perPage = 15, ?string $searchTerm = null): LengthAwarePaginator;

    public function findById(int $id): ?Supplier;

    public function create(array $data): Supplier;

    public function update(int $id, array $data): ?Supplier;

    public function delete(int $id): bool;

    public function searchByName(string $query): Collection;
}
