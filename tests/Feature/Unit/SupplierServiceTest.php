<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\SupplierService;
use App\Interfaces\SupplierRepositoryInterface;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Illuminate\Support\Facades\Log; // Don't forget to import Log facade for mocking

class SupplierServiceTest extends TestCase
{
    protected $supplierRepository;
    protected SupplierService $supplierService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a Mockery mock for the SupplierRepositoryInterface
        $this->supplierRepository = Mockery::mock(SupplierRepositoryInterface::class);

        // Mock the Log facade as it's used in create, update, delete methods
        Log::shouldReceive('info')->byDefault();
        Log::shouldReceive('warning')->byDefault();

        // Instantiate the SupplierService, injecting the mocked repository
        $this->supplierService = new SupplierService($this->supplierRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_all_suppliers_paginated_with_filters()
    {
        // Arrange
        $filters = ['search' => 'Acme Pharma'];
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $perPage = 15; // As defined in the service

        $this->supplierRepository->shouldReceive('getAllPaginated')
                                 ->once()
                                 ->with($perPage, $filters['search'])
                                 ->andReturn($paginator);

        // Act
        $result = $this->supplierService->getAllSuppliers($filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test */
    public function it_can_get_all_suppliers_paginated_without_search_filter()
    {
        // Arrange
        $filters = []; // No search filter
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $perPage = 15;

        $this->supplierRepository->shouldReceive('getAllPaginated')
                                 ->once()
                                 ->with($perPage, null) // Expect null for search term
                                 ->andReturn($paginator);

        // Act
        $result = $this->supplierService->getAllSuppliers($filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test */
    public function it_can_create_a_supplier()
    {
        // Arrange
        $supplierData = ['name' => 'New Supplier Corp', 'contact_person' => 'Alice', 'phone' => '9876543210'];
        $supplier = Mockery::mock(Supplier::class);

        // Log::shouldReceive('info')->once()->with("Creating a new supplier with name: {$supplierData['name']}"); // Optional: If you want to assert the log call

        $this->supplierRepository->shouldReceive('create')
                                 ->once()
                                 ->with($supplierData)
                                 ->andReturn($supplier);

        // Act
        $result = $this->supplierService->createSupplier($supplierData);

        // Assert
        $this->assertInstanceOf(Supplier::class, $result);
    }

    /** @test */
    public function it_can_get_a_supplier_by_id()
    {
        // Arrange
        $supplierId = 1;
        $supplier = Mockery::mock(Supplier::class);

        $this->supplierRepository->shouldReceive('findById')
                                 ->once()
                                 ->with($supplierId)
                                 ->andReturn($supplier);

        // Act
        $result = $this->supplierService->getSupplierById($supplierId);

        // Assert
        $this->assertInstanceOf(Supplier::class, $result);
    }

    /** @test */
    public function it_returns_null_when_supplier_not_found_by_id()
    {
        // Arrange
        $supplierId = 999;

        $this->supplierRepository->shouldReceive('findById')
                                 ->once()
                                 ->with($supplierId)
                                 ->andReturn(null);

        // Act
        $result = $this->supplierService->getSupplierById($supplierId);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_update_a_supplier()
    {
        // Arrange
        $supplierId = 1;
        $updateData = ['name' => 'Updated Supplier Inc', 'phone' => '1122334455'];
        $updatedSupplier = Mockery::mock(Supplier::class);

        // Log::shouldReceive('info')->once()->with("Updating supplier with ID: {$supplierId}"); // Optional: If you want to assert the log call

        $this->supplierRepository->shouldReceive('update')
                                 ->once()
                                 ->with($supplierId, $updateData)
                                 ->andReturn($updatedSupplier);

        // Act
        $result = $this->supplierService->updateSupplier($supplierId, $updateData);

        // Assert
        $this->assertInstanceOf(Supplier::class, $result);
    }

    /** @test */
    public function it_returns_null_when_updating_non_existent_supplier()
    {
        // Arrange
        $supplierId = 999;
        $updateData = ['name' => 'Non Existent Co'];

        // Log::shouldReceive('info')->once()->with("Updating supplier with ID: {$supplierId}"); // Optional: If you want to assert the log call

        $this->supplierRepository->shouldReceive('update')
                                 ->once()
                                 ->with($supplierId, $updateData)
                                 ->andReturn(null);

        // Act
        $result = $this->supplierService->updateSupplier($supplierId, $updateData);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_delete_a_supplier()
    {
        // Arrange
        $supplierId = 1;

        // Log::shouldReceive('warning')->once()->with("Deleting supplier with ID: {$supplierId}"); // Optional: If you want to assert the log call

        $this->supplierRepository->shouldReceive('delete')
                                 ->once()
                                 ->with($supplierId)
                                 ->andReturn(true);

        // Act
        $result = $this->supplierService->deleteSupplier($supplierId);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_deleting_non_existent_supplier()
    {
        // Arrange
        $supplierId = 999;

        // Log::shouldReceive('warning')->once()->with("Deleting supplier with ID: {$supplierId}"); // Optional: If you want to assert the log call

        $this->supplierRepository->shouldReceive('delete')
                                 ->once()
                                 ->with($supplierId)
                                 ->andReturn(false);

        // Act
        $result = $this->supplierService->deleteSupplier($supplierId);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_search_suppliers_by_name()
    {
        // Arrange
        $query = 'Pharma';
        $suppliers = new Collection([
            Mockery::mock(Supplier::class),
            Mockery::mock(Supplier::class)
        ]);

        $this->supplierRepository->shouldReceive('searchByName')
                                 ->once()
                                 ->with($query)
                                 ->andReturn($suppliers);

        // Act
        $result = $this->supplierService->searchSuppliersByName($query);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    /** @test */
    public function it_returns_empty_collection_when_search_query_is_null_for_search_suppliers_by_name()
    {
        // Arrange
        $query = null;

        $this->supplierRepository->shouldNotReceive('searchByName'); // Should not hit repository if query is null

        // Act
        $result = $this->supplierService->searchSuppliersByName($query);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEmpty($result);
    }

    /** @test */
    public function it_returns_empty_collection_when_search_query_is_empty_string_for_search_suppliers_by_name()
    {
        // Arrange
        $query = '';

        $this->supplierRepository->shouldNotReceive('searchByName'); // Should not hit repository if query is empty

        // Act
        $result = $this->supplierService->searchSuppliersByName($query);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEmpty($result);
    }
}