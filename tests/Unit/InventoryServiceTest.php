<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\InventoryService;
use App\Interfaces\InventoryRepositoryInterface;
use App\Interfaces\MedicineRepositoryInterface; // <--- NEW: Import the Medicine Repository Interface
use App\Models\Medicine;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class InventoryServiceTest extends TestCase
{
    protected $inventoryRepository;
    protected $medicineRepository; // <--- NEW: Property for the Medicine Repository Mock
    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inventoryRepository = Mockery::mock(InventoryRepositoryInterface::class);
        $this->medicineRepository = Mockery::mock(MedicineRepositoryInterface::class); // <--- NEW: Mock MedicineRepository

        // Bind both mocked repositories to Laravel's service container.
        $this->app->instance(InventoryRepositoryInterface::class, $this->inventoryRepository);
        $this->app->instance(MedicineRepositoryInterface::class, $this->medicineRepository); // <--- NEW: Bind MedicineRepository

        // Let Laravel resolve the InventoryService, which will now inject both mocks.
        $this->inventoryService = $this->app->make(InventoryService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_get_grouped_inventory()
    {
        $searchTerm = 'paracetamol';
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->inventoryRepository->shouldReceive('getGroupedInventory')
                                 ->once()
                                 ->with($searchTerm)
                                 ->andReturn($paginator);

        $result = $this->inventoryService->getGroupedInventory($searchTerm);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    #[Test]
    public function it_can_get_grouped_inventory_with_null_search_term()
    {
        $searchTerm = null;
        $paginator = Mockery::mock(LengthAwarePaginator::class);

        $this->inventoryRepository->shouldReceive('getGroupedInventory')
                                 ->once()
                                 ->with(null)
                                 ->andReturn($paginator);

        $result = $this->inventoryService->getGroupedInventory($searchTerm);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    #[Test]
    public function it_gets_inventory_details_for_medicine_when_inventory_exists()
    {
        // Arrange
        $medicineId = 1;

        $mockMedicine = Mockery::mock(Medicine::class)->makePartial();
        $mockMedicine->id = $medicineId;
        $mockMedicine->name = 'Paracetamol 500mg';

        $mockInventoryDetail1 = (object)[
            'id' => 101,
            'medicine_id' => $medicineId,
            'batch_number' => 'B001',
            'quantity' => 50,
            'medicine' => $mockMedicine // This is the mock instance
        ];
        $mockInventoryDetail2 = (object)[
            'id' => 102,
            'medicine_id' => $medicineId,
            'batch_number' => 'B002',
            'quantity' => 30,
            'medicine' => $mockMedicine
        ];

        $inventoryDetailsCollection = new Collection([$mockInventoryDetail1, $mockInventoryDetail2]);

        $this->inventoryRepository->shouldReceive('getDetailsForMedicine')
                                 ->once()
                                 ->with($medicineId)
                                 ->andReturn($inventoryDetailsCollection);

        // FIX: MedicineRepository->findById() should NOT be called in this scenario.
        $this->medicineRepository->shouldNotReceive('findById');

        // Act
        $result = $this->inventoryService->getInventoryDetailsForMedicine($medicineId);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('inventoryDetails', $result);
        $this->assertArrayHasKey('medicine', $result);
        $this->assertInstanceOf(Collection::class, $result['inventoryDetails']);
        $this->assertCount(2, $result['inventoryDetails']);
        $this->assertInstanceOf(Medicine::class, $result['medicine']);
        $this->assertEquals($medicineId, $result['medicine']->id);
        $this->assertEquals('Paracetamol 500mg', $result['medicine']->name);
    }

    #[Test]
    public function it_gets_inventory_details_and_finds_medicine_when_inventory_is_empty()
    {
        // Arrange
        $medicineId = 1;
        $mockMedicine = Mockery::mock(Medicine::class)->makePartial();
        $mockMedicine->id = $medicineId;
        $mockMedicine->name = 'Paracetamol 500mg';

        $emptyInventoryCollection = new Collection();

        $this->inventoryRepository->shouldReceive('getDetailsForMedicine')
                                 ->once()
                                 ->with($medicineId)
                                 ->andReturn($emptyInventoryCollection);

        // FIX: MedicineRepository->findById() *should* be called and return the mocked medicine.
        $this->medicineRepository->shouldReceive('findById')
            ->once()
            ->with($medicineId)
            ->andReturn($mockMedicine);

        // Act
        $result = $this->inventoryService->getInventoryDetailsForMedicine($medicineId);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('inventoryDetails', $result);
        $this->assertArrayHasKey('medicine', $result);
        $this->assertInstanceOf(Collection::class, $result['inventoryDetails']);
        $this->assertEmpty($result['inventoryDetails']);
        $this->assertInstanceOf(Medicine::class, $result['medicine']);
        $this->assertEquals($medicineId, $result['medicine']->id);
        $this->assertEquals('Paracetamol 500mg', $result['medicine']->name);
    }

    #[Test]
    public function it_throws_model_not_found_exception_when_medicine_not_found_for_details()
    {
        // Arrange
        $medicineId = 999;
        $emptyInventoryCollection = new Collection();

        $this->inventoryRepository->shouldReceive('getDetailsForMedicine')
                                 ->once()
                                 ->with($medicineId)
                                 ->andReturn($emptyInventoryCollection);

        // FIX: MedicineRepository->findById() *should* be called and return null.
        $this->medicineRepository->shouldReceive('findById')
            ->once()
            ->with($medicineId)
            ->andReturn(null);

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage("Medicine with ID {$medicineId} not found.");

        // Act
        $this->inventoryService->getInventoryDetailsForMedicine($medicineId);

        // Assert (exception handles the assertion)
    }
}