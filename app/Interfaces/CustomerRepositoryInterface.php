<?php

// File: app/Interfaces/CustomerRepositoryInterface.php

namespace App\Interfaces;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CustomerRepositoryInterface
{
    /**
     * Get all customers with pagination and optional search.
     *
     * @param int $perPage
     * @param string|null $searchTerm
     * @return LengthAwarePaginator
     */
   public function getAllPaginated(array $data = []): LengthAwarePaginator;// <-- ADD THIS LINE
    /**
     * Get a customer by its ID.
     *
     * @param int $id
     * @return Customer|null
     */
    public function findById(int $id): ?Customer;

    /**
     * Create a new customer.
     *
     * @param array $data
     * @return Customer
     */
    public function create(array $data): Customer;

    /**
     * Update a customer by its ID.
     *
     * @param int $id
     * @param array $data
     * @return Customer|null
     */
    public function update(int $id, array $data): ?Customer;

    /**
     * Delete a customer by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Search for customers by name or phone for AJAX requests.
     *
     * @param string $query
     * @return Collection
     */
    public function search(string $query): Collection;
}
