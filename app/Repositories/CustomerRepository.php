<?php

// File: app/Repositories/CustomerRepository.php

namespace App\Repositories;

use App\Interfaces\CustomerRepositoryInterface;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CustomerRepository implements CustomerRepositoryInterface
{
    /**
     * Get all customers with pagination and filtering.
     *
     * @param array $data
     * @return LengthAwarePaginator
     */
    public function getAllPaginated(array $data = []): LengthAwarePaginator
    {
        $query = Customer::query();

        // Handle searching/filtering
        if (isset($data['search']) && $data['search'] !== '') {
            $searchTerm = $data['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('phone_number', 'like', "%{$searchTerm}%");
            });
        }

        // Get per_page from request, default to 15
        $perPage = $data['per_page'] ?? 15;

        // Use paginate() to return the paginator instance
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById(int $id): ?Customer
    {
        return Customer::find($id);
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(int $id, array $data): ?Customer
    {
        $customer = $this->findById($id);
        if ($customer) {
            $customer->update($data);
            return $customer;
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $customer = $this->findById($id);
        if ($customer) {
            return $customer->delete();
        }
        return false;
    }

    public function search(string $query): Collection
    {
        return Customer::where('name', 'like', "%{$query}%")
            ->orWhere('phone_number', 'like', "%{$query}%")
            ->take(10) // Limit results for performance
            ->get();
    }
}
