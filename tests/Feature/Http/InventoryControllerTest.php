<?php

// File: tests/Feature/Http/InventoryControllerTest.php

namespace Tests\Feature\Http;

use App\Models\Inventory;
use App\Models\Medicine;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Feature test for the refactored InventoryController.
 * This test uses mocking to isolate the controller for testing.
 */
class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $inventoryServiceMock;

    /**
     * Set up the test environment by mocking the InventoryService.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryServiceMock = Mockery::mock(InventoryService::class);
        $this->app->instance(InventoryService::class, $this->inventoryServiceMock);
    }

    #[Test]
    public function it_can_display_a_list_of_inventory(): void
    {
        // Arrange: We don't need real data, just a paginator instance.
        $paginator = new LengthAwarePaginator([], 0, 10);

        $this->inventoryServiceMock
            ->shouldReceive('getGroupedInventory')
            ->once()
            ->with(null) // Expect it to be called with no search term
            ->andReturn($paginator);

        // Act
        $response = $this->get(route('inventories.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('inventories.index');
        $response->assertViewHas('inventories', $paginator);
    }

    #[Test]
    public function it_can_display_the_details_for_a_medicine_inventory(): void
    {
        // Arrange
        $medicine = Medicine::factory()->create();
        $inventoryDetails = Inventory::factory()->count(2)->for($medicine)->create();
        
        $serviceResponse = [
            'inventoryDetails' => $inventoryDetails,
            'medicine' => $medicine,
        ];

        $this->inventoryServiceMock
            ->shouldReceive('getInventoryDetailsForMedicine')
            ->once()
            ->with($medicine->id)
            ->andReturn($serviceResponse);

        // Act
        $response = $this->get(route('inventories.show', $medicine->id));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('inventories.show');
        $response->assertViewHas('medicine', $medicine);
        $response->assertViewHas('inventoryDetails', $inventoryDetails);
    }
}
