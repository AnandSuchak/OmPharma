<?php

// File: tests/Unit/SupplierServiceTest.php

namespace Tests\Unit;

use Tests\TestCase; // <-- IMPORTANT: Use Laravel's base TestCase
use App\Services\SupplierService;
use App\Interfaces\SupplierRepositoryInterface;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test; // <-- Use modern attribute

class SupplierServiceTest extends TestCase // <-- IMPORTANT: Extend Laravel's TestCase
{
    protected $supplierRepository;
    protected SupplierService $supplierService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplierRepository = Mockery::mock(SupplierRepositoryInterface::class);

        // This now works because we are extending Laravel's TestCase
        Log::shouldReceive('info')->byDefault();
        Log::shouldReceive('warning')->byDefault();

        $this->supplierService = new SupplierService($this->supplierRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test] // <-- Use modern attribute
    public function it_can_get_all_suppliers_paginated_with_filters()
    {
        $filters = ['search' => 'Acme Pharma'];
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $this->supplierRepository->shouldReceive('getAllPaginated')->once()->with(15, 'Acme Pharma')->andReturn($paginator);
        $result = $this->supplierService->getAllSuppliers($filters);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    #[Test]
    public function it_can_get_all_suppliers_paginated_without_search_filter()
    {
        $filters = [];
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $this->supplierRepository->shouldReceive('getAllPaginated')->once()->with(15, null)->andReturn($paginator);
        $result = $this->supplierService->getAllSuppliers($filters);
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    #[Test]
    public function it_can_create_a_supplier()
    {
        $supplierData = ['name' => 'New Supplier Corp'];
        $supplier = Mockery::mock(Supplier::class);
        $this->supplierRepository->shouldReceive('create')->once()->with($supplierData)->andReturn($supplier);
        $result = $this->supplierService->createSupplier($supplierData);
        $this->assertInstanceOf(Supplier::class, $result);
    }

    #[Test]
    public function it_can_get_a_supplier_by_id()
    {
        $supplierId = 1;
        $supplier = Mockery::mock(Supplier::class);
        $this->supplierRepository->shouldReceive('findById')->once()->with($supplierId)->andReturn($supplier);
        $result = $this->supplierService->getSupplierById($supplierId);
        $this->assertInstanceOf(Supplier::class, $result);
    }

    #[Test]
    public function it_returns_null_when_supplier_not_found_by_id()
    {
        $supplierId = 999;
        $this->supplierRepository->shouldReceive('findById')->once()->with($supplierId)->andReturn(null);
        $result = $this->supplierService->getSupplierById($supplierId);
        $this->assertNull($result);
    }

    #[Test]
    public function it_can_update_a_supplier()
    {
        $supplierId = 1;
        $updateData = ['name' => 'Updated Supplier Inc'];
        $updatedSupplier = Mockery::mock(Supplier::class);
        $this->supplierRepository->shouldReceive('update')->once()->with($supplierId, $updateData)->andReturn($updatedSupplier);
        $result = $this->supplierService->updateSupplier($supplierId, $updateData);
        $this->assertInstanceOf(Supplier::class, $result);
    }

    #[Test]
    public function it_returns_null_when_updating_non_existent_supplier()
    {
        $supplierId = 999;
        $updateData = ['name' => 'Non Existent Co'];
        $this->supplierRepository->shouldReceive('update')->once()->with($supplierId, $updateData)->andReturn(null);
        $result = $this->supplierService->updateSupplier($supplierId, $updateData);
        $this->assertNull($result);
    }

    #[Test]
    public function it_can_delete_a_supplier()
    {
        $supplierId = 1;
        $this->supplierRepository->shouldReceive('delete')->once()->with($supplierId)->andReturn(true);
        $result = $this->supplierService->deleteSupplier($supplierId);
        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_deleting_non_existent_supplier()
    {
        $supplierId = 999;
        $this->supplierRepository->shouldReceive('delete')->once()->with($supplierId)->andReturn(false);
        $result = $this->supplierService->deleteSupplier($supplierId);
        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_search_suppliers_by_name()
    {
        $query = 'Pharma';
        $suppliers = new Collection([Mockery::mock(Supplier::class)]);
        $this->supplierRepository->shouldReceive('searchByName')->once()->with($query)->andReturn($suppliers);
        $result = $this->supplierService->searchSuppliersByName($query);
        $this->assertInstanceOf(Collection::class, $result);
    }

    #[Test]
    public function it_returns_empty_collection_when_search_query_is_null_for_search_suppliers_by_name()
    {
        $query = null;
        $result = $this->supplierService->searchSuppliersByName($query);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_returns_empty_collection_when_search_query_is_empty_string_for_search_suppliers_by_name()
    {
        $query = '';
        $result = $this->supplierService->searchSuppliersByName($query);
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEmpty($result);
    }
}
