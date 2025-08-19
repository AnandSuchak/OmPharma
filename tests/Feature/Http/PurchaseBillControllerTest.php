<?php

// File: tests/Feature/Http/PurchaseBillControllerTest.php

namespace Tests\Feature\Http;

use App\Models\PurchaseBill;
use App\Models\Supplier;
use App\Models\Medicine;
use App\Services\PurchaseBillService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Exception;

/**
 * Feature test for the refactored PurchaseBillController.
 * This test uses mocking to isolate the controller for testing.
 */
class PurchaseBillControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $purchaseBillServiceMock;

    /**
     * Set up the test environment by mocking the PurchaseBillService.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->purchaseBillServiceMock = Mockery::mock(PurchaseBillService::class);
        $this->app->instance(PurchaseBillService::class, $this->purchaseBillServiceMock);

                // Authenticate a test user
        /** @var \App\Models\User $user */
        $user = \App\Models\User::factory()->createOne();
        $this->actingAs($user);

    }

    #[Test]
    public function it_can_display_a_list_of_purchase_bills(): void
    {
        // Arrange
        $bills = PurchaseBill::factory()->count(3)->create();
        $paginator = new LengthAwarePaginator($bills, 3, 15);

        $this->purchaseBillServiceMock
            ->shouldReceive('getAllPurchaseBills')
            ->once()
            ->andReturn($paginator);

        // Act
        $response = $this->get(route('purchase_bills.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('purchase_bills.index');
    }

    #[Test]
    public function it_can_store_a_new_purchase_bill(): void
    {
        // Arrange
        $supplier = Supplier::factory()->create();
        $medicine = Medicine::factory()->create();
        $billData = [
            'supplier_id' => $supplier->id,
            'bill_date' => now()->format('Y-m-d'),
            'bill_number' => 'BILL-001',
            'purchase_items' => [
                [
                    'medicine_id' => $medicine->id,
                    'quantity' => 10,
                    'purchase_price' => 100,
                    'sale_price' => 120,
                ]
            ]
        ];

        $this->purchaseBillServiceMock
            ->shouldReceive('createPurchaseBill')
            ->once();

        // Act
        $response = $this->post(route('purchase_bills.store'), $billData);

        // Assert
        $response->assertRedirect(route('purchase_bills.index'));
        $response->assertSessionHas('success', 'Purchase bill created and inventory updated.');
    }

    #[Test]
    public function it_can_update_an_existing_purchase_bill(): void
    {
        // Arrange
        $purchaseBill = PurchaseBill::factory()->create();
        
        // The test must send all required fields for validation to pass.
        // We merge the original bill's data with our changes.
        $updateData = array_merge($purchaseBill->toArray(), [
            'notes' => 'Updated notes'
        ]);

        $this->purchaseBillServiceMock
            ->shouldReceive('updatePurchaseBill')
            ->once()
            ->with($purchaseBill->id, Mockery::any());

        // Act
        $response = $this->put(route('purchase_bills.update', $purchaseBill), $updateData);

        // Assert
        $response->assertRedirect(route('purchase_bills.index'));
        $response->assertSessionHas('success', 'Purchase bill updated successfully.');
    }

    #[Test]
    public function it_can_delete_a_purchase_bill(): void
    {
        // Arrange
        $purchaseBill = PurchaseBill::factory()->create();

        $this->purchaseBillServiceMock
            ->shouldReceive('deletePurchaseBill')
            ->once()
            ->with($purchaseBill->id)
            ->andReturn(true);

        // Act
        $response = $this->delete(route('purchase_bills.destroy', $purchaseBill));

        // Assert
        $response->assertRedirect(route('purchase_bills.index'));
        $response->assertSessionHas('success', 'Purchase bill deleted and inventory adjusted.');
    }

    #[Test]
    public function it_handles_errors_during_store(): void
    {
        // Arrange
        $this->purchaseBillServiceMock
            ->shouldReceive('createPurchaseBill')
            ->once()
            ->andThrow(new Exception('Inventory adjustment failed'));

        // Act
        $response = $this->post(route('purchase_bills.store'), [
            'supplier_id' => Supplier::factory()->create()->id,
            'bill_date' => now()->format('Y-m-d'),
            'bill_number' => 'FAIL-BILL-001',
            'purchase_items' => [
                ['medicine_id' => Medicine::factory()->create()->id, 'quantity' => 1, 'purchase_price' => 1, 'sale_price' => 1]
            ]
        ]);

        // Assert
        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }

    #[Test]
    public function it_fails_to_store_a_purchase_bill_without_a_supplier(): void
    {
        // Arrange: Prepare valid data but intentionally omit the supplier_id
        $medicine = Medicine::factory()->create();
        $billData = [
            // 'supplier_id' is missing
            'bill_date' => now()->format('Y-m-d'),
            'bill_number' => 'BILL-002',
            'purchase_items' => [
                [
                    'medicine_id' => $medicine->id,
                    'quantity' => 5,
                    'purchase_price' => 50,
                    'sale_price' => 60,
                ]
            ]
        ];

        // Act: Post the invalid data to the store route.
        // We do NOT mock the service here, because the request should be
        // stopped by the validation layer before it ever reaches the service.
        $response = $this->post(route('purchase_bills.store'), $billData);

        // Assert: Check that the response is a redirect (validation failure)
        // and that the session contains a specific error for the 'supplier_id' field.
        $response->assertRedirect();
        $response->assertSessionHasErrors('supplier_id');
    }
}
