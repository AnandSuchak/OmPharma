<?php

// File: tests/Feature/Http/SaleControllerTest.php

namespace Tests\Feature\Http;

use App\Models\Customer;
use App\Models\Medicine;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Exception;

/**
 * Feature test for the refactored SaleController.
 * This test uses mocking to isolate the controller for testing.
 */
class SaleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $saleServiceMock;

    /**
     * Set up the test environment by mocking the SaleService.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->saleServiceMock = Mockery::mock(SaleService::class);
        $this->app->instance(SaleService::class, $this->saleServiceMock);
    }

    #[Test]
    public function it_can_display_a_list_of_sales(): void
    {
        // Arrange
        $paginator = new LengthAwarePaginator([], 0, 15);

        $this->saleServiceMock
            ->shouldReceive('getAllSales')
            ->once()
            ->andReturn($paginator);

        // Act
        $response = $this->get(route('sales.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('sales.index');
    }

    #[Test]
    public function it_can_store_a_new_sale(): void
    {
        // Arrange
        $customer = Customer::factory()->create();
        $medicine = Medicine::factory()->create();
        $saleData = [
            'customer_id' => $customer->id,
            'sale_date' => now()->format('Y-m-d'),
            'new_sale_items' => [
                [
                    'medicine_id' => $medicine->id,
                    'batch_number' => 'B001',
                    'quantity' => 10,
                    'sale_price' => 120,
                ]
            ]
        ];

        $this->saleServiceMock
            ->shouldReceive('createSale')
            ->once();

        // Act
        $response = $this->post(route('sales.store'), $saleData);

        // Assert
        $response->assertRedirect(route('sales.index'));
        $response->assertSessionHas('success', 'Sale created successfully.');
    }

    #[Test]
    public function it_can_update_an_existing_sale(): void
    {
        // Arrange
        $sale = Sale::factory()->create();
        $updateData = array_merge($sale->toArray(), [
            'notes' => 'Updated sale notes.'
        ]);

        $this->saleServiceMock
            ->shouldReceive('updateSale')
            ->once()
            ->with($sale->id, Mockery::any());

        // Act
        $response = $this->put(route('sales.update', $sale), $updateData);

        // Assert
        $response->assertRedirect(route('sales.index'));
        $response->assertSessionHas('success', 'Sale updated successfully.');
    }

    #[Test]
    public function it_can_delete_a_sale(): void
    {
        // Arrange
        $sale = Sale::factory()->create();

        $this->saleServiceMock
            ->shouldReceive('deleteSale')
            ->once()
            ->with($sale->id)
            ->andReturn(true);

        // Act
        $response = $this->delete(route('sales.destroy', $sale));

        // Assert
        $response->assertRedirect(route('sales.index'));
        $response->assertSessionHas('success', 'Sale deleted and inventory restored.');
    }

    #[Test]
    public function it_handles_errors_during_store(): void
    {
        // Arrange
        $this->saleServiceMock
            ->shouldReceive('createSale')
            ->once()
            ->andThrow(new Exception('Inventory check failed'));

        // Act
        $response = $this->post(route('sales.store'), [
            'customer_id' => Customer::factory()->create()->id,
            'sale_date' => now()->format('Y-m-d'),
            'new_sale_items' => [
                ['medicine_id' => Medicine::factory()->create()->id, 'batch_number' => 'B001', 'quantity' => 1, 'sale_price' => 1]
            ]
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }
}
