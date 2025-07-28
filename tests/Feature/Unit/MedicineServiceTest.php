<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\MedicineService;
use App\Interfaces\MedicineRepositoryInterface;
use App\Models\Medicine;
use App\Models\SaleItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection; // Alias for Eloquent's Collection
use Illuminate\Support\Collection as BaseCollection; // Alias for the generic Support Collection
use Mockery;
use Exception;
use Carbon\Carbon;

class MedicineServiceTest extends TestCase
{
    protected $medicineRepository;
    protected MedicineService $medicineService;
    protected $mockSaleItem;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a Mockery mock for the MedicineRepositoryInterface
        $this->medicineRepository = Mockery::mock(MedicineRepositoryInterface::class);

        // --- FIX FOR FAILURE 2: Mock SaleItem statically using 'alias:' and store it ---
        // This sets up a static mock alias for SaleItem so that SaleItem::where() etc. are intercepted
        $this->mockSaleItem = Mockery::mock('alias:App\Models\SaleItem');
        // --- END FIX ---

        // Instantiate the MedicineService, injecting the mocked repository
        $this->medicineService = new MedicineService($this->medicineRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_get_all_medicines_paginated()
    {
        // Arrange
        $filters = ['search' => 'Paracetamol'];
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->medicineRepository->shouldReceive('getAllPaginated')
                                 ->once()
                                 ->with($filters)
                                 ->andReturn($paginator);

        // Act
        $result = $this->medicineService->getAllMedicines($filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    /** @test */
    public function it_can_create_a_medicine()
    {
        // Arrange
        $medicineData = ['name' => 'Amoxicillin', 'pack' => '500mg', 'company_name' => 'GSK'];
        $medicine = Mockery::mock(Medicine::class);

        $this->medicineRepository->shouldReceive('create')
                                 ->once()
                                 ->with($medicineData)
                                 ->andReturn($medicine);

        // Act
        $result = $this->medicineService->createMedicine($medicineData);

        // Assert
        $this->assertInstanceOf(Medicine::class, $result);
    }

    /** @test */
    public function it_can_get_a_medicine_by_id()
    {
        // Arrange
        $medicineId = 1;
        $medicine = Mockery::mock(Medicine::class);

        $this->medicineRepository->shouldReceive('findById')
                                 ->once()
                                 ->with($medicineId)
                                 ->andReturn($medicine);

        // Act
        $result = $this->medicineService->getMedicineById($medicineId);

        // Assert
        $this->assertInstanceOf(Medicine::class, $result);
    }

    /** @test */
    public function it_returns_null_when_medicine_not_found_by_id()
    {
        // Arrange
        $medicineId = 999;

        $this->medicineRepository->shouldReceive('findById')
                                 ->once()
                                 ->with($medicineId)
                                 ->andReturn(null);

        // Act
        $result = $this->medicineService->getMedicineById($medicineId);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_can_update_a_medicine()
    {
        // Arrange
        $medicineId = 1;
        $updateData = ['name' => 'Amoxicillin New', 'pack' => '250mg'];

        $this->medicineRepository->shouldReceive('update')
                                 ->once()
                                 ->with($medicineId, $updateData)
                                 ->andReturn(true); // update method returns boolean

        // Act
        $result = $this->medicineService->updateMedicine($medicineId, $updateData);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_when_updating_non_existent_medicine()
    {
        // Arrange
        $medicineId = 999;
        $updateData = ['name' => 'Non Existent Med'];

        $this->medicineRepository->shouldReceive('update')
                                 ->once()
                                 ->with($medicineId, $updateData)
                                 ->andReturn(false);

        // Act
        $result = $this->medicineService->updateMedicine($medicineId, $updateData);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_can_delete_a_medicine_without_related_transactions()
    {
        // Arrange
        $medicineId = 1;

        $this->medicineRepository->shouldReceive('hasRelatedTransactions')
                                 ->once()
                                 ->with($medicineId)
                                 ->andReturn(false); // No related transactions

        $this->medicineRepository->shouldReceive('delete')
                                 ->once()
                                 ->with($medicineId)
                                 ->andReturn(true);

        // Act
        $result = $this->medicineService->deleteMedicine($medicineId);

        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_throws_exception_when_deleting_medicine_with_related_transactions()
    {
        // Arrange
        $medicineId = 1;

        $this->medicineRepository->shouldReceive('hasRelatedTransactions')
                                 ->once()
                                 ->with($medicineId)
                                 ->andReturn(true); // Has related transactions

        // We expect an Exception to be thrown
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot delete medicine that has related transactions.');

        // Act
        $this->medicineService->deleteMedicine($medicineId);

        // Assert: No direct assertion here, the expectation handles the assertion
        // Also assert that delete was NOT called
        $this->medicineRepository->shouldNotHaveReceived('delete');
    }

    /** @test */
    public function it_gets_formatted_batches_for_new_sale()
    {
        // Arrange
        $medicineId = 1;
        $saleId = null; // New sale

        // --- FIX FOR FAILURE 1: Repository must return EloquentCollection due to type hint ---
        $rawBatches = new EloquentCollection([ // Changed to EloquentCollection
            (object)['batch_number' => 'B001', 'expiry_date' => '2025-12-31', 'quantity' => 100, 'sale_price' => 10.50, 'ptr' => 8.00, 'gst_rate' => 5],
            (object)['batch_number' => 'B002', 'expiry_date' => null, 'quantity' => 50, 'sale_price' => 12.00, 'ptr' => 9.00, 'gst_rate' => 12],
        ]);

        $this->medicineRepository->shouldReceive('findBatchesForSale')
                                 ->once()
                                 ->with($medicineId)
                                 ->andReturn($rawBatches);

        // Act
        $result = $this->medicineService->getFormattedBatches($medicineId, $saleId);

        // Assert: The service's map method returns Illuminate\Support\Collection.
        $this->assertInstanceOf(BaseCollection::class, $result);
        $this->assertCount(2, $result);

        $this->assertEquals([
            'batch_number' => 'B001',
            'expiry_date' => '2025-12-31',
            'quantity' => 100.0,
            'sale_price' => 10.50,
            'ptr' => 8.00,
            'gst' => 5.0,
            'existing_sale_item' => null
        ], $result->first());

        $this->assertEquals([
            'batch_number' => 'B002',
            'expiry_date' => '', // Null expiry date should be formatted as empty string
            'quantity' => 50.0,
            'sale_price' => 12.00,
            'ptr' => 9.00,
            'gst' => 12.0,
            'existing_sale_item' => null
        ], $result->last());
    }

    /** @test */
    public function it_gets_formatted_batches_for_existing_sale()
    {
        // Arrange
        $medicineId = 1;
        $saleId = 101; // Existing sale

        // --- FIX FOR FAILURE 1 (again): Repository must return EloquentCollection ---
        $rawBatches = new EloquentCollection([
            (object)['batch_number' => 'B001', 'expiry_date' => '2025-12-31', 'quantity' => 100, 'sale_price' => 10.50, 'ptr' => 8.00, 'gst_rate' => 5],
            (object)['batch_number' => 'B003', 'expiry_date' => '2024-06-15', 'quantity' => 200, 'sale_price' => 9.00, 'ptr' => 7.00, 'gst_rate' => 12],
        ]);

        // --- FIX FOR FAILURE 2: Define expectations on the mocked alias property ---
        $this->mockSaleItem->shouldReceive('where')
            ->with('sale_id', $saleId)
            ->andReturnSelf()
            ->shouldReceive('where')
            ->with('medicine_id', $medicineId)
            ->andReturnSelf()
            ->shouldReceive('get')
            ->andReturn(new EloquentCollection([
                (object)['id' => 1, 'sale_id' => $saleId, 'medicine_id' => $medicineId, 'batch_number' => 'B001', 'quantity' => 10, 'free_quantity' => 0],
            ]));
        // --- END FIX ---

        $this->medicineRepository->shouldReceive('findBatchesFromPastSale')
                                 ->once()
                                 ->with($medicineId, $saleId)
                                 ->andReturn($rawBatches);

        // Act
        $result = $this->medicineService->getFormattedBatches($medicineId, $saleId);

        // Assert: The service's map method returns Illuminate\Support\Collection.
        $this->assertInstanceOf(BaseCollection::class, $result);
        $this->assertCount(2, $result);

        // Check the first batch with existing sale item
        $this->assertEquals('B001', $result[0]['batch_number']);
        $this->assertNotNull($result[0]['existing_sale_item']);
        $this->assertEquals(10, $result[0]['existing_sale_item']->quantity); // Check a property on the mocked SaleItem

        // Check the second batch without existing sale item
        $this->assertEquals('B003', $result[1]['batch_number']);
        $this->assertNull($result[1]['existing_sale_item']);
    }
    
    /** @test */
    public function it_gets_formatted_search_results_with_stock_single_pack()
    {
        // Arrange
        $query = 'Paracetamol';
        // Repository should return EloquentCollection as it's directly from DB
        $medicinesWithStock = new EloquentCollection([
            (object)['id' => 1, 'name' => 'Paracetamol', 'pack' => '500mg', 'company_name' => 'ABC Pharma'],
        ]);

        $this->medicineRepository->shouldReceive('searchWithStock')
                                 ->once()
                                 ->with($query)
                                 ->andReturn($medicinesWithStock);

        // Act
        $result = $this->medicineService->getFormattedSearchWithStock($query);

        // Assert
        $this->assertIsArray($result); // The service converts to array
        $this->assertCount(1, $result);
        $this->assertEquals([
            'id' => 'Paracetamol',
            'text' => 'Paracetamol (ABC Pharma) - 500mg',
            'packs' => [[
                'medicine_id' => 1,
                'pack' => '500mg',
                'text' => '500mg',
            ]]
        ], $result[0]);
    }

    /** @test */
    public function it_gets_formatted_search_results_with_stock_multiple_packs()
    {
        // Arrange
        $query = 'Ibuprofen';
        // Repository should return EloquentCollection
        $medicinesWithStock = new EloquentCollection([
            (object)['id' => 10, 'name' => 'Ibuprofen', 'pack' => '200mg', 'company_name' => 'XYZ Meds'],
            (object)['id' => 11, 'name' => 'Ibuprofen', 'pack' => '400mg', 'company_name' => 'XYZ Meds'],
        ]);

        $this->medicineRepository->shouldReceive('searchWithStock')
                                 ->once()
                                 ->with($query)
                                 ->andReturn($medicinesWithStock);

        // Act
        $result = $this->medicineService->getFormattedSearchWithStock($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([
            'id' => 'Ibuprofen',
            'text' => 'Ibuprofen (XYZ Meds) - Multiple Packs',
            'packs' => [
                ['medicine_id' => 10, 'pack' => '200mg', 'text' => '200mg'],
                ['medicine_id' => 11, 'pack' => '400mg', 'text' => '400mg'],
            ]
        ], $result[0]);
    }

    /** @test */
    public function it_gets_formatted_search_results_with_stock_with_generic_company()
    {
        // Arrange
        $query = 'Vitamin C';
        // Repository should return EloquentCollection
        $medicinesWithStock = new EloquentCollection([
            (object)['id' => 20, 'name' => 'Vitamin C', 'pack' => '100mg', 'company_name' => null],
        ]);

        $this->medicineRepository->shouldReceive('searchWithStock')
                                 ->once()
                                 ->with($query)
                                 ->andReturn($medicinesWithStock);

        // Act
        $result = $this->medicineService->getFormattedSearchWithStock($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Vitamin C (Generic) - 100mg', $result[0]['text']);
    }

    /** @test */
    public function it_gets_formatted_search_by_name_or_company()
    {
        // Arrange
        $query = 'Aspirin';
        // Repository should return EloquentCollection
        $items = new EloquentCollection([
            (object)['id' => 1, 'name' => 'Aspirin', 'company_name' => 'Bayer', 'pack' => '100mg'],
            (object)['id' => 2, 'name' => 'Aspirin Plus', 'company_name' => null, 'pack' => null],
        ]);

        $this->medicineRepository->shouldReceive('searchByNameOrCompany')
                                 ->once()
                                 ->with($query)
                                 ->andReturn($items);

        // Act
        $result = $this->medicineService->getFormattedSearchByNameOrCompany($query);

        // Assert: The service's map method returns Illuminate\Support\Collection.
        $this->assertInstanceOf(BaseCollection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals([
            'id' => 1,
            'text' => 'Aspirin (Bayer) - 100mg',
            'pack' => '100mg'
        ], $result->first());
        $this->assertEquals([
            'id' => 2,
            'text' => 'Aspirin Plus (Generic)', // Pack should be omitted if null
            'pack' => null
        ], $result->last());
    }

    /** @test */
    public function it_gets_formatted_search_by_name_for_purchase()
    {
        // Arrange
        $query = 'Paracetamol';
        // Repository should return EloquentCollection
        $medicines = new EloquentCollection([
            (object)['id' => 1, 'name' => 'Paracetamol', 'company_name' => 'ABC Pharma', 'pack' => '500mg'],
            (object)['id' => 2, 'name' => 'Paracetamol', 'company_name' => null, 'pack' => null],
        ]);

        $this->medicineRepository->shouldReceive('searchByName')
                                 ->once()
                                 ->with($query)
                                 ->andReturn($medicines);

        // Act
        $result = $this->medicineService->getFormattedSearchByName($query);

        // Assert: The service's map method returns Illuminate\Support\Collection.
        $this->assertInstanceOf(BaseCollection::class, $result);
        $this->assertCount(2, $result);

        $this->assertEquals([
            'id' => 1,
            'text' => 'Paracetamol - 500mg (ABC Pharma)',
        ], $result->first());

        $this->assertEquals([
            'id' => 2,
            'text' => 'Paracetamol', // No pack or company
        ], $result->last());
    }

    /** @test */
    public function it_can_get_packs_for_a_given_medicine_name()
    {
        // Arrange
        $name = 'Paracetamol';
        $companyName = 'ABC Pharma';
        // Repository should return EloquentCollection
        $packs = new EloquentCollection([
            (object)['id' => 1, 'pack' => '500mg'],
            (object)['id' => 2, 'pack' => '650mg'],
        ]);

        $this->medicineRepository->shouldReceive('findPacksByName')
                                 ->once()
                                 ->with($name, $companyName)
                                 ->andReturn($packs);

        // Act
        $result = $this->medicineService->getPacksForName($name, $companyName);

        // Assert
        $this->assertInstanceOf(EloquentCollection::class, $result); // This method doesn't use map() so it returns the repository's collection directly
        $this->assertCount(2, $result);
        $this->assertEquals('500mg', $result->first()->pack);
    }
}