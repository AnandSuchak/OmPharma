<?php

namespace Tests\Unit;

use Tests\TestCase;
use Mockery;
use App\Services\SaleService;
use App\Interfaces\SaleRepositoryInterface;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Exception;
use PHPUnit\Framework\Attributes\Test;

class SaleServiceTest extends TestCase
{
    protected $saleRepository;
    protected SaleService $saleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->saleRepository = Mockery::mock(SaleRepositoryInterface::class);
        $this->saleService = new SaleService($this->saleRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_get_all_sales(): void
    {
        $paginator = Mockery::mock(LengthAwarePaginator::class);
        $this->saleRepository->shouldReceive('getAllPaginated')->once()->andReturn($paginator);

        $result = $this->saleService->getAllSales();
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    #[Test]
    public function it_can_get_sale_by_id(): void
    {
        $sale = new Sale(['id' => 1]);
        $this->saleRepository->shouldReceive('findById')->with(1)->once()->andReturn($sale);

        $result = $this->saleService->getSaleById(1);
        $this->assertSame($sale, $result);
    }

    #[Test]
    public function it_throws_validation_exception_when_stock_is_insufficient()
    {
        $items = [[
            'medicine_id' => 1,
            'batch_number' => 'B001',
            'quantity' => 5,
            'free_quantity' => 1,
            'sale_price' => 100,
            'discount_percentage' => 0,
            'gst_rate' => 12
        ]];
        $data = ['customer_id' => 1, 'sale_date' => now()->toDateString(), 'new_sale_items' => $items];

        Mockery::mock('alias:App\Models\Inventory')
            ->shouldReceive('firstOrNew')
            ->andReturn(new class {
                public float $quantity = 2.0;
            });

        $this->expectException(ValidationException::class);
        $this->saleService->createSale($data);
    }

    #[Test]
    public function it_throws_exception_if_sale_not_found_on_update()
    {
        $this->saleRepository->shouldReceive('findById')->with(999)->andReturn(null);
        $this->expectException(Exception::class);
        $this->saleService->updateSale(999, ['customer_id' => 1]);
    }

    #[Test]
    public function it_throws_exception_if_sale_not_found_on_delete()
    {
        $this->saleRepository->shouldReceive('findById')->with(999)->andReturn(null);
        $this->expectException(Exception::class);
        $this->saleService->deleteSale(999);
    }

    #[Test]
    public function it_generates_unique_bill_number()
    {
        $this->saleRepository->shouldReceive('getLatestSaleId')->andReturn(123);
        $this->saleRepository->shouldReceive('billNumberExists')->with('CASH-00124')->andReturn(false);

        $method = new ReflectionMethod(SaleService::class, 'generateBillNumber');
        $method->setAccessible(true);
        $billNumber = $method->invoke($this->saleService);

        $this->assertEquals('CASH-00124', $billNumber);
    }
    
    public function it_creates_sale_successfully_when_stock_is_sufficient(): void
{
    // Mock Customer::find to avoid DB query
    Mockery::mock('alias:App\Models\Customer')
        ->shouldReceive('find')
        ->with(1)
        ->andReturn((object)['name' => 'John Doe']);

    // Mock Inventory::firstOrNew so no DB is touched
    Mockery::mock('alias:App\Models\Inventory')
        ->shouldReceive('firstOrNew')
        ->andReturn(new class {
            public float $quantity = 10.0;
            public function save() {}
        });

    // Arrange Sale
    $sale = new Sale(['id' => 1, 'bill_number' => 'CASH-00001']);

    $items = [[
        'medicine_id' => 1,
        'batch_number' => 'B001',
        'quantity' => 5,
        'free_quantity' => 1,
        'sale_price' => 100,
        'discount_percentage' => 0,
        'gst_rate' => 12
    ]];

    $data = [
        'customer_id' => 1,
        'sale_date' => now()->toDateString(),
        'notes' => 'Test sale',
        'new_sale_items' => $items
    ];

    // Mock repository methods
    $this->saleRepository->shouldReceive('getLatestSaleId')->andReturn(0);
    $this->saleRepository->shouldReceive('billNumberExists')->andReturn(false);
    $this->saleRepository->shouldReceive('createSale')->andReturn($sale);
    $this->saleRepository->shouldReceive('createItem')->with($sale->id, Mockery::type('array'))->once();

    // Act
    $result = $this->saleService->createSale($data);

    // Assert
    $this->assertSame($sale, $result);
}

    #[Test]
public function it_can_delete_sale_and_restore_inventory()
{
    // Prepare SaleItem with required properties
    $saleItem = new SaleItem();
    $saleItem->quantity = 5;
    $saleItem->free_quantity = 1;
    $saleItem->medicine_id = 1;
    $saleItem->batch_number = 'B001';

    // Mock Inventory::firstOrNew to avoid DB
    Mockery::mock('alias:App\Models\Inventory')
        ->shouldReceive('firstOrNew')
        ->andReturn(new class {
            public float $quantity = 100.0;
            public function save() {}
        });

    // Mock sale with saleItems
    $sale = new Sale();
    $sale->setRelation('saleItems', collect([$saleItem]));

    $this->saleRepository->shouldReceive('findById')->with(1)->andReturn($sale);
    $this->saleRepository->shouldReceive('deleteSale')->with(1)->once()->andReturn(true);

    // Act
    $result = $this->saleService->deleteSale(1);

    // Assert
    $this->assertTrue($result);
}

#[Test]
public function it_recalculates_sale_totals()
{
    // Create SaleItem objects with required properties
    $item1 = new SaleItem();
    $item1->quantity = 2;
    $item1->sale_price = 50;
    $item1->discount_percentage = 10;
    $item1->gst_rate = 12;

    $item2 = new SaleItem();
    $item2->quantity = 1;
    $item2->sale_price = 200;
    $item2->discount_percentage = 0;
    $item2->gst_rate = 5;

    $items = collect([$item1, $item2]);

    // Access the private method calculateTotals using reflection
    $method = new \ReflectionMethod(\App\Services\SaleService::class, 'calculateTotals');
    $method->setAccessible(true);

    $totals = $method->invoke($this->saleService, $items);

    $this->assertEquals(20.8, $totals['gst']);
    $this->assertEquals(310.8, $totals['total']);
}


}
