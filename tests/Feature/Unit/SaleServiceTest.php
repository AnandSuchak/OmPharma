<?php

namespace Tests\Feature\Unit;

use Tests\TestCase;
use Mockery;
use App\Services\SaleService;
use App\Interfaces\SaleRepositoryInterface;
use App\Models\Inventory;
use App\Models\Customer;
use App\Models\SaleItem;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;

class SaleServiceTest extends TestCase
{
    protected $saleRepository;
    protected SaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saleRepository = Mockery::mock(SaleRepositoryInterface::class);
        $this->app->instance(SaleRepositoryInterface::class, $this->saleRepository);

        // Default mock for Inventory
        Mockery::mock('alias:' . Inventory::class)
            ->shouldReceive('firstOrNew')
            ->andReturnUsing(function () {
                return new class {
                    public float $quantity = 100.0;
                    public function save() { return true; }
                };
            });

        // Default mock for Customer
        Mockery::mock('alias:' . Customer::class)
            ->shouldReceive('find')
            ->andReturn((object)['name' => 'Test Customer']);

        $this->saleService = $this->app->make(SaleService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_get_all_sales()
    {
        $paginator = Mockery::mock(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
        $this->saleRepository->shouldReceive('getAllPaginated')->once()->andReturn($paginator);

        $result = $this->saleService->getAllSales();
        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    #[Test]
    public function it_can_create_sale_and_update_inventory()
    {
        $sale = new \App\Models\Sale();
        $sale->id = 1;

        $items = [
            [
                'medicine_id' => 1,
                'batch_number' => 'B001',
                'quantity' => 5,
                'free_quantity' => 1,
                'sale_price' => 100,
                'discount_percentage' => 0,
                'gst_rate' => 12,
            ],
        ];

        $this->saleRepository->shouldReceive('getLatestSaleId')->andReturn(0);
        $this->saleRepository->shouldReceive('billNumberExists')->andReturn(false);
        $this->saleRepository->shouldReceive('createSale')->andReturn($sale);
        $this->saleRepository->shouldReceive('createItem')->with($sale->id, Mockery::type('array'))->once();

        $data = [
            'customer_id' => 1,
            'sale_date' => now()->toDateString(),
            'notes' => 'Test sale',
            'new_sale_items' => $items,
        ];

        $result = $this->saleService->createSale($data);
        $this->assertSame($sale, $result);
    }

#[Test]
public function it_can_update_sale_and_adjust_inventory()
{
    $sale = new \App\Models\Sale();
    $sale->id = 1;

    // Existing item with id = 1
    $item = new \App\Models\SaleItem([
        'id' => 1,
        'medicine_id' => 1,
        'batch_number' => 'B001',
        'quantity' => 5,
        'free_quantity' => 0
    ]);
    $sale->setRelation('saleItems', collect([$item]));

    // Mock repository expectations
    $this->saleRepository->shouldReceive('findById')->andReturn($sale);
    $this->saleRepository->shouldReceive('updateSale')->twice();
    $this->saleRepository->shouldNotReceive('updateItem'); // updateItem is no longer called
    $this->saleRepository->shouldReceive('createItem')->never();
    $this->saleRepository->shouldReceive('deleteItem')->never();

    $data = [
        'customer_id' => 1,
        'sale_date' => now()->toDateString(),
        'notes' => 'Updated sale',
        'existing_sale_items' => [
            // changed quantity; service no longer calls updateItem directly
            ['id' => 1, 'quantity' => 6, 'free_quantity' => 0],
        ],
    ];

    $result = $this->saleService->updateSale(1, $data);
    $this->assertTrue($result);
}


#[Test]
public function it_throws_validation_exception_when_insufficient_stock()
{
    // Clear the default Inventory alias mock created in setUp()
    Mockery::close();

    // Re-mock Inventory so quantity is always 0
    Mockery::mock('alias:' . \App\Models\Inventory::class)
        ->shouldReceive('firstOrNew')
        ->andReturnUsing(function () {
            return new class {
                public float $quantity = 0.0;
                public function save() { return true; }
            };
        });

    // Re-mock Customer::find() to avoid Eloquent calls
    Mockery::mock('alias:' . \App\Models\Customer::class)
        ->shouldReceive('find')
        ->andReturn((object)['name' => 'Dummy Customer']);

    // Repository should NOT create a sale because validation fails
    $this->saleRepository->shouldNotReceive('createSale');

    // Expect validation exception
    $this->expectException(\Illuminate\Validation\ValidationException::class);

    $data = [
        'customer_id' => 1,
        'sale_date' => now()->toDateString(),
        'notes' => 'Insufficient stock test',
        'new_sale_items' => [
            [
                'medicine_id' => 1,
                'batch_number' => 'B001',
                'quantity' => 5,
                'free_quantity' => 0,
                'sale_price' => 100,
                'discount_percentage' => 0,
                'gst_rate' => 12,
            ],
        ],
    ];

    $this->saleService->createSale($data);
}


    #[Test]
    public function it_can_delete_sale_and_restore_inventory()
    {
        $sale = new \App\Models\Sale();
        $sale->id = 1;
        $item = new SaleItem(['id' => 1, 'medicine_id' => 1, 'batch_number' => 'B001', 'quantity' => 5, 'free_quantity' => 0]);
        $sale->setRelation('saleItems', collect([$item]));

        $this->saleRepository->shouldReceive('findById')->andReturn($sale);
        $this->saleRepository->shouldReceive('deleteSale')->with(1)->once();

        $result = $this->saleService->deleteSale(1);
        $this->assertTrue($result);
    }


    #[Test]
    public function it_throws_exception_if_sale_not_found_on_update()
    {
        $this->saleRepository->shouldReceive('findById')->andReturn(null);

        $this->expectException(\Exception::class);
        $this->saleService->updateSale(999, ['customer_id' => 1]);
    }

    #[Test]
    public function it_throws_exception_if_sale_not_found_on_delete()
    {
        $this->saleRepository->shouldReceive('findById')->andReturn(null);

        $this->expectException(\Exception::class);
        $this->saleService->deleteSale(999);
    }

    #[Test]
    public function it_generates_unique_bill_number()
    {
        $this->saleRepository->shouldReceive('getLatestSaleId')->andReturn(10);
        $this->saleRepository->shouldReceive('billNumberExists')->andReturn(false);

        $result = (new \ReflectionMethod(SaleService::class, 'generateBillNumber'))->invoke($this->saleService);

        $this->assertStringStartsWith('CASH-', $result);
    }

    #[Test]
    public function it_recalculates_sale_totals()
    {
        $items = collect([
            new SaleItem(['quantity' => 2, 'sale_price' => 50, 'discount_percentage' => 10, 'gst_rate' => 12]),
        ]);

        $totals = (new \ReflectionMethod(SaleService::class, 'calculateTotals'))->invoke($this->saleService, $items);

        $this->assertIsArray($totals);
        $this->assertArrayHasKey('total', $totals);
        $this->assertArrayHasKey('gst', $totals);
    }

    #[Test]
    public function it_can_create_sale_with_multiple_items()
    {
        $sale = new \App\Models\Sale();
        $sale->id = 2;

        $items = [
            [
                'medicine_id' => 1,
                'batch_number' => 'B001',
                'quantity' => 2,
                'free_quantity' => 0,
                'sale_price' => 100,
                'discount_percentage' => 5,
                'gst_rate' => 12,
            ],
            [
                'medicine_id' => 2,
                'batch_number' => 'B002',
                'quantity' => 1,
                'free_quantity' => 0,
                'sale_price' => 200,
                'discount_percentage' => 0,
                'gst_rate' => 5,
            ],
        ];

        $this->saleRepository->shouldReceive('getLatestSaleId')->andReturn(0);
        $this->saleRepository->shouldReceive('billNumberExists')->andReturn(false);
        $this->saleRepository->shouldReceive('createSale')->andReturn($sale);
        $this->saleRepository->shouldReceive('createItem')->times(2);

        $data = [
            'customer_id' => 1,
            'sale_date' => now()->toDateString(),
            'notes' => 'Multiple items sale',
            'new_sale_items' => $items,
        ];

        $result = $this->saleService->createSale($data);
        $this->assertSame($sale, $result);
    }
}
