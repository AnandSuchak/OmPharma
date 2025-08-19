<?php

namespace Tests\Feature\Http;

use App\Models\Inventory;
use App\Models\Medicine;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $inventoryServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryServiceMock = Mockery::mock(InventoryService::class);
        $this->app->instance(InventoryService::class, $this->inventoryServiceMock);

                // Authenticate a test user
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->createOne();
        $this->actingAs($user);

    }

    #[Test]
    public function it_can_display_a_list_of_inventory(): void
    {
        $paginator = new LengthAwarePaginator([], 0, 10);

        $this->inventoryServiceMock
            ->shouldReceive('getGroupedInventory')
            ->once()
            ->with(null)
            ->andReturn($paginator);

        $response = $this->get(route('inventories.index'));

        $response->assertStatus(200);
        $response->assertViewIs('inventories.index');
        $response->assertViewHas('inventories', $paginator);
    }

    #[Test]
    public function it_can_display_the_details_for_a_medicine_inventory(): void
    {
        $medicine = new Medicine();
        $medicine->id = 1;
        $medicine->name = 'Test Medicine';

        $inventory1 = new Inventory();
        $inventory1->id = 1;
        $inventory1->medicine_id = $medicine->id;
        $inventory1->batch_number = 'BATCH001';
        $inventory1->expiry_date = now()->addMonths(6);
        $inventory1->quantity = 50;

        $inventory2 = new Inventory();
        $inventory2->id = 2;
        $inventory2->medicine_id = $medicine->id;
        $inventory2->batch_number = 'BATCH002';
        $inventory2->expiry_date = now()->addMonths(12);
        $inventory2->quantity = 30;

        $inventoryDetails = collect([$inventory1, $inventory2]);

        $serviceResponse = [
            'inventoryDetails' => $inventoryDetails,
            'medicine' => $medicine,
        ];

        $this->inventoryServiceMock
            ->shouldReceive('getInventoryDetailsForMedicine')
            ->once()
            ->with($medicine->id)
            ->andReturn($serviceResponse);

        $response = $this->get(route('inventories.show', ['inventory' => $medicine->id]));

        $response->assertStatus(200);
        $response->assertViewIs('inventories.show');
        $response->assertViewHas('medicine', $medicine);
        $response->assertViewHas('inventoryDetails', $inventoryDetails);
    }
}
