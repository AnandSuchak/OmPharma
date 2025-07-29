<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CustomerService;
use PHPUnit\Framework\Attributes\Test;
use App\Interfaces\CustomerRepositoryInterface;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Illuminate\Support\Facades\Log; // Add this line to import the Log facade

class CustomerServiceTest extends TestCase
{
    protected $customerRepository;
    protected CustomerService $customerService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a Mockery mock for the CustomerRepositoryInterface
        $this->customerRepository = Mockery::mock(CustomerRepositoryInterface::class);

        // --- ADD THIS LINE TO MOCK THE LOG FACADE ---
        // Mock the Log facade to prevent errors from its calls
        // We use `shouldReceive('info')` and `shouldReceive('warning')` to allow these calls to happen without failing.
        // `byDefault()` makes it so we don't need to specify it for every test method.
        Log::shouldReceive('info')->byDefault();
        Log::shouldReceive('warning')->byDefault();
        // --- END ADDITION ---


        // Instantiate the CustomerService, injecting the mocked repository
        $this->customerService = new CustomerService($this->customerRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_get_all_customers_paginated()
    {
        $filters = ['search' => 'John Doe'];
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->customerRepository->shouldReceive('getAllPaginated')
                                 ->once()
                                 ->with($filters)
                                 ->andReturn($paginator);

        $result = $this->customerService->getAllCustomers($filters);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    #[Test]
    public function it_can_create_a_customer()
    {
        $customerData = ['name' => 'Jane Doe', 'email' => 'jane@example.com', 'phone' => '1234567890'];
        $customer = Mockery::mock(Customer::class);

        // Optionally, you can assert that Log::info was called if that's part of the test's concern
        // Log::shouldReceive('info')->once()->with("Creating a new customer with name: {$customerData['name']}");

        $this->customerRepository->shouldReceive('create')
                                 ->once()
                                 ->with($customerData)
                                 ->andReturn($customer);

        $result = $this->customerService->createCustomer($customerData);

        $this->assertInstanceOf(Customer::class, $result);
    }

    #[Test]
    public function it_can_get_a_customer_by_id()
    {
        $customerId = 1;
        $customer = Mockery::mock(Customer::class);

        $this->customerRepository->shouldReceive('findById')
                                 ->once()
                                 ->with($customerId)
                                 ->andReturn($customer);

        $result = $this->customerService->getCustomerById($customerId);

        $this->assertInstanceOf(Customer::class, $result);
    }

    #[Test]
    public function it_returns_null_when_customer_not_found_by_id()
    {
        $customerId = 999;

        $this->customerRepository->shouldReceive('findById')
                                 ->once()
                                 ->with($customerId)
                                 ->andReturn(null);

        $result = $this->customerService->getCustomerById($customerId);

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_update_a_customer()
    {
        $customerId = 1;
        $customerData = ['name' => 'Jane Smith', 'email' => 'jane.smith@example.com'];
        $updatedCustomer = Mockery::mock(Customer::class);

        // Log::shouldReceive('info')->once()->with("Updating customer with ID: {$customerId}");

        $this->customerRepository->shouldReceive('update')
                                 ->once()
                                 ->with($customerId, $customerData)
                                 ->andReturn($updatedCustomer);

        $result = $this->customerService->updateCustomer($customerId, $customerData);

        $this->assertInstanceOf(Customer::class, $result);
    }

    #[Test]
    public function it_returns_null_when_updating_non_existent_customer()
    {
        $customerId = 999;
        $customerData = ['name' => 'Jane Smith'];

        // Log::shouldReceive('info')->once()->with("Updating customer with ID: {$customerId}");

        $this->customerRepository->shouldReceive('update')
                                 ->once()
                                 ->with($customerId, $customerData)
                                 ->andReturn(null);

        $result = $this->customerService->updateCustomer($customerId, $customerData);

        $this->assertNull($result);
    }

    #[Test]
    public function it_can_delete_a_customer()
    {
        $customerId = 1;

        // Log::shouldReceive('warning')->once()->with("Deleting customer with ID: {$customerId}");

        $this->customerRepository->shouldReceive('delete')
                                 ->once()
                                 ->with($customerId)
                                 ->andReturn(true);

        $result = $this->customerService->deleteCustomer($customerId);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_deleting_non_existent_customer()
    {
        $customerId = 999;

        // Log::shouldReceive('warning')->once()->with("Deleting customer with ID: {$customerId}");

        $this->customerRepository->shouldReceive('delete')
                                 ->once()
                                 ->with($customerId)
                                 ->andReturn(false);

        $result = $this->customerService->deleteCustomer($customerId);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_search_customers_by_query()
    {
        $query = 'John';
        $customers = new Collection([
            Mockery::mock(Customer::class),
            Mockery::mock(Customer::class)
        ]);

        $this->customerRepository->shouldReceive('search')
                                 ->once()
                                 ->with($query)
                                 ->andReturn($customers);

        $result = $this->customerService->searchCustomers($query);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    #[Test]
    public function it_returns_empty_collection_when_search_query_is_null()
    {
        $query = null;

        $this->customerRepository->shouldNotReceive('search');

        $result = $this->customerService->searchCustomers($query);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_returns_empty_collection_when_search_query_is_empty_string()
    {
        $query = '';

        $this->customerRepository->shouldNotReceive('search');

        $result = $this->customerService->searchCustomers($query);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEmpty($result);
    }
}  