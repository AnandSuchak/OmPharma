<?php

namespace Tests\Feature\Unit;

use Tests\TestCase;
use Mockery;
use App\Services\PurchaseBillService;
use App\Interfaces\PurchaseBillRepositoryInterface;
use App\Models\PurchaseBill;
use App\Models\Inventory;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class PurchaseBillServiceTest extends TestCase
{
    protected $purchaseBillRepository;
    protected PurchaseBillService $purchaseBillService;

   protected function setUp(): void
{
    parent::setUp();

    $this->purchaseBillRepository = Mockery::mock(PurchaseBillRepositoryInterface::class);
    $this->app->instance(PurchaseBillRepositoryInterface::class, $this->purchaseBillRepository);

    // Mock Inventory for ALL tests
    \Mockery::close();
    \Mockery::mock('alias:' . Inventory::class)
        ->shouldReceive('firstOrNew')
        ->andReturnUsing(function () {
            return new class {
                public bool $exists = false;
                public ?string $expiry_date = null;
                public float $quantity = 0.0;
                public function save() { return true; }
            };
        });

    $this->purchaseBillService = $this->app->make(PurchaseBillService::class);
}


    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_get_all_purchase_bills_paginated()
    {
        $filters = ['search' => 'Bill001'];
        $paginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);

        $this->purchaseBillRepository
            ->shouldReceive('getAllPaginated')
            ->once()
            ->with($filters)
            ->andReturn($paginator);

        $result = $this->purchaseBillService->getAllPurchaseBills($filters);
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    #[Test]
    public function it_can_create_purchase_bill_and_update_inventory()
    {
        $data = [
            'purchase_items' => [
                [
                    'medicine_id' => 1,
                    'batch_number' => 'B001',
                    'expiry_date' => '2025-12-31',
                    'quantity' => 10,
                    'free_quantity' => 2,
                    'purchase_price' => 100,
                    'our_discount_percentage' => 10,
                    'gst_rate' => 12,
                ],
            ],
            'extra_discount_amount' => 5,
        ];

        $purchaseBill = new PurchaseBill();
        $purchaseBill->id = 1;

        $this->purchaseBillRepository
            ->shouldReceive('createBill')
            ->once()
            ->andReturn($purchaseBill);

        $this->purchaseBillRepository
            ->shouldReceive('createItem')
            ->once()
            ->with(1, Mockery::on(fn($item) => $item['medicine_id'] === 1));

        $result = $this->purchaseBillService->createPurchaseBill($data);
        $this->assertInstanceOf(PurchaseBill::class, $result);
    }

    #[Test]
    public function it_can_update_purchase_bill_and_adjust_inventory()
    {
        $billId = 1;
        $originalItems = new Collection([
            (object)[
                'id' => 1,
                'medicine_id' => 1,
                'batch_number' => 'B001',
                'expiry_date' => '2025-12-31',
                'quantity' => 5,
                'free_quantity' => 1,
            ],
        ]);

        $updatedData = [
            'existing_items' => [
                [
                    'id' => 1,
                    'medicine_id' => 1,
                    'batch_number' => 'B001',
                    'expiry_date' => '2025-12-31',
                    'quantity' => 8,
                    'free_quantity' => 2,
                    'purchase_price' => 100,
                    'our_discount_percentage' => 5,
                    'gst_rate' => 12,
                ],
            ],
            'new_purchase_items' => [],
        ];

        $this->purchaseBillRepository
            ->shouldReceive('getOriginalItems')
            ->once()
            ->with($billId)
            ->andReturn($originalItems);

        $this->purchaseBillRepository
            ->shouldReceive('updateItem')
            ->once()
            ->with(1, Mockery::type('array'));

        $this->purchaseBillRepository
            ->shouldReceive('updateBill')
            ->once()
            ->with($billId, Mockery::type('array'));

        $result = $this->purchaseBillService->updatePurchaseBill($billId, $updatedData);
        $this->assertTrue($result);
    }

    #[Test]
public function it_can_delete_purchase_bill_and_reverse_inventory()
{
    $billId = 1;

    // Create a PurchaseBill and attach fake related items properly
    $purchaseBill = new PurchaseBill();
    $items = collect([
        (object)[
            'medicine_id'  => 1,
            'batch_number' => 'B001',
            'expiry_date'  => '2025-12-31',
            'quantity'     => 5,
            'free_quantity'=> 1,
        ],
    ]);
    $purchaseBill->setRelation('purchaseBillItems', $items); // Correct way to set relationship

    // Mock repository methods
    $this->purchaseBillRepository
        ->shouldReceive('findById')
        ->once()
        ->with($billId)
        ->andReturn($purchaseBill);

    $this->purchaseBillRepository
        ->shouldReceive('deleteBill')
        ->once()
        ->with($billId);

    // Execute the service method
    $result = $this->purchaseBillService->deletePurchaseBill($billId);

    // Assert success
    $this->assertTrue($result);
}

    #[Test]
    public function it_throws_exception_when_deleting_nonexistent_purchase_bill()
    {
        $billId = 999;

        $this->purchaseBillRepository
            ->shouldReceive('findById')
            ->once()
            ->with($billId)
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->purchaseBillService->deletePurchaseBill($billId);
    }

    #[Test]
    public function it_handles_zero_quantity_adjustment_without_saving()
    {
        $data = [
            'purchase_items' => [
                [
                    'medicine_id' => 1,
                    'batch_number' => 'B001',
                    'expiry_date' => '2025-12-31',
                    'quantity' => 0,
                    'free_quantity' => 0,
                    'purchase_price' => 100,
                    'our_discount_percentage' => 0,
                    'gst_rate' => 0,
                ],
            ],
        ];

        $purchaseBill = new PurchaseBill();
        $purchaseBill->id = 1;

        $this->purchaseBillRepository
            ->shouldReceive('createBill')
            ->once()
            ->andReturn($purchaseBill);

        $this->purchaseBillRepository
            ->shouldReceive('createItem')
            ->once()
            ->with(1, Mockery::type('array'));

        $result = $this->purchaseBillService->createPurchaseBill($data);
        $this->assertInstanceOf(PurchaseBill::class, $result);
    }

    #[Test]
    public function it_throws_validation_exception_for_expiry_mismatch()
    {
        // Re-mock Inventory to force expiry mismatch
        \Mockery::close();
        \Mockery::mock('alias:' . Inventory::class)
            ->shouldReceive('firstOrNew')
            ->andReturn((object)[
                'exists' => true,
                'expiry_date' => Carbon::parse('2024-01-01'),
                'quantity' => 5,
                'save' => fn() => true,
            ]);

        $this->purchaseBillRepository = Mockery::mock(PurchaseBillRepositoryInterface::class);
        $this->app->instance(PurchaseBillRepositoryInterface::class, $this->purchaseBillRepository);
        $this->purchaseBillService = $this->app->make(PurchaseBillService::class);

        $data = [
            'purchase_items' => [
                [
                    'medicine_id' => 1,
                    'batch_number' => 'B001',
                    'expiry_date' => '2025-12-31', // mismatch
                    'quantity' => 10,
                    'free_quantity' => 2,
                    'purchase_price' => 100,
                    'our_discount_percentage' => 10,
                    'gst_rate' => 12,
                ],
            ],
        ];

        $purchaseBill = new PurchaseBill();
        $purchaseBill->id = 1;

        $this->purchaseBillRepository
            ->shouldReceive('createBill')
            ->once()
            ->andReturn($purchaseBill);

        $this->purchaseBillRepository
            ->shouldReceive('createItem')
            ->once();

        $this->expectException(ValidationException::class);
        $this->purchaseBillService->createPurchaseBill($data);
    }
}
