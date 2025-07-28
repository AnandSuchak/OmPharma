<?php

// File: app/Services/CustomerService.php

namespace App\Services;

use App\Interfaces\CustomerRepositoryInterface;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class CustomerService
{
    protected CustomerRepositoryInterface $customerRepository;

    /**
     * CustomerService constructor.
     *
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(CustomerRepositoryInterface $customerRepository)
    {
        $this->customerRepository = $customerRepository;
    }

    /**
     * Get all customers with pagination and optional search filters.
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
      public function getAllCustomers(array $data = []): LengthAwarePaginator
    {
        // The service's job is to call the repository, not to build queries.
        return $this->customerRepository->getAllPaginated($data);
    }

    /**
     * Create a new customer.
     *
     * @param array $data The validated data for the new customer.
     * @return Customer
     */
    public function createCustomer(array $data): Customer
    {
        Log::info("Creating a new customer with name: {$data['name']}");
        return $this->customerRepository->create($data);
    }

    /**
     * Get a single customer by its ID.
     *
     * @param int $id
     * @return Customer|null
     */
    public function getCustomerById(int $id): ?Customer
    {
        return $this->customerRepository->findById($id);
    }

    /**
     * Update an existing customer.
     *
     * @param int $id The ID of the customer to update.
     * @param array $data The validated data for the update.
     * @return Customer|null
     */
    public function updateCustomer(int $id, array $data): ?Customer
    {
        Log::info("Updating customer with ID: {$id}");
        return $this->customerRepository->update($id, $data);
    }

    /**
     * Delete a customer by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteCustomer(int $id): bool
    {
        Log::warning("Deleting customer with ID: {$id}");
        return $this->customerRepository->delete($id);
    }

    /**
     * Search for customers by name or phone number.
     *
     * @param string|null $query The search term.
     * @return Collection
     */
    public function searchCustomers(?string $query): Collection
    {
        if (!$query) {
            return new Collection();
        }
        return $this->customerRepository->search($query);
    }
}
